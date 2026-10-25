<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

if (!function_exists('customer_next_code')) {
  function customer_next_code(): int {
    return (int)db()->query("SELECT IFNULL(MAX(id),0)+1 FROM customers")->fetchColumn();
  }

  function customer_seq_sync(int $code): void {
    try {
      db()->prepare("INSERT INTO rs_seq (name,val) VALUES ('tbl_customers_id',?) ON DUPLICATE KEY UPDATE val=GREATEST(val,VALUES(val))")
        ->execute([$code]);
    } catch (Throwable $t) {}
  }

  function customer_save(array $in, int $id = 0): array {
    $db = db();
    $norm = [
      'name' => fn($v) => mb_substr(trim((string)$v), 0, 250),
      'act' => fn($v) => in_array((int)$v, [1, 2], true) ? (int)$v : 1,
      'tax_no' => fn($v) => mb_substr(trim((string)$v), 0, 64),
      'national_id' => fn($v) => mb_substr(trim((string)$v), 0, 32),
      'country' => fn($v) => (int)$v,
      'city' => fn($v) => (int)$v,
      'area' => fn($v) => (int)$v,
      'email' => fn($v) => mb_substr(trim((string)$v), 0, 128),
      'mobile' => fn($v) => mb_substr(preg_replace('/[^\d+]/', '', (string)$v), 0, 32),
      'mobile2' => fn($v) => mb_substr(preg_replace('/[^\d+]/', '', (string)$v), 0, 32),
      'fax' => fn($v) => mb_substr(trim((string)$v), 0, 32),
      'notes' => fn($v) => mb_substr(trim((string)$v), 0, 255),
      'discount_balance' => fn($v) => (float)$v,
      'maxdepit' => fn($v) => (float)$v,
      'accountNo' => fn($v) => (int)$v,
    ];
    $cols = array_keys($norm);

    if ($id > 0) {
      $sets = [];
      $vals = [];
      foreach ($norm as $k => $fn) {
        if (!array_key_exists($k, $in)) continue;
        $v = $fn($in[$k]);
        if ($k === 'name' && $v === '') return ['ok' => false, 'error' => 'اسم العميل مطلوب'];
        $sets[] = "`$k`=?";
        $vals[] = $v;
      }
      if (!$sets) return ['ok' => true, 'id' => $id, 'edited' => true];
      $db->prepare("UPDATE customers SET " . implode(',', $sets) . " WHERE id=?")->execute([...$vals, $id]);
      try { audit_log('edit', 'customer', $id); } catch (Throwable $t) {}
      return ['ok' => true, 'id' => $id, 'edited' => true];
    }

    $defaults = [
      'name' => '', 'act' => 1, 'tax_no' => '', 'national_id' => '', 'country' => 0,
      'city' => 0, 'area' => 0, 'email' => '', 'mobile' => '', 'mobile2' => '',
      'fax' => '', 'notes' => '', 'discount_balance' => 0, 'maxdepit' => 0, 'accountNo' => 0,
    ];
    $row = $defaults;
    foreach ($norm as $k => $fn) {
      if (array_key_exists($k, $in)) $row[$k] = $fn($in[$k]);
    }
    if ($row['name'] === '') return ['ok' => false, 'error' => 'اسم العميل مطلوب'];

    $code = 0;
    for ($try = 1; $try <= 5; $try++) {
      $code = customer_next_code();
      try {
        $db->prepare("INSERT INTO customers (id," . implode(',', $cols) . ",IS_Deleted) VALUES (?" . str_repeat(',?', count($cols)) . ",0)")
          ->execute([$code, ...array_values($row)]);
        break;
      } catch (Throwable $e) {
        if ($try === 5 || stripos($e->getMessage(), 'Duplicate') === false) throw $e;
        usleep(120000);
      }
    }
    customer_seq_sync($code);
    try { audit_log('add', 'customer', $code, $row['name']); } catch (Throwable $t) {}
    return ['ok' => true, 'id' => $code, 'edited' => false];
  }
}
