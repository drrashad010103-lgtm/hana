<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

if (!function_exists('user_next_code')) {
  function user_next_code(): int {
    return (int)db()->query("SELECT IFNULL(MAX(id),0)+1 FROM users")->fetchColumn();
  }

  function user_seq_sync(int $code): void {
    try {
      db()->prepare("INSERT INTO rs_seq (name,val) VALUES ('tbl_users_id',?) ON DUPLICATE KEY UPDATE val=GREATEST(val,VALUES(val))")
        ->execute([$code]);
    } catch (Throwable $t) {}
  }

  function user_mods_save(int $id, array $mods): void {
    $allowed = ['m_sale', 'm_buy', 'm_stock', 'm_reports', 'm_money', 'm_cust', 'm_admin'];
    $db = db();
    $db->prepare("DELETE FROM user_perms WHERE user_id=?")->execute([$id]);
    $mods = array_values(array_unique(array_filter($mods, fn($m) => in_array($m, $allowed, true))));
    if (!$mods) return;
    $st = $db->prepare("INSERT INTO user_perms (user_id, perm, val) VALUES (?,?,1)");
    foreach ($mods as $m) $st->execute([$id, $m]);
  }

  function user_save(array $in, int $id = 0): array {
    $db = db();
    $uname = array_key_exists('username', $in) ? mb_substr(trim((string)$in['username']), 0, 64) : null;
    if ($uname === '') return ['ok' => false, 'error' => 'اسم المستخدم مطلوب'];

    $dup = $db->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id<>?");
    if ($uname !== null) {
      $dup->execute([$uname, $id]);
      if ((int)$dup->fetchColumn() > 0) return ['ok' => false, 'error' => 'الاسم مستعمل'];
    }

    if ($id > 0) {
      $sets = [];
      $vals = [];
      if ($uname !== null) { $sets[] = 'username=?'; $vals[] = $uname; }
      if (array_key_exists('emp', $in)) { $sets[] = 'emp=?'; $vals[] = (int)$in['emp']; }
      foreach (['edit_inv', 'edit_disc', 'edit_price', 'edit_date'] as $k) {
        if (array_key_exists($k, $in)) { $sets[] = "$k=?"; $vals[] = !empty($in[$k]) ? 1 : 0; }
      }
      if (array_key_exists('off', $in)) { $sets[] = 'IS_Deleted=?'; $vals[] = !empty($in['off']) ? 1 : 0; }
      if (array_key_exists('pwd', $in)) {
        $pwd = trim((string)$in['pwd']);
        if ($pwd !== '') { $sets[] = 'pwd=?'; $vals[] = auth_hash_pwd($pwd); }
      }
      if ($sets) $db->prepare("UPDATE users SET " . implode(',', $sets) . " WHERE id=?")->execute([...$vals, $id]);
      if (array_key_exists('mods', $in) && is_array($in['mods'])) user_mods_save($id, $in['mods']);
      try { audit_log('edit', 'user', $id, implode(',', (array)($in['mods'] ?? []))); } catch (Throwable $t) {}
      return ['ok' => true, 'id' => $id, 'edited' => true];
    }

    $pwd = trim((string)($in['pwd'] ?? ''));
    if ($uname === null || $uname === '' || $pwd === '') return ['ok' => false, 'error' => 'اكتب الاسم والباسورد'];
    $row = [
      'username' => $uname,
      'pwd' => auth_hash_pwd($pwd),
      'emp' => (int)($in['emp'] ?? 0),
      'edit_inv' => array_key_exists('edit_inv', $in) ? (!empty($in['edit_inv']) ? 1 : 0) : 1,
      'edit_disc' => array_key_exists('edit_disc', $in) ? (!empty($in['edit_disc']) ? 1 : 0) : 1,
      'edit_price' => array_key_exists('edit_price', $in) ? (!empty($in['edit_price']) ? 1 : 0) : 1,
      'edit_date' => array_key_exists('edit_date', $in) ? (!empty($in['edit_date']) ? 1 : 0) : 1,
    ];

    $code = 0;
    for ($try = 1; $try <= 5; $try++) {
      $code = user_next_code();
      try {
        $db->prepare("INSERT INTO users (id, username, pwd, emp, edit_inv, edit_disc, edit_price, edit_date, IS_Deleted) VALUES (?,?,?,?,?,?,?,?,0)")
          ->execute([$code, ...array_values($row)]);
        break;
      } catch (Throwable $e) {
        if ($try === 5 || stripos($e->getMessage(), 'Duplicate') === false) throw $e;
        usleep(120000);
      }
    }
    user_seq_sync($code);
    if (array_key_exists('mods', $in) && is_array($in['mods'])) user_mods_save($code, $in['mods']);
    try { audit_log('add', 'user', $code, $uname); } catch (Throwable $t) {}
    return ['ok' => true, 'id' => $code, 'edited' => false];
  }
}
