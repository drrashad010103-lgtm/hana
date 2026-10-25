<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../app/lib/db.php';

$opts = [];
foreach (array_slice($argv, 1) as $a) {
  if (preg_match('/^--([a-z0-9_-]+)(?:=(.*))?$/i', $a, $m)) $opts[strtolower($m[1])] = $m[2] ?? '1';
}
$dry = isset($opts['dry']);
$days = max(1, (int)($opts['days'] ?? 7));
$only = isset($opts['only']) ? (string)$opts['only'] : '';
$noInv = isset($opts['no-inv']);
$doMigrate = isset($opts['migrate']);

$secFile = 'C:/ProgramData/RS_Own/secrets.php';
$sec = is_file($secFile) ? @include $secFile : [];
$url = trim((string)(getenv('RS_PUSH_URL') ?: ($sec['push_url'] ?? '')));
$token = trim((string)(getenv('RS_PUSH_TOKEN') ?: ($sec['push_token'] ?? '')));
if ($url === '' || $token === '') {
  fwrite(STDERR, "missing push_url/push_token in secrets.php or RS_PUSH_URL/RS_PUSH_TOKEN\n");
  exit(2);
}
$logFile = __DIR__ . '/push_infinity.log';
function plog(string $s): void {
  global $logFile;
  @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $s . "\n", FILE_APPEND);
}
function out(string $s): void { fwrite(STDOUT, $s . "\n"); }

function inf_cookie_file(): string { return __DIR__ . '/infinity_cookie.json'; }

function inf_cookie_get(): string {
  $f = inf_cookie_file();
  if (!is_file($f)) return '';
  $j = json_decode((string)@file_get_contents($f), true);
  if (!is_array($j)) return '';
  if (time() - (int)($j['t'] ?? 0) > 20000) return '';
  return (string)($j['c'] ?? '');
}

function inf_cookie_set(string $v): void {
  @file_put_contents(inf_cookie_file(), json_encode(['c' => $v, 't' => time()]));
}

function inf_solve(string $body): string {
  if (!preg_match('/toNumbers\("([0-9a-f]+)"\),b=toNumbers\("([0-9a-f]+)"\),c=toNumbers\("([0-9a-f]+)"\)/i', $body, $m)) return '';
  $a = hex2bin($m[1]);
  $b = hex2bin($m[2]);
  $c = hex2bin($m[3]);
  $pt = @openssl_decrypt($c, 'aes-128-cbc', $a, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $b);
  if ($pt === false || $pt === '') return '';
  return bin2hex($pt);
}

function post(array $req, bool $migrate = false): array {
  global $url, $token;
  $body = json_encode($req, JSON_UNESCAPED_UNICODE);
  if ($body === false) return ['ok' => false, 'error' => 'json_encode'];
  $gz = strlen($body) > 20000;
  $payload = $gz ? gzencode($body, 6) : $body;
  $endpoint = $migrate ? preg_replace('/sync_in\.php.*$/', 'migrate_in.php?mode=apply', $url) : $url;
  $cookie = inf_cookie_get();
  $solved = 0;
  for ($try = 1; $try <= 4; $try++) {
    $ch = curl_init($endpoint);
    $h = ['Content-Type: application/json', 'X-RS-Token: ' . $token];
    if ($gz) $h[] = 'X-RS-Enc: gzip';
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_HTTPHEADER => $h,
      CURLOPT_CONNECTTIMEOUT => 15,
      CURLOPT_TIMEOUT => 180,
    ]);
    if ($cookie !== '') curl_setopt($ch, CURLOPT_COOKIE, '__test=' . $cookie);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = $resp === false ? curl_error($ch) : '';
    curl_close($ch);
    if ($resp === false) { sleep(2 * $try); continue; }
    $j = json_decode((string)$resp, true);
    if (is_array($j)) return $j;
    if ($solved < 2 && strpos((string)$resp, 'slowAES.decrypt') !== false) {
      $c = inf_solve((string)$resp);
      if ($c !== '') { $cookie = $c; inf_cookie_set($c); $solved++; continue; }
      return ['ok' => false, 'error' => 'challenge_unsolved'];
    }
    if ($try < 4) { sleep(2 * $try); continue; }
    return ['ok' => false, 'error' => 'http ' . $code . ' ' . substr((string)$resp, 0, 200)];
  }
  return ['ok' => false, 'error' => 'network ' . $err];
}

$SWAP = ['groups', 'units', 'curr_barcodes', 'curr_units', 'customers', 'cust_groups', 'areas', 'cities', 'countries', 'companies', 'branches', 'departments', 'employees', 'salesmen', 'stocks', 'stock_emps', 'safes', 'safe_emps', 'banks', 'pay_methods', 'tawla', 'tawla_groups', 'coboun', 'loyalty', 'loyalty_log', 'gard', 'gard_sub'];
$UPSERT = ['currencies', 'item_bal', 'rs_seq', 'app_settings'];
$CHUNK = ['currencies' => 400, 'item_bal' => 1500, 'rs_seq' => 500, 'app_settings' => 500];

$db = db();
out('target ' . $url);
plog('start dry=' . ($dry ? 1 : 0) . ' days=' . $days);

if ($doMigrate) {
  out('migrate ...');
  $r = $dry ? ['ok' => true, 'dry' => true] : post([], true);
  out('migrate ' . json_encode($r, JSON_UNESCAPED_UNICODE));
  plog('migrate ' . json_encode($r, JSON_UNESCAPED_UNICODE));
  if (empty($r['ok'])) exit(3);
}

if (!$dry) {
  $p = post(['op' => 'ping']);
  out('ping ' . json_encode($p, JSON_UNESCAPED_UNICODE));
  if (empty($p['ok'])) { plog('ping failed'); exit(4); }
}

$fail = 0;
function table_cols(PDO $db, string $t): array {
  $c = [];
  foreach ($db->query('SHOW COLUMNS FROM `' . $t . '`')->fetchAll(PDO::FETCH_ASSOC) as $r) $c[] = $r['Field'];
  return $c;
}
function table_rows(PDO $db, string $t, string $where = '', array $args = []): array {
  $sql = 'SELECT * FROM `' . $t . '`' . ($where !== '' ? ' WHERE ' . $where : '');
  $st = $db->prepare($sql);
  $st->execute($args);
  return $st->fetchAll(PDO::FETCH_NUM);
}

foreach ($SWAP as $t) {
  if ($only !== '' && $only !== $t) continue;
  $cols = table_cols($db, $t);
  $rows = table_rows($db, $t);
  out($t . ' rows=' . count($rows));
  if ($dry) continue;
  $r = post(['op' => 'table_swap', 'table' => $t, 'cols' => $cols, 'rows' => $rows]);
  if (empty($r['ok'])) { $fail++; out('  ERR ' . json_encode($r, JSON_UNESCAPED_UNICODE)); plog($t . ' ERR ' . json_encode($r, JSON_UNESCAPED_UNICODE)); }
}

foreach ($UPSERT as $t) {
  if ($only !== '' && $only !== $t) continue;
  $cols = table_cols($db, $t);
  $rows = table_rows($db, $t);
  $n = count($rows);
  out($t . ' rows=' . $n);
  if ($dry) continue;
  $sz = $CHUNK[$t] ?? 400;
  for ($i = 0; $i < $n; $i += $sz) {
    $part = array_slice($rows, $i, $sz);
    $r = post(['op' => 'table_upsert', 'table' => $t, 'cols' => $cols, 'rows' => $part]);
    if (empty($r['ok'])) { $fail++; out('  ERR ' . json_encode($r, JSON_UNESCAPED_UNICODE)); plog($t . ' ERR ' . json_encode($r, JSON_UNESCAPED_UNICODE)); break; }
  }
}

if (!$noInv) {
  $from = date('Y-m-d', strtotime('-' . $days . ' days'));
  $st = $db->prepare('SELECT DISTINCT proc_id FROM inv WHERE date >= ? ORDER BY proc_id');
  $st->execute([$from]);
  $procs = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
  out('invoices since ' . $from . ' count=' . count($procs));
  if (!$dry) {
    $ic = table_cols($db, 'inv');
    $sc = table_cols($db, 'inv_sub');
    foreach (array_chunk($procs, 40) as $batch) {
      $in = implode(',', $batch);
      $ir = table_rows($db, 'inv', 'proc_id IN (' . $in . ')');
      $sr = table_rows($db, 'inv_sub', 'proc_id IN (' . $in . ')');
      $r = post(['op' => 'inv_batch', 'procs' => $batch, 'inv_cols' => $ic, 'inv_rows' => $ir, 'sub_cols' => $sc, 'sub_rows' => $sr]);
      if (empty($r['ok'])) { $fail++; out('  ERR batch ' . $batch[0] . '..' . end($batch) . ' ' . json_encode($r, JSON_UNESCAPED_UNICODE)); plog('inv ERR ' . json_encode($r, JSON_UNESCAPED_UNICODE)); break; }
      out('  batch ' . $batch[0] . '-' . end($batch) . ' inv=' . ($r['inv'] ?? '?') . ' sub=' . ($r['sub'] ?? '?'));
    }
  }
}

if (!$dry) {
  $s = post(['op' => 'stats']);
  if (!empty($s['ok'])) {
    $bad = [];
    foreach ($SWAP as $t) { if ($only !== '' && $only !== $t) continue; $l = (int)$db->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn(); $r = $s['counts'][$t] ?? null; if ($r === null || (int)$r !== $l) $bad[] = $t . ' local=' . $l . ' remote=' . $r; }
    foreach ($UPSERT as $t) { if ($only !== '' && $only !== $t) continue; $l = (int)$db->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn(); $r = $s['counts'][$t] ?? null; if ($r === null || (int)$r !== $l) $bad[] = $t . ' local=' . $l . ' remote=' . $r; }
    out($bad ? ('count mismatch: ' . implode('; ', $bad)) : 'counts ok');
    if ($bad) plog('counts mismatch ' . implode('; ', $bad));
  }
}

plog('done fail=' . $fail);
out('done fail=' . $fail);
exit($fail ? 1 : 0);

