<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/paths.php';

const PUSH_TABLES = [
  'groups', 'units', 'curr_barcodes', 'curr_units', 'customers', 'cust_groups', 'areas',
  'cities', 'countries', 'companies', 'branches', 'departments', 'employees', 'salesmen',
  'stocks', 'stock_emps', 'safes', 'safe_emps', 'banks', 'pay_methods', 'tawla',
  'tawla_groups', 'coboun', 'loyalty', 'loyalty_log', 'gard', 'gard_sub', 'rs_seq',
  'app_settings', 'item_bal', 'currencies',
];

function push_token(): string {
  static $cached = null;
  if ($cached !== null) return $cached;
  // 1. القراءة من ملف الأسرار المحمي خارج مسار الويب
  $secFile = 'C:\\ProgramData\\RS_Own\\secrets.php';
  if (is_file($secFile)) {
    $sec = @include $secFile;
    if (is_array($sec) && !empty($sec['push_token'])) {
      return $cached = (string)$sec['push_token'];
    }
  }
  // 2. احتياطي للبيئات السحابية من مسار البيانات غير العام
  $d = function_exists('app_data_dir') ? app_data_dir() : (__DIR__ . '/../data');
  $f = $d . '/push_secret.php';
  if (is_file($f)) {
    $s = @include $f;
    if (is_array($s) && !empty($s['token'])) return $cached = (string)$s['token'];
  }
  $localLegacy = __DIR__ . '/push_secret.php';
  if (is_file($localLegacy)) {
    $s = @include $localLegacy;
    if (is_array($s) && !empty($s['token'])) return $cached = (string)$s['token'];
  }
  return $cached = '';
}

function push_token_ok(string $given): bool {
  $t = push_token();
  return $t !== '' && strlen($given) === strlen($t) && hash_equals($t, $given);
}

function push_log(string $line): void {
  $d = app_data_dir() . '/logs';
  if (!is_dir($d)) @mkdir($d, 0777, true);
  if (!is_dir($d) || !is_writable($d)) return;
  @file_put_contents($d . '/push.log', gmdate('Y-m-d H:i:s') . ' ' . $line . "\n", FILE_APPEND);
}

function push_ident(string $s): bool {
  return (bool)preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $s);
}

function push_table_ok(string $t): bool {
  return in_array($t, PUSH_TABLES, true);
}

function push_cols_ok(string $table, array $cols): bool {
  if (!$cols) return false;
  foreach ($cols as $c) if (!is_string($c) || !push_ident($c)) return false;
  $known = [];
  foreach (db()->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC) as $r) $known[] = $r['Field'];
  foreach ($cols as $c) if (!in_array($c, $known, true)) return false;
  return true;
}

function push_rows_ok(array $cols, $rows): bool {
  if (!is_array($rows) || count($rows) > 5000) return false;
  $n = count($cols);
  foreach ($rows as $r) {
    if (!is_array($r) || count($r) !== $n) return false;
    foreach ($r as $v) {
      if ($v !== null && !is_scalar($v)) return false;
    }
  }
  return true;
}

function push_insert(string $table, array $cols, array $rows, bool $replace): int {
  if (!$rows) return 0;
  $db = db();
  $colSql = '`' . implode('`,`', $cols) . '`';
  $ph = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
  $verb = $replace ? 'REPLACE' : 'INSERT';
  $done = 0;
  foreach (array_chunk($rows, 200) as $chunk) {
    $vals = [];
    foreach ($chunk as $r) foreach ($r as $v) $vals[] = $v === '' ? '' : $v;
    $sql = $verb . ' INTO `' . $table . '` (' . $colSql . ') VALUES ' . implode(',', array_fill(0, count($chunk), $ph));
    $db->prepare($sql)->execute($vals);
    $done += count($chunk);
  }
  return $done;
}

function push_apply(array $req): array {
  $op = (string)($req['op'] ?? '');
  $db = db();

  if ($op === 'ping') {
    return ['ok' => true, 'now' => date('c'), 'db' => (string)$db->query('SELECT DATABASE()')->fetchColumn(), 'php' => PHP_VERSION];
  }

  if ($op === 'stats') {
    $out = [];
    foreach (PUSH_TABLES as $t) {
      try { $out[$t] = (int)$db->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn(); }
      catch (Throwable $e) { $out[$t] = null; }
    }
    foreach (['inv', 'inv_sub'] as $t) {
      try { $out[$t] = (int)$db->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn(); }
      catch (Throwable $e) { $out[$t] = null; }
    }
    return ['ok' => true, 'counts' => $out];
  }

  if ($op === 'table_swap') {
    $t = (string)($req['table'] ?? '');
    $cols = $req['cols'] ?? [];
    $rows = $req['rows'] ?? [];
    if (!push_table_ok($t) || !is_array($cols) || !push_cols_ok($t, $cols) || !push_rows_ok($cols, $rows)) {
      return ['ok' => false, 'error' => 'bad_params'];
    }
    $db->beginTransaction();
    try {
      $db->exec('DELETE FROM `' . $t . '`');
      $n = push_insert($t, $cols, $rows, false);
      $db->commit();
      return ['ok' => true, 'n' => $n];
    } catch (Throwable $e) {
      if ($db->inTransaction()) $db->rollBack();
      return ['ok' => false, 'error' => 'db: ' . $e->getMessage()];
    }
  }

  if ($op === 'table_upsert') {
    $t = (string)($req['table'] ?? '');
    $cols = $req['cols'] ?? [];
    $rows = $req['rows'] ?? [];
    if (!push_table_ok($t) || !is_array($cols) || !push_cols_ok($t, $cols) || !push_rows_ok($cols, $rows)) {
      return ['ok' => false, 'error' => 'bad_params'];
    }
    $db->beginTransaction();
    try {
      $n = push_insert($t, $cols, $rows, true);
      $db->commit();
      return ['ok' => true, 'n' => $n];
    } catch (Throwable $e) {
      if ($db->inTransaction()) $db->rollBack();
      return ['ok' => false, 'error' => 'db: ' . $e->getMessage()];
    }
  }

  if ($op === 'inv_batch') {
    $procs = $req['procs'] ?? [];
    $ic = $req['inv_cols'] ?? [];
    $ir = $req['inv_rows'] ?? [];
    $sc = $req['sub_cols'] ?? [];
    $sr = $req['sub_rows'] ?? [];
    if (!is_array($procs) || !$procs || count($procs) > 500) return ['ok' => false, 'error' => 'bad_procs'];
    $ids = [];
    foreach ($procs as $p) { $p = (int)$p; if ($p <= 0) return ['ok' => false, 'error' => 'bad_procs']; $ids[] = $p; }
    if (!push_cols_ok('inv', $ic) || !push_rows_ok($ic, $ir)) return ['ok' => false, 'error' => 'bad_inv'];
    if (!push_cols_ok('inv_sub', $sc) || !push_rows_ok($sc, $sr)) return ['ok' => false, 'error' => 'bad_sub'];
    $in = implode(',', array_map('intval', $ids));
    $db->beginTransaction();
    try {
      $db->exec('DELETE FROM inv_sub WHERE proc_id IN (' . $in . ')');
      $db->exec('DELETE FROM inv WHERE proc_id IN (' . $in . ')');
      $ni = push_insert('inv', $ic, $ir, false);
      $ns = push_insert('inv_sub', $sc, $sr, false);
      $db->commit();
      return ['ok' => true, 'inv' => $ni, 'sub' => $ns, 'procs' => count($ids)];
    } catch (Throwable $e) {
      if ($db->inTransaction()) $db->rollBack();
      return ['ok' => false, 'error' => 'db: ' . $e->getMessage()];
    }
  }

  return ['ok' => false, 'error' => 'unknown_op'];
}
