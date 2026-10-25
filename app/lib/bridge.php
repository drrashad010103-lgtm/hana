<?php
require_once __DIR__ . '/db.php';

if (!function_exists('bridge_init')) {
function bridge_init(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  $db = db();
  $db->exec("CREATE TABLE IF NOT EXISTS bridge_map (
    kind VARCHAR(16) NOT NULL,
    local_id BIGINT NOT NULL,
    remote_id BIGINT NULL,
    local_proc BIGINT NULL,
    remote_proc BIGINT NULL,
    origin CHAR(1) NOT NULL DEFAULT 'L',
    local_hash CHAR(64) NULL,
    remote_hash CHAR(64) NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (kind, local_id),
    UNIQUE KEY uq_bridge_remote (kind, remote_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $db->exec("CREATE TABLE IF NOT EXISTS bridge_state (
    k VARCHAR(64) NOT NULL PRIMARY KEY,
    v TEXT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $db->exec("CREATE TABLE IF NOT EXISTS bridge_log (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ts DATETIME NOT NULL,
    level VARCHAR(8) NOT NULL,
    msg VARCHAR(255) NOT NULL,
    ctx TEXT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function bridge_cfg(): array {
  static $cfg = null;
  if (is_array($cfg)) return $cfg;
  $s = @include 'C:\\ProgramData\\RS_Own\\secrets.php';
  if (!is_array($s) || empty($s['rs_bridge_user']) || empty($s['rs_bridge_pass'])) {
    throw new RuntimeException('bridge credentials missing');
  }
  $cfg = [
    'sqlcmd' => 'C:\\Program Files\\Microsoft SQL Server\\100\\Tools\\Binn\\SQLCMD.EXE',
    'server' => (string)($s['rs_bridge_server'] ?? '.\\SQLEXPRESS'),
    'db' => (string)($s['rs_bridge_db'] ?? 'RS_RS'),
    'user' => (string)$s['rs_bridge_user'],
    'pass' => (string)$s['rs_bridge_pass'],
  ];
  return $cfg;
}

function bridge_sql(string $sql, bool $read = true): array {
  bridge_init();
  $cfg = bridge_cfg();
  $tmp = tempnam(sys_get_temp_dir(), 'rsb_');
  if ($tmp === false) throw new RuntimeException('bridge temp file failed');
  file_put_contents($tmp, "SET NOCOUNT ON;\nSET XACT_ABORT ON;\n" . $sql);
  $cmd = '"' . $cfg['sqlcmd'] . '" -S "' . $cfg['server'] . '" -U "' . $cfg['user'] . '" -d "' . $cfg['db']
    . '" -b -f 65001 -h-1 -W -s"|" -i "' . $tmp . '" 2>&1';
  $old = getenv('SQLCMDPASSWORD');
  putenv('SQLCMDPASSWORD=' . $cfg['pass']);
  $out = [];
  $rc = 1;
  exec($cmd, $out, $rc);
  if ($old === false) putenv('SQLCMDPASSWORD'); else putenv('SQLCMDPASSWORD=' . $old);
  @unlink($tmp);
  $rows = [];
  foreach ($out as $line) {
    $line = rtrim($line, "\r\n");
    if ($line === '') continue;
    // بايتات غير صالحة من sqlcmd (نص تالف في الأصل) كانت تُسقط INSERT كاملًا — نستبدلها بدل الفشل
    if (!mb_check_encoding($line, 'UTF-8')) $line = mb_scrub($line, 'UTF-8');
    $rows[] = array_map(fn($v) => trim($v), explode('|', $line));
  }
  if ($rc !== 0) {
    $err = implode("\n", array_slice($out, -8));
    throw new RuntimeException('sqlcmd ' . $rc . ': ' . mb_substr($err, 0, 500));
  }
  return $rows;
}

function bridge_state_get(string $k, string $default = ''): string {
  bridge_init();
  $st = db()->prepare("SELECT v FROM bridge_state WHERE k=?");
  $st->execute([$k]);
  $v = $st->fetchColumn();
  return $v === false ? $default : (string)$v;
}

function bridge_state_set(string $k, string $v): void {
  bridge_init();
  db()->prepare("INSERT INTO bridge_state (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$k, $v]);
}

function bridge_bump_seq(string $name, int $id): void {
  db()->prepare("UPDATE rs_seq SET val=? WHERE name=? AND val<?")->execute([$id, $name, $id]);
}

function bridge_log(string $level, string $msg, $ctx = null): void {
  bridge_init();
  try {
    db()->prepare("INSERT INTO bridge_log (ts,level,msg,ctx) VALUES (NOW(),?,?,?)")
      ->execute([$level, mb_substr($msg, 0, 255), $ctx === null ? null : json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
  } catch (Throwable $e) {}
}

function bridge_map_get(string $kind, int $localId): ?array {
  bridge_init();
  $st = db()->prepare("SELECT * FROM bridge_map WHERE kind=? AND local_id=?");
  $st->execute([$kind, $localId]);
  $r = $st->fetch();
  return $r ?: null;
}

function bridge_map_by_remote(string $kind, int $remoteId): ?array {
  bridge_init();
  $st = db()->prepare("SELECT * FROM bridge_map WHERE kind=? AND remote_id=?");
  $st->execute([$kind, $remoteId]);
  $r = $st->fetch();
  return $r ?: null;
}

function bridge_map_set(string $kind, int $localId, int $remoteId, ?int $localProc, ?int $remoteProc, string $origin, ?string $localHash = null, ?string $remoteHash = null): void {
  bridge_init();
  $sql = "INSERT INTO bridge_map (kind,local_id,remote_id,local_proc,remote_proc,origin,local_hash,remote_hash,updated_at)
          VALUES (?,?,?,?,?,?,?,?,NOW())
          ON DUPLICATE KEY UPDATE remote_id=VALUES(remote_id), local_proc=VALUES(local_proc), remote_proc=VALUES(remote_proc),
            origin=VALUES(origin), local_hash=VALUES(local_hash), remote_hash=VALUES(remote_hash), updated_at=NOW()";
  db()->prepare($sql)->execute([$kind, $localId, $remoteId, $localProc, $remoteProc, $origin, $localHash, $remoteHash]);
}

function bridge_hash(array $row): string {
  ksort($row);
  return hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function bridge_norm_name($v): string {
  return preg_replace('/\s+/u', ' ', trim((string)$v));
}

function bridge_norm_mobile($v): string {
  return preg_replace('/\D+/', '', (string)$v);
}

function bridge_local_columns(string $table): array {
  static $cache = [];
  if (isset($cache[$table])) return $cache[$table];
  $out = [];
  foreach (db()->query("DESCRIBE `$table`") as $r) $out[] = $r['Field'];
  return $cache[$table] = $out;
}

function bridge_remote_columns(string $table): array {
  static $cache = [];
  if (isset($cache[$table])) return $cache[$table];
  $rows = bridge_sql("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$table' ORDER BY ORDINAL_POSITION", true);
  $out = [];
  foreach ($rows as $r) {
    $name = trim((string)($r[0] ?? ''));
    if ($name !== '') $out[strtolower($name)] = $name;
  }
  return $cache[$table] = $out;
}

function bridge_common(string $remoteTable, string $localTable): array {
  $remote = bridge_remote_columns($remoteTable);
  $out = [];
  foreach (bridge_local_columns($localTable) as $f) {
    $k = strtolower($f);
    if (isset($remote[$k])) $out[$f] = $remote[$k];
  }
  return $out;
}

function bridge_lit($v): string {
  if ($v === null) return 'NULL';
  if (is_bool($v)) return $v ? '1' : '0';
  if (is_int($v) || is_float($v)) return (string)$v;
  return "N'" . str_replace("'", "''", (string)$v) . "'";
}

// خريطة أنواع أعمدة الجدول البعيد (DATA_TYPE صغير)
function bridge_push_types(string $remoteTable): array {
  $t = [];
  foreach (bridge_sql("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='" . str_replace("'", "''", $remoteTable) . "'", true) as $r) {
    if (count($r) >= 2) $t[strtolower(trim((string)$r[0]))] = strtolower(trim((string)$r[1]));
  }
  return $t;
}

// literal واعي بالنوع: الأعمدة الصحيحة عند المهندس (مثل sale_price int) لا تقبل كسورًا
function bridge_lit_for(string $rsType, $v): string {
  if ($v === null) return 'NULL';
  if (in_array($rsType, ['int', 'bigint', 'smallint', 'tinyint'], true)) {
    return (string)(int)round((float)$v);
  }
  return bridge_lit($v);
}

function bridge_next_remote_id(string $table, string $col = 'id'): int {
  $rows = bridge_sql("SELECT ISNULL(MAX([$col]),0)+1 FROM dbo.[$table] WITH (UPDLOCK,HOLDLOCK)", true);
  return (int)($rows[0][0] ?? 0);
}

function bridge_push_item(int $localId): array {
  $st = db()->prepare("SELECT * FROM currencies WHERE id=?");
  $st->execute([$localId]);
  $row = $st->fetch();
  if (!$row) throw new RuntimeException('item not found ' . $localId);
  $common = bridge_common('Currencies', 'currencies');
  $rsTypes = bridge_push_types('Currencies');
  $cols = ['id'];
  $vals = ['@id'];
  foreach ($common as $local => $remote) {
    if (strtolower($local) === 'id') continue;
    $cols[] = $remote;
    $vals[] = bridge_lit_for($rsTypes[strtolower($remote)] ?? '', $row[$local]);
  }
  $sql = 'BEGIN TRAN; DECLARE @id int; SELECT @id=ISNULL(MAX([id]),0)+1 FROM dbo.Currencies WITH (TABLOCKX,HOLDLOCK); INSERT INTO dbo.Currencies ('
    . implode(',', array_map(fn($c) => '[' . $c . ']', $cols)) . ') VALUES ('
    . implode(',', array_map('bridge_lit', $vals)) . '); SELECT @id; COMMIT;';
  $r = bridge_sql($sql, false);
  $remoteId = (int)($r[0][0] ?? 0);
  if ($remoteId <= 0) throw new RuntimeException('remote item id allocation failed');
  bridge_map_set('item', $localId, $remoteId, null, null, 'L', bridge_hash($row), null);
  bridge_log('info', 'item pushed', ['local' => $localId, 'remote' => $remoteId]);
  return ['local' => $localId, 'remote' => $remoteId];
}

function bridge_push_customer(int $localId): array {
  $st = db()->prepare("SELECT * FROM customers WHERE id=?");
  $st->execute([$localId]);
  $row = $st->fetch();
  if (!$row) throw new RuntimeException('customer not found ' . $localId);
  $common = bridge_common('Customers', 'customers');
  $rsTypes = bridge_push_types('Customers');
  $cols = [];
  $vals = [];
  foreach ($common as $local => $remote) {
    if (strtolower($local) === 'id') continue;
    $cols[] = $remote;
    $vals[] = bridge_lit_for($rsTypes[strtolower($remote)] ?? '', $row[$local]);
  }
  $sql = 'BEGIN TRAN; INSERT INTO dbo.Customers (' . implode(',', array_map(fn($c) => '[' . $c . ']', $cols)) . ') VALUES ('
    . implode(',', array_map('bridge_lit', $vals)) . '); SELECT CONVERT(int,SCOPE_IDENTITY()); COMMIT;';
  $r = bridge_sql($sql, false);
  $remoteId = (int)($r[0][0] ?? 0);
  if ($remoteId <= 0) throw new RuntimeException('customer identity failed');
  bridge_map_set('customer', $localId, $remoteId, null, null, 'L', bridge_hash($row), null);
  bridge_log('info', 'customer pushed', ['local' => $localId, 'remote' => $remoteId]);
  return ['local' => $localId, 'remote' => $remoteId];
}

function bridge_ensure_item(int $localId): int {
  $m = bridge_map_get('item', $localId);
  if ($m) return (int)$m['remote_id'];
  if (bridge_map_by_remote('item', $localId)) return (int)bridge_push_item($localId)['remote'];
  $rows = bridge_sql("SELECT id FROM dbo.Currencies WHERE id=$localId", true);
  if ($rows) {
    bridge_map_set('item', $localId, $localId, null, null, 'R');
    return $localId;
  }
  return (int)bridge_push_item($localId)['remote'];
}

function bridge_ensure_customer(int $localId): int {
  if ($localId <= 0) return 0;
  $m = bridge_map_get('customer', $localId);
  if ($m) return (int)$m['remote_id'];
  if (bridge_map_by_remote('customer', $localId)) return (int)bridge_push_customer($localId)['remote'];
  $rows = bridge_sql("SELECT id FROM dbo.Customers WHERE id=$localId", true);
  if ($rows) {
    bridge_map_set('customer', $localId, $localId, null, null, 'R');
    return $localId;
  }
  return (int)bridge_push_customer($localId)['remote'];
}

function bridge_invoice_payload(array $head, array $lines): array {
  $h = $head;
  unset($h['inv_notes']);
  return ['head' => $h, 'lines' => $lines];
}

function bridge_push_invoice(int $localId, bool $dry = false): array {
  $st = db()->prepare("SELECT * FROM inv WHERE id=? AND branch=99");
  $st->execute([$localId]);
  $head = $st->fetch();
  if (!$head) throw new RuntimeException('invoice not found');
  $map = bridge_map_get('invoice', $localId);
  $st = db()->prepare("SELECT * FROM inv_sub WHERE proc_id=? ORDER BY id");
  $st->execute([(int)$head['proc_id']]);
  $lines = $st->fetchAll();
  if (!$lines && (int)$head['IS_Deleted'] === 1 && !$map) {
    return ['skipped' => true, 'deleted' => true, 'local' => $localId];
  }
  if (!$lines) throw new RuntimeException('invoice lines empty');
  $hash = bridge_hash(bridge_invoice_payload($head, $lines));
  if ($map && $map['local_hash'] === $hash) {
    return ['skipped' => true, 'local' => $localId];
  }
  if ($dry) {
    return ['dry' => true, 'local' => $localId, 'mapped' => (bool)$map, 'deleted' => (int)$head['IS_Deleted']];
  }
  $remoteProc = $map ? (int)$map['remote_proc'] : 0;
  $remoteId = $map ? (int)$map['remote_id'] : 0;
  if ($map) {
    $rows = bridge_sql("SELECT id,proc_id FROM dbo.Inv WHERE proc_id=$remoteProc AND branch=99", true);
    if (!$rows) $map = null;
  }
  if (!$map && (int)$head['IS_Deleted'] === 0) {
    $cust = bridge_ensure_customer((int)($head['cust_id'] ?? 0));
    $itemMap = [];
    foreach ($lines as $ln) {
      $lid = (int)$ln['currency_from'];
      if (!isset($itemMap[$lid])) $itemMap[$lid] = bridge_ensure_item($lid);
    }
    $common = bridge_common('Inv', 'inv');
    $cols = ['id', 'branch'];
    $vals = ['@i', '99'];
    foreach ($common as $local => $remote) {
      $lk = strtolower($local);
      if (in_array($lk, ['proc_id', 'id', 'branch'], true)) continue;
      $v = $head[$local];
      if ($lk === 'cust_id') $v = $cust;
      $cols[] = $remote;
      $vals[] = $lk === 'cust_id' ? '@cust' : bridge_lit($v);
    }
    $sql = "BEGIN TRAN;
      DECLARE @p int, @i int, @cust int;
      SET @cust=$cust;
      SELECT @i=ISNULL(MAX(id),0)+1 FROM dbo.Inv WITH (UPDLOCK,HOLDLOCK);
      INSERT INTO dbo.Inv (" . implode(',', array_map(fn($c) => '[' . $c . ']', $cols)) . ") VALUES (" . implode(',', $vals) . ");
      SET @p=CAST(SCOPE_IDENTITY() AS int);
      SELECT @p,@i;
      COMMIT;";
    $r = bridge_sql($sql, false);
    $out = $r[0] ?? [];
    $remoteProc = (int)($out[0] ?? 0);
    $remoteId = (int)($out[1] ?? 0);
    if ($remoteProc <= 0 || $remoteId <= 0) throw new RuntimeException('remote invoice id allocation failed');
    $map = ['remote_proc' => $remoteProc, 'remote_id' => $remoteId];
    bridge_map_set('invoice', $localId, $remoteId, (int)$head['proc_id'], $remoteProc, 'L', null, null);
  } elseif (!$map) {
    return ['skipped' => true, 'deleted' => true, 'local' => $localId];
  }
  $remoteProc = (int)$map['remote_proc'];
  $remoteId = (int)$map['remote_id'];
  $custRemote = bridge_ensure_customer((int)($head['cust_id'] ?? 0));
  $itemMap = [];
  foreach ($lines as $ln) {
    $lid = (int)$ln['currency_from'];
    if (!isset($itemMap[$lid])) $itemMap[$lid] = bridge_ensure_item($lid);
  }
  $sets = [];
  $common = bridge_common('Inv', 'inv');
  foreach ($common as $local => $remote) {
    $lk = strtolower($local);
    if (in_array($lk, ['proc_id', 'id', 'branch', 'is_deleted'], true)) continue;
    $v = $lk === 'cust_id' ? $custRemote : $head[$local];
    $sets[] = '[' . $remote . ']=' . bridge_lit($v);
  }
  $sets[] = '[branch]=99';
  $sets[] = '[IS_Deleted]=' . (int)$head['IS_Deleted'];
  $lineSql = "DELETE FROM dbo.Inv_Sub WHERE proc_id=$remoteProc;";
  $subCommon = bridge_common('Inv_Sub', 'inv_sub');
  $subCols = [];
  foreach ($subCommon as $local => $remote) {
    if (in_array(strtolower($local), ['id', 'proc_id'], true)) continue;
    $subCols[] = $remote;
  }
  $subCols[] = 'proc_id';
  foreach ($lines as $ln) {
    $vals = [];
    foreach ($subCommon as $local => $remote) {
      if (in_array(strtolower($local), ['id', 'proc_id'], true)) continue;
      $v = $ln[$local];
      if (strtolower($local) === 'currency_from') $v = $itemMap[(int)$ln['currency_from']];
      $vals[] = $v;
    }
    $vals[] = $remoteProc;
    $lineSql .= " INSERT INTO dbo.Inv_Sub (" . implode(',', array_map(fn($c) => '[' . $c . ']', $subCols)) . ") VALUES ("
      . implode(',', array_map('bridge_lit', $vals)) . ");";
  }
  $sql = 'BEGIN TRAN; UPDATE dbo.Inv SET ' . implode(',', $sets) . ' WHERE proc_id=' . $remoteProc . ' AND branch=99; '
    . $lineSql . ' COMMIT;';
  bridge_sql($sql, false);
  bridge_map_set('invoice', $localId, $remoteId, (int)$head['proc_id'], $remoteProc, 'L', $hash, null);
  bridge_log('info', 'invoice pushed', ['local' => $localId, 'remote_proc' => $remoteProc, 'remote_id' => $remoteId, 'deleted' => (int)$head['IS_Deleted']]);
  return ['local' => $localId, 'remote_proc' => $remoteProc, 'remote_id' => $remoteId];
}

function bridge_pull_items(): array {
  $common = bridge_common('Currencies', 'currencies');
  $select = implode(',', array_map(fn($c) => '[' . $c . ']', array_values($common)));
  $maxRows = bridge_sql("SELECT ISNULL(MAX(id),0) FROM dbo.Currencies", true);
  $remoteMax = (int)($maxRows[0][0] ?? 0);
  $last = (int)bridge_state_get('bridge_item_max', '0');
  if ($remoteMax <= $last) return ['mapped' => 0, 'inserted' => 0];
  $rows = bridge_sql("SELECT $select FROM dbo.Currencies WHERE id>$last ORDER BY id", true);
  $out = ['mapped' => 0, 'inserted' => 0];
  $maxLocal = (int)db()->query("SELECT IFNULL(MAX(id),0) FROM currencies")->fetchColumn();
  foreach ($rows as $r) {
    $row = [];
    $i = 0;
    foreach ($common as $local => $remote) {
      $v = $r[$i++] ?? null;
      if ($v === 'NULL') $v = null;
      $row[$local] = $v;
    }
    $remoteId = (int)($row['id'] ?? 0);
    if ($remoteId <= 0) continue;
    $mapped = bridge_map_by_remote('item', $remoteId);
    if ($mapped) {
      bridge_bump_seq('tbl_currencies_id', max((int)$mapped['local_id'], $remoteId));
      continue;
    }
    $st = db()->prepare("SELECT 1 FROM currencies WHERE id=?");
    $st->execute([$remoteId]);
    if ($st->fetch()) {
      do { $localId = ++$maxLocal; } while (bridge_map_get('item', $localId));
    } else {
      $localId = $remoteId;
      if ($localId > $maxLocal) $maxLocal = $localId;
    }
    $cols = ['id'];
    $vals = [$localId];
    foreach ($common as $local => $remote) {
      if (strtolower($local) === 'id') continue;
      $cols[] = $local;
      $vals[] = $row[$local];
    }
    $sql = "INSERT INTO currencies (" . implode(',', array_map(fn($c) => "`$c`", $cols)) . ") VALUES ("
      . rtrim(str_repeat('?,', count($cols)), ',') . ")";
    db()->prepare($sql)->execute($vals);
    bridge_map_set('item', $localId, $remoteId, null, null, 'R');
    bridge_bump_seq('tbl_currencies_id', max($localId, $remoteId));
    $out['inserted']++;
  }
  bridge_state_set('bridge_item_max', (string)$remoteMax);
  return $out;
}

function bridge_pull_customers(): array {
  $common = bridge_common('Customers', 'customers');
  $select = implode(',', array_map(fn($c) => '[' . $c . ']', array_values($common)));
  $rows = bridge_sql("SELECT $select FROM dbo.Customers ORDER BY id", true);
  $out = ['mapped' => 0, 'inserted' => 0];
  $locals = db()->query("SELECT id,name,mobile FROM customers WHERE IFNULL(IS_Deleted,0)=0 ORDER BY id")->fetchAll();
  $byMobile = [];
  $byName = [];
  foreach ($locals as $l) {
    $m = bridge_norm_mobile($l['mobile']);
    $n = bridge_norm_name($l['name']);
    if ($m !== '') $byMobile[$m][] = $l;
    if ($n !== '') $byName[$n][] = $l;
  }
  $maxLocal = (int)db()->query("SELECT IFNULL(MAX(id),0) FROM customers")->fetchColumn();
  foreach ($rows as $r) {
    $row = [];
    $i = 0;
    foreach ($common as $local => $remote) {
      $v = $r[$i++] ?? null;
      if ($v === 'NULL') $v = null;
      $row[$local] = $v;
    }
    $remoteId = (int)($row['id'] ?? 0);
    if ($remoteId <= 0) continue;
    $mapped = bridge_map_by_remote('customer', $remoteId);
    if ($mapped) {
      bridge_bump_seq('tbl_customers_id', max((int)$mapped['local_id'], $remoteId));
      continue;
    }
    $candidate = null;
    $mobile = bridge_norm_mobile($row['mobile'] ?? '');
    $name = bridge_norm_name($row['name'] ?? '');
    $pool = $mobile !== '' ? ($byMobile[$mobile] ?? []) : ($byName[$name] ?? []);
    foreach ($pool as $l) {
      $m = bridge_map_get('customer', (int)$l['id']);
      if (!$m || (int)$m['remote_id'] === $remoteId) { $candidate = $l; break; }
    }
    if (!$candidate && $mobile === '' && $name !== '') {
      foreach (($byName[$name] ?? []) as $l) {
        $m = bridge_map_get('customer', (int)$l['id']);
        if (!$m || (int)$m['remote_id'] === $remoteId) { $candidate = $l; break; }
      }
    }
    if ($candidate) {
      bridge_map_set('customer', (int)$candidate['id'], $remoteId, null, null, 'R');
      bridge_bump_seq('tbl_customers_id', max((int)$candidate['id'], $remoteId));
      $out['mapped']++;
      continue;
    }
    $st = db()->prepare("SELECT 1 FROM customers WHERE id=?");
    $st->execute([$remoteId]);
    if ($st->fetch()) {
      $localId = ++$maxLocal;
    } else {
      $localId = $remoteId;
      if ($remoteId > $maxLocal) $maxLocal = $remoteId;
    }
    $cols = ['id'];
    $vals = [$localId];
    foreach ($common as $local => $remote) {
      if (strtolower($local) === 'id') continue;
      $cols[] = $local;
      $vals[] = $row[$local];
    }
    $sql = "INSERT INTO customers (" . implode(',', array_map(fn($c) => "`$c`", $cols)) . ") VALUES ("
      . rtrim(str_repeat('?,', count($cols)), ',') . ")";
    db()->prepare($sql)->execute($vals);
    bridge_map_set('customer', $localId, $remoteId, null, null, 'R');
    bridge_bump_seq('tbl_customers_id', max($localId, $remoteId));
    $out['inserted']++;
  }
  return $out;
}

function bridge_php_bin(): string {
  $bin = PHP_BINARY;
  if ($bin !== '' && preg_match('/\.exe$/i', $bin) && is_file($bin)) return $bin;
  foreach (['C:\\phplocal\\php\\php.exe'] as $p) if (is_file($p)) return $p;
  return 'php';
}

function bridge_item_same(array $local, array $remote): bool {
  $lb = trim((string)($local['barcode'] ?? ''));
  $rb = trim((string)($remote['barcode'] ?? ''));
  if ($lb !== '' && $rb !== '') return $lb === $rb;
  $ls = trim((string)($local['symbol'] ?? ''));
  $rs = trim((string)($remote['symbol'] ?? ''));
  return $ls !== '' && $rs !== '' && mb_strtolower($ls, 'UTF-8') === mb_strtolower($rs, 'UTF-8');
}

function bridge_customer_same(array $local, array $remote): bool {
  $lm = bridge_norm_mobile($local['mobile'] ?? '');
  $rm = bridge_norm_mobile($remote['mobile'] ?? '');
  if ($lm !== '' && $rm !== '') return $lm === $rm;
  $ln = bridge_norm_name($local['name'] ?? '');
  $rn = bridge_norm_name($remote['name'] ?? '');
  return $ln !== '' && $ln === $rn;
}

function bridge_run_invoice_pull(bool $dry = false): array {
  $script = dirname(__DIR__, 2) . '/tools/sync_rs_to_own.php';
  $cmd = [bridge_php_bin(), $script, '--invoices-only', '--sql-auth'];
  if ($dry) $cmd[] = '--dry';
  $spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
  $p = @proc_open($cmd, $spec, $pipes, null, null, ['bypass_shell' => true]);
  if (!is_resource($p)) return ['ok' => false, 'rc' => -1, 'output' => 'تعذر تشغيل sync_rs_to_own.php'];
  $out = (string)stream_get_contents($pipes[1]);
  $err = (string)stream_get_contents($pipes[2]);
  fclose($pipes[1]); fclose($pipes[2]);
  $rc = proc_close($p);
  $text = trim($out . ($err !== '' ? "\n" . $err : ''));
  return ['ok' => $rc === 0, 'rc' => $rc, 'output' => $text];
}

function bridge_with_lock(callable $fn): array {
  $lockf = dirname(__DIR__, 2) . '/tools/bridge.lock';
  $lock = @fopen($lockf, 'c');
  if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    if ($lock) fclose($lock);
    return ['ok' => false, 'busy' => true, 'output' => 'عملية أخرى قيد التنفيذ، انتظر قليلًا ثم أعد المحاولة.'];
  }
  try {
    return $fn();
  } finally {
    flock($lock, LOCK_UN);
    fclose($lock);
  }
}

function bridge_run_export(bool $dry = false): array {
  return bridge_with_lock(function () use ($dry): array {
    $t0 = microtime(true);
    $out = ['ok' => true, 'dry' => $dry, 'items' => 0, 'customers' => 0, 'invoices' => 0, 'mapped' => 0, 'errors' => []];
    $remoteItems = [];
    foreach (bridge_sql("SELECT id,symbol,barcode FROM dbo.Currencies ORDER BY id", true) as $r) {
      $remoteItems[(int)($r[0] ?? 0)] = ['symbol' => (string)($r[1] ?? ''), 'barcode' => (string)($r[2] ?? '')];
    }
    $q = db()->query("SELECT c.* FROM currencies c LEFT JOIN bridge_map m ON m.kind='item' AND m.local_id=c.id WHERE m.local_id IS NULL AND IFNULL(c.IS_Deleted,0)=0 ORDER BY c.id");
    foreach ($q as $row) {
      $id = (int)$row['id'];
      try {
        if (!bridge_map_by_remote('item', $id) && isset($remoteItems[$id]) && bridge_item_same($row, $remoteItems[$id])) {
          if (!$dry) bridge_map_set('item', $id, $id, null, null, 'R');
          $out['mapped']++;
          continue;
        }
        if ($dry) { $out['items']++; continue; }
        bridge_push_item($id);
        $out['items']++;
      } catch (Throwable $e) {
        $out['ok'] = false;
        $out['errors'][] = 'صنف ' . $id . ': ' . $e->getMessage();
        bridge_log('error', 'ui item push failed', ['id' => $id, 'error' => $e->getMessage()]);
      }
    }
    $remoteCustomers = [];
    foreach (bridge_sql("SELECT id,name,mobile FROM dbo.Customers ORDER BY id", true) as $r) {
      $remoteCustomers[(int)($r[0] ?? 0)] = ['name' => (string)($r[1] ?? ''), 'mobile' => (string)($r[2] ?? '')];
    }
    $q = db()->query("SELECT c.* FROM customers c LEFT JOIN bridge_map m ON m.kind='customer' AND m.local_id=c.id WHERE m.local_id IS NULL AND IFNULL(c.IS_Deleted,0)=0 ORDER BY c.id");
    foreach ($q as $row) {
      $id = (int)$row['id'];
      try {
        if (!bridge_map_by_remote('customer', $id) && isset($remoteCustomers[$id]) && bridge_customer_same($row, $remoteCustomers[$id])) {
          if (!$dry) bridge_map_set('customer', $id, $id, null, null, 'R');
          $out['mapped']++;
          continue;
        }
        if ($dry) { $out['customers']++; continue; }
        bridge_push_customer($id);
        $out['customers']++;
      } catch (Throwable $e) {
        $out['ok'] = false;
        $out['errors'][] = 'عميل ' . $id . ': ' . $e->getMessage();
        bridge_log('error', 'ui customer push failed', ['id' => $id, 'error' => $e->getMessage()]);
      }
    }
    foreach (db()->query("SELECT id FROM inv WHERE branch=99 AND IFNULL(IS_Temp,0)=0 ORDER BY id") as $r) {
      $id = (int)$r['id'];
      try {
        $x = bridge_push_invoice($id, $dry);
        if (!empty($x['dry'])) { $out['invoices']++; }
        elseif (empty($x['skipped'])) $out['invoices']++;
      } catch (Throwable $e) {
        $out['ok'] = false;
        $out['errors'][] = 'فاتورة ' . $id . ': ' . $e->getMessage();
        bridge_log('error', 'ui invoice push failed', ['id' => $id, 'error' => $e->getMessage()]);
      }
    }
    $out['seconds'] = round(microtime(true) - $t0, 2);
    if (!$dry) {
      bridge_state_set('ui_last_export', json_encode(['at' => date('Y-m-d H:i:s'), 'result' => $out], JSON_UNESCAPED_UNICODE));
      bridge_log($out['ok'] ? 'info' : 'error', 'تصدير يدوي', $out);
    }
    return $out;
  });
}

function bridge_run_import(bool $dry = false): array {
  return bridge_with_lock(function () use ($dry): array {
    $t0 = microtime(true);
    $out = ['ok' => true, 'dry' => $dry, 'items' => 0, 'customers' => 0, 'invoices' => 0, 'added' => 0, 'updated' => 0, 'output' => ''];
    try {
      if (!$dry) {
        $it = bridge_pull_items();
        $cu = bridge_pull_customers();
        $out['items'] = (int)$it['inserted'];
        $out['customers'] = (int)$cu['inserted'];
      }
      $sync = bridge_run_invoice_pull($dry);
      $out['output'] = $sync['output'];
      if (preg_match('/added=(\d+)/', $out['output'], $m)) $out['added'] = (int)$m[1];
      if (preg_match('/updated=(\d+)/', $out['output'], $m)) $out['updated'] = (int)$m[1];
      $out['invoices'] = $out['added'] + $out['updated'];
      $out['ok'] = $out['ok'] && $sync['ok'];
      if (!$sync['ok']) $out['errors'] = ['فشل استيراد الفواتير'];
    } catch (Throwable $e) {
      $out['ok'] = false;
      $out['output'] = $e->getMessage();
      $out['errors'] = [$e->getMessage()];
      bridge_log('error', 'ui import failed', ['error' => $e->getMessage()]);
    }
    $out['seconds'] = round(microtime(true) - $t0, 2);
    if (!$dry) {
      bridge_state_set('ui_last_import', json_encode(['at' => date('Y-m-d H:i:s'), 'result' => $out], JSON_UNESCAPED_UNICODE));
      bridge_log($out['ok'] ? 'info' : 'error', 'استيراد يدوي', $out);
    }
    return $out;
  });
}
}
