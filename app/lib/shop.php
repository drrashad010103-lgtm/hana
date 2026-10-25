<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

if (!function_exists('shop_next_code')) {
  function shop_next_code(string $table): int {
    if (!in_array($table, ['coboun', 'voucher'], true)) throw new InvalidArgumentException('table');
    return (int)db()->query("SELECT IFNULL(MAX(id),0)+1 FROM `$table`")->fetchColumn();
  }

  function cust_group_add(string $name): array {
    $name = mb_substr(trim($name), 0, 128);
    if ($name === '') return ['ok' => false, 'error' => 'اسم المجموعة مطلوب'];
    $db = db();
    $db->prepare("INSERT INTO cust_groups (name) VALUES (?)")->execute([$name]);
    $id = (int)$db->lastInsertId();
    try { audit_log('add', 'cust_group', $id, $name); } catch (Throwable $t) {}
    return ['ok' => true, 'id' => $id];
  }

  function cust_group_delete(int $id): void {
    if ($id <= 0) return;
    db()->prepare("DELETE FROM cust_groups WHERE id=?")->execute([$id]);
    try { audit_log('delete', 'cust_group', $id); } catch (Throwable $t) {}
  }

  function coupon_save(array $in, int $id = 0): array {
    $db = db();
    $norm = [
      'code_name' => fn($v) => mb_substr(trim((string)$v), 0, 64),
      'discount' => fn($v) => (int)$v,
      'date_start' => fn($v) => ((string)$v) !== '' ? (string)$v : null,
      'date_end' => fn($v) => ((string)$v) !== '' ? (string)$v : null,
      'active' => fn($v) => !empty($v) ? 1 : 0,
      'for_clients' => fn($v) => (int)$v,
      'clients_group' => fn($v) => (int)$v,
    ];
    if ($id > 0) {
      $sets = [];
      $vals = [];
      foreach ($norm as $k => $fn) {
        if (!array_key_exists($k, $in)) continue;
        $v = $fn($in[$k]);
        if ($k === 'code_name' && $v === '') return ['ok' => false, 'error' => 'كود الكوبون مطلوب'];
        $sets[] = "`$k`=?";
        $vals[] = $v;
      }
      if (!$sets) return ['ok' => true, 'id' => $id, 'edited' => true];
      $db->prepare("UPDATE coboun SET " . implode(',', $sets) . " WHERE id=?")->execute([...$vals, $id]);
      try { audit_log('edit', 'coupon', $id); } catch (Throwable $t) {}
      return ['ok' => true, 'id' => $id, 'edited' => true];
    }
    $row = ['code_name' => '', 'discount' => 0, 'date_start' => null, 'date_end' => null, 'active' => 0, 'for_clients' => 0, 'clients_group' => 0];
    foreach ($norm as $k => $fn) {
      if (array_key_exists($k, $in)) $row[$k] = $fn($in[$k]);
    }
    if ($row['code_name'] === '') return ['ok' => false, 'error' => 'كود الكوبون مطلوب'];
    $cid = shop_next_code('coboun');
    $db->prepare("INSERT INTO coboun (id, code_name, discount, date_start, date_end, active, for_clients, clients_group, Is_deleted) VALUES (?,?,?,?,?,?,?,?,0)")
      ->execute([$cid, ...array_values($row)]);
    try { audit_log('add', 'coupon', $cid, $row['code_name']); } catch (Throwable $t) {}
    return ['ok' => true, 'id' => $cid, 'edited' => false];
  }

  function voucher_delete(int $id): void {
    if ($id <= 0) return;
    db()->prepare("UPDATE voucher SET is_deleted=1, user_deleted=1, date_deleted=NOW() WHERE id=?")->execute([$id]);
    try { audit_log('delete', 'voucher', $id); } catch (Throwable $t) {}
  }

  function voucher_save(array $in, int $id = 0): array {
    $db = db();
    $norm = [
      'name' => fn($v) => mb_substr(trim((string)$v), 0, 128),
      'code' => fn($v) => mb_substr(trim((string)$v), 0, 64),
      'value' => fn($v) => (int)$v,
      'date_create' => fn($v) => ((string)$v) !== '' ? (string)$v : date('Y-m-d H:i:s'),
      'date_end' => fn($v) => ((string)$v) !== '' ? (string)$v : null,
      'vaild' => fn($v) => !empty($v) ? 1 : 0,
    ];
    if ($id > 0) {
      $sets = [];
      $vals = [];
      foreach ($norm as $k => $fn) {
        if (!array_key_exists($k, $in)) continue;
        $v = $fn($in[$k]);
        if (($k === 'name' || $k === 'code') && $v === '') return ['ok' => false, 'error' => 'الاسم والكود مطلوبين'];
        $sets[] = "`$k`=?";
        $vals[] = $v;
      }
      if (!$sets) return ['ok' => true, 'id' => $id, 'edited' => true];
      $db->prepare("UPDATE voucher SET " . implode(',', $sets) . " WHERE id=?")->execute([...$vals, $id]);
      try { audit_log('edit', 'voucher', $id); } catch (Throwable $t) {}
      return ['ok' => true, 'id' => $id, 'edited' => true];
    }
    $row = ['name' => '', 'code' => '', 'value' => 0, 'date_create' => date('Y-m-d H:i:s'), 'date_end' => null, 'vaild' => 0];
    foreach ($norm as $k => $fn) {
      if (array_key_exists($k, $in)) $row[$k] = $fn($in[$k]);
    }
    if ($row['name'] === '' || $row['code'] === '') return ['ok' => false, 'error' => 'الاسم والكود مطلوبين'];
    $vid = shop_next_code('voucher');
    $db->prepare("INSERT INTO voucher (id, name, code, value, date_create, date_end, vaild, used_count, is_deleted) VALUES (?,?,?,?,?,?,?,0,0)")
      ->execute([$vid, ...array_values($row)]);
    try { audit_log('add', 'voucher', $vid, $row['code']); } catch (Throwable $t) {}
    return ['ok' => true, 'id' => $vid, 'edited' => false];
  }
}
