<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

if (!function_exists('company_next_code')) {
  function company_next_code(): int {
    return (int)db()->query("SELECT IFNULL(MAX(id),0)+1 FROM companies")->fetchColumn();
  }

  function company_save(array $in, int $id = 0): array {
    $db = db();
    $norm = [
      'name' => fn($v) => mb_substr(trim((string)$v), 0, 190),
      'name_en' => fn($v) => mb_substr(trim((string)$v), 0, 128),
      'activity' => fn($v) => mb_substr(trim((string)$v), 0, 128),
      'license_no' => fn($v) => mb_substr(trim((string)$v), 0, 64),
      'comm_reg' => fn($v) => mb_substr(trim((string)$v), 0, 64),
      'Tel' => fn($v) => mb_substr(trim((string)$v), 0, 60),
      'Mobile' => fn($v) => mb_substr(trim((string)$v), 0, 60),
      'Fax' => fn($v) => mb_substr(trim((string)$v), 0, 32),
      'Email' => fn($v) => mb_substr(trim((string)$v), 0, 128),
      'website' => fn($v) => mb_substr(trim((string)$v), 0, 128),
      'tax_no' => fn($v) => mb_substr(trim((string)$v), 0, 64),
      'tax_pct' => fn($v) => (float)$v,
      'Address' => fn($v) => mb_substr(trim((string)$v), 0, 255),
      'notes' => fn($v) => mb_substr(trim((string)$v), 0, 255),
    ];

    if ($id <= 0) {
      $first = $db->query("SELECT id FROM companies ORDER BY id LIMIT 1")->fetchColumn();
      $id = $first !== false ? (int)$first : 0;
    }

    if ($id > 0) {
      $sets = [];
      $vals = [];
      foreach ($norm as $k => $fn) {
        if (!array_key_exists($k, $in)) continue;
        $v = $fn($in[$k]);
        if ($k === 'name' && $v === '') return ['ok' => false, 'error' => 'اسم الشركة مطلوب'];
        $sets[] = "`$k`=?";
        $vals[] = $v;
      }
      if (!$sets) return ['ok' => true, 'id' => $id, 'created' => false];
      $db->prepare("UPDATE companies SET " . implode(',', $sets) . " WHERE id=?")->execute([...$vals, $id]);
      try { audit_log('edit', 'company', $id); } catch (Throwable $t) {}
      return ['ok' => true, 'id' => $id, 'created' => false];
    }

    $defaults = array_fill_keys(array_keys($norm), '');
    $defaults['tax_pct'] = 0;
    foreach ($norm as $k => $fn) {
      if (array_key_exists($k, $in)) $defaults[$k] = $fn($in[$k]);
    }
    if ($defaults['name'] === '') return ['ok' => false, 'error' => 'اسم الشركة مطلوب'];

    $cols = array_keys($norm);
    $code = 0;
    for ($try = 1; $try <= 5; $try++) {
      $code = company_next_code();
      try {
        $db->prepare("INSERT INTO companies (id," . implode(',', $cols) . ",IS_Deleted) VALUES (?" . str_repeat(',?', count($cols)) . ",0)")
          ->execute([$code, ...array_values($defaults)]);
        break;
      } catch (Throwable $e) {
        if ($try === 5 || stripos($e->getMessage(), 'Duplicate') === false) throw $e;
        usleep(120000);
      }
    }
    try {
      $db->prepare("INSERT INTO rs_seq (name,val) VALUES ('tbl_companies_id',?) ON DUPLICATE KEY UPDATE val=GREATEST(val,VALUES(val))")->execute([$code]);
    } catch (Throwable $t) {}
    try { audit_log('add', 'company', $code, $defaults['name']); } catch (Throwable $t) {}
    return ['ok' => true, 'id' => $code, 'created' => true];
  }
}
