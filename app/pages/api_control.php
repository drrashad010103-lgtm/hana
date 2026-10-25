<?php
// pages/api_control.php — عقل لوحة التحكم الشاملة (تبويب واحد): قاعدة/أصناف/عملاء/دفع/ولاء/يوزرات
// الصلاحية: m_admin (خريطة cfg) — والتبديل/الإنشاء/الحذف الخطر حصراً للمالك admin
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/audit.php';
require_once __DIR__ . '/../lib/customers.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/loyalty.php';
if (empty($_SESSION['u'])) { echo json_encode(['ok' => false, 'error' => 'غير مسجل دخول']); exit; }
$db = db();
$__admin = cfg_is_admin();
$__uid = (int)($_SESSION['uid'] ?? 0);
if (!$__admin) {
  $__permOk = false; $__nRows = 0;
  try {
    $st = $db->prepare("SELECT perm, val FROM user_perms WHERE user_id=?");
    $st->execute([$__uid]);
    foreach ($st->fetchAll() as $r) { $__nRows++; if ((int)$r['val'] === 1 && $r['perm'] === 'm_admin') $__permOk = true; }
  } catch (Throwable $t) {}
  if ($__nRows === 0) $__permOk = false; // بلا صفوف = لا وصول
  if (!$__permOk) { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'لا صلاحية إدارة'], JSON_UNESCAPED_UNICODE); exit; }
}
$act = $_GET['act'] ?? $_POST['act'] ?? '';
$body = [];
try { $body = json_decode(file_get_contents('php://input'), true) ?: []; } catch (Throwable $t) { $body = []; }
$g = function (string $k, $d = null) use ($body) { return $_POST[$k] ?? $body[$k] ?? $d; };
$out = function (array $a = []) { echo json_encode(['ok' => true] + $a, JSON_UNESCAPED_UNICODE); exit; };
$bad = function (string $m, int $code = 400) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $m], JSON_UNESCAPED_UNICODE); exit; };
$needAdmin = function () use ($__admin) { if (!$__admin) $bad('العملية دي للمالك (admin) فقط', 403); };
$dbnCheck = function (string $n) use ($db): string {
  $n = strtolower(trim($n));
  if (!preg_match('/^rs_[a-z0-9_]{1,40}$/', $n)) $bad('اسم القاعدة لازم يبدأ rs_ وينتهي بحروف/أرقام');
  if ($n === 'rs_rs') $bad('قاعدة الأصل الأصلي RS_RS محرومة — دي SQL Server مش MySQL');
  if (in_array($n, ['information_schema', 'mysql', 'performance_schema', 'sys'], true)) $bad('اسم محظور');
  return $n;
};
$dbExists = function (string $n) use ($db): bool {
  foreach ($db->query('SHOW DATABASES') as $r) if (strcasecmp((string)reset($r), $n) === 0) return true;
  return false;
};
$curDb = function () { $c = require __DIR__ . '/../config.php'; return (string)$c['db']['name']; };

// ============ قاعدة البيانات ============
if ($act === 'db_list') {
  $cur = $curDb();
  $rows = [];
  foreach ($db->query('SHOW DATABASES') as $r) {
    $n = (string)reset($r);
    if (!preg_match('/^rs_[a-z0-9_]+$/i', $n)) continue;
    if (strcasecmp($n, 'rs_rs') === 0) continue;
    $inv = -1;
    try { $inv = (int)$db->query("SELECT COUNT(*) FROM `" . $n . "`.`inv`")->fetchColumn(); } catch (Throwable $t) {}
    $rows[] = ['name' => $n, 'inv' => $inv, 'cur' => strcasecmp($n, $cur) === 0];
  }
  usort($rows, fn($a, $b) => strcmp($a['name'], $b['name']));
  $out(['cur' => $cur, 'rows' => $rows]);
}
if ($act === 'db_use') {
  $needAdmin();
  $n = $dbnCheck((string)$g('name', ''));
  if (!$dbExists($n)) $bad('القاعدة مش موجودة');
  if (strcasecmp($n, $curDb()) === 0) $bad('هي القاعدة الحالية أصلاً');
  global $db; // فحص فواتير معلقة في الحالية قبل المغادرة
  $tmp = (int)$db->query("SELECT COUNT(*) FROM inv WHERE branch=99 AND IFNULL(IS_Temp,0)=1 AND IFNULL(IS_Deleted,0)=0")->fetchColumn();
  $msg = $tmp > 0 ? "⚠️ عنك $tmp فاتورة معلقة في القاعدة الحالية — اتنقلت بياناتها مش معاك" : '';
  file_put_contents(__DIR__ . '/../../db.name', $n);
  audit_log('db_switch', 'db', 0, 'to=' . $n);
  $out(['name' => $n, 'note' => $msg]);
}
if ($act === 'db_create') {
  $needAdmin();
  $n = $dbnCheck((string)$g('name', ''));
  if ($dbExists($n)) $bad('الاسم مستعمل — اختار اسم تاني');
  $cur = $curDb();
  $cfg = require __DIR__ . '/../config.php';
  $d = $cfg['db'];
  $c2 = new PDO("mysql:host={$d['host']};charset={$d['charset']}", $d['user'], $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $c2->exec("CREATE DATABASE IF NOT EXISTS `" . $n . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $tables = [];
  foreach ($db->query("SHOW TABLES FROM `$cur`") as $r) $tables[] = (string)reset($r);
  $seed = ['users', 'companies', 'groups', 'maingroups', 'units', 'curr_units', 'banks', 'app_settings', 'user_perms'];
  $full = (int)$g('full', 0) === 1;
  if ($full) $seed = array_merge($seed, ['currencies', 'customers', 'pay_methods']);
  $copied = [];
  foreach ($tables as $t) {
    $cr = $db->query("SHOW CREATE TABLE `$cur`.`$t`")->fetch(PDO::FETCH_NUM);
    $c2->exec("USE `" . $n . "`");
    $c2->exec($cr[1]);
    if (in_array($t, $seed, true)) {
      $cols = [];
      foreach ($db->query("SHOW COLUMNS FROM `$cur`.`$t`") as $cw) $cols[] = $cw['Field'];
      $ph = implode(',', array_fill(0, count($cols), '?'));
      $ins = $c2->prepare("INSERT INTO `$n`.`$t` (`" . implode('`,`', $cols) . "`) VALUES ($ph)");
      $w = in_array($t, ['currencies', 'customers'], true) ? ' WHERE IFNULL(IS_Deleted,0)=0' : '';
      foreach ($db->query("SELECT * FROM `$cur`.`$t`" . $w) as $row) {
        $vals = [];
        foreach ($cols as $cn) $vals[] = $row[$cn];
        $ins->execute($vals);
      }
      $copied[] = $t;
    }
  }
  if ($full && in_array('currencies', $copied, true) && in_array('item_bal', $tables, true)) {
    $c2->exec("INSERT IGNORE INTO `" . $n . "`.`item_bal` (id, bal, updated) SELECT id, 0, NOW() FROM `" . $n . "`.`currencies`");
  }
  audit_log('db_create', 'db', 0, 'name=' . $n . ($full ? ' full=1' : ''));
  $out(['name' => $n, 'tables' => count($tables), 'copied' => $copied, 'note' => $full ? 'الهيكل + التعريفات + الأصناف والعملاء الفعليين وطرق الدفع اتنقلت — الفواتير والأرصدة والولاء يبدؤوا صفر' : 'الهيكل اتكمل + التعريفات (يوزرات/شركة/مجموعات/وحدات/بنوك) اتنقلت — الفواتير والأصناف فاضية']);
}

// ============ طرق الدفع ============
if ($act === 'pay_list') { $out(['rows' => cfg_pay_methods(true)]); }
if ($act === 'pay_add') {
  $needAdmin();
  $nm = mb_substr(trim((string)$g('name', '')), 0, 64);
  if ($nm === '') $bad('اسم الطريقة مطلوب');
  $mx = (int)$db->query("SELECT IFNULL(MAX(id),4)+1 FROM pay_methods")->fetchColumn();
  $db->prepare("INSERT INTO pay_methods (id, name, active) VALUES (?,?,1)")->execute([$mx, $nm]);
  $out(['id' => $mx]);
}
if ($act === 'pay_save') {
  $needAdmin();
  $id = (int)$g('id', 0);
  $nm = mb_substr(trim((string)$g('name', '')), 0, 64);
  $active = !empty($g('active')) ? 1 : 0;
  if ($id <= 0 || $nm === '') $bad('بيانات ناقصة');
  if ($id === 1 && !$active) $bad('طريقة الكاش لا تُعطل — هي الأساس');
  $db->prepare("UPDATE pay_methods SET name=?, active=? WHERE id=?")->execute([$nm, $active, $id]);
  $out();
}
if ($act === 'pay_del') {
  $needAdmin();
  $id = (int)$g('id', 0);
  if ($id <= 0) $bad('بيانات ناقصة');
  if ($id === 1) $bad('الكاش لا يُحذف');
  $n = (int)$db->query("SELECT COUNT(*) FROM inv WHERE pay_type=$id")->fetchColumn();
  if ($n > 0) $bad("في $n فاتورة مستعملاها — اتعطلها بدل الحذف");
  $db->prepare("DELETE FROM pay_methods WHERE id=?")->execute([$id]);
  audit_log('delete', 'pay_method', $id);
  $out();
}

// ============ العملاء ============
if ($act === 'cust_list') {
  $q = trim((string)$g('q', ''));
  if ($q === '') { $rows = $db->query("SELECT id, name, mobile, act, notes, discount_balance, maxdepit FROM customers WHERE IFNULL(IS_Deleted,0)=0 ORDER BY id DESC LIMIT 30")->fetchAll(); }
  else {
    $like = '%' . str_replace(['%', '_'], '', mb_substr($q, 0, 64)) . '%';
    $st = $db->prepare("SELECT id, name, mobile, act, notes, discount_balance, maxdepit FROM customers WHERE IFNULL(IS_Deleted,0)=0 AND (name LIKE ? OR mobile LIKE ? OR tel LIKE ? OR CAST(id AS CHAR)=?) ORDER BY id DESC LIMIT 30");
    $st->execute([$like, $like, $like, $q]);
    $rows = $st->fetchAll();
  }
  $out(['rows' => $rows]);
}
if ($act === 'cust_get') {
  $id = (int)$g('id', 0);
  $st = $db->prepare("SELECT id, name, mobile, act, notes, discount_balance, maxdepit FROM customers WHERE id=? AND IFNULL(IS_Deleted,0)=0");
  $st->execute([$id]);
  $out(['row' => $st->fetch() ?: null]);
}
if ($act === 'cust_save') {
  $id = (int)$g('id', 0);
  $in = [
    'name' => $g('name', ''),
    'mobile' => $g('mobile', ''),
    'act' => (int)$g('act', 1),
    'notes' => $g('notes', ''),
    'discount_balance' => max(0, (float)$g('discount_balance', 0)),
    'maxdepit' => max(0, (float)$g('maxdepit', 0)),
  ];
  $r = customer_save($in, $id);
  if (empty($r['ok'])) $bad($r['error'] ?? 'خطأ');
  $out(['id' => $r['id'], 'edited' => !empty($r['edited'])]);
}
if ($act === 'cust_del') {
  $needAdmin();
  $id = (int)$g('id', 0);
  if ($id <= 0) $bad('بيانات ناقصة');
  $n = (int)$db->query("SELECT COUNT(*) FROM inv WHERE cust_id=$id AND IFNULL(IS_Deleted,0)=0")->fetchColumn();
  if ($n > 0) $bad("له $n فاتورة — لا يُحذف، اعدّله بدلاً من ده");
  $db->prepare("UPDATE customers SET IS_Deleted=1 WHERE id=?")->execute([$id]);
  audit_log('delete', 'customer', $id);
  $out();
}

// ============ نقاط الولاء ============
if ($act === 'loy_rules') {
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $g('earn') !== null && $g('cash') !== null) {
    $needAdmin();
    $earn = max(0, (float)$g('earn', 1));
    $cash = max(0.0001, (float)$g('cash', 0.05));
    if ($earn > 100) $earn = 100;
    if ($cash > 100) $cash = 100;
    cfg_set('loy_earn', rtrim(rtrim((string)$earn, '0'), '.'));
    cfg_set('loy_cash_rate', rtrim(rtrim((string)$cash, '0'), '.'));
    audit_log('edit', 'loy_rules', 0, "earn=$earn cash=$cash");
  }
  $out(['earn' => cfg_get('loy_earn', '1') ?: '1', 'cash' => cfg_get('loy_cash_rate', '0.05') ?: '0.05']);
}
if ($act === 'loy_list') {
  $q = trim((string)$g('q', ''));
  if ($q !== '') {
    $like = '%' . str_replace(['%', '_'], '', mb_substr($q, 0, 64)) . '%';
    $st = $db->prepare("SELECT l.cust_id, l.points, l.total_earned, l.total_redeemed, c.name FROM loyalty l JOIN customers c ON c.id=l.cust_id WHERE c.name LIKE ? OR CAST(l.cust_id AS CHAR)=? ORDER BY l.points DESC LIMIT 50");
    $st->execute([$like, $q]);
    $rows = $st->fetchAll();
  } else $rows = $db->query("SELECT l.cust_id, l.points, l.total_earned, l.total_redeemed, c.name FROM loyalty l JOIN customers c ON c.id=l.cust_id ORDER BY l.points DESC LIMIT 50")->fetchAll();
  $logs = $db->query("SELECT lg.cust_id, lg.points, lg.type, lg.inv_id, lg.note, lg.created_at, c.name FROM loyalty_log lg JOIN customers c ON c.id=lg.cust_id ORDER BY lg.id DESC LIMIT 15")->fetchAll();
  $out(['rows' => $rows, 'logs' => $logs]);
}
if ($act === 'loy_adj') {
  $cust = (int)$g('cust', 0);
  $pts = (int)$g('pts', 0);
  $note = mb_substr(trim((string)$g('note', '')), 0, 128);
  if ($cust <= 0 || $pts === 0) $bad('كود عميل + نقاط مطلوبة');
  $ex = $db->prepare("SELECT COUNT(*) FROM customers WHERE id=? AND IFNULL(IS_Deleted,0)=0");
  $ex->execute([$cust]);
  if (!(int)$ex->fetchColumn()) $bad('العميل غير موجود');
  loyalty_adjust($cust, $pts, $note ?: 'تعديل يدوي — لوحة التحكم');
  audit_log('edit', 'loyalty', $cust, "pts=$pts");
  $out();
}
if ($act === 'loy_enroll') {
  $needAdmin();
  $r = loyalty_enroll_all();
  audit_log('edit', 'loyalty', 0, 'enroll_all total=' . $r['total']);
  $out(['total' => $r['total'], 'dupes' => $r['dupes'], 'note' => 'كل العملاء بقت عضوية في البرنامج — اللي كان عنده نقاط نقاطه زي ما هي والباقي بيبدؤوا صفر']);
}
if ($act === 'loy_zero') {
  $needAdmin();
  $cust = (int)$g('cust', 0);
  if ($cust <= 0) $bad('كود عميل مطلوب');
  $old = loyalty_zero($cust, 'تصفير نقاط — لوحة التحكم');
  audit_log('edit', 'loyalty', $cust, "zero ($old)");
  $out(['old' => $old]);
}

// ============ المستخدمون والصلاحيات ============
$mods = ['m_sale', 'm_buy', 'm_stock', 'm_reports', 'm_money', 'm_cust', 'm_admin'];
if ($act === 'usr_list') {
  $rows = $db->query("SELECT id, username, edit_inv, edit_disc, edit_price, edit_date, IS_Deleted FROM users ORDER BY id")->fetchAll();
  $pm = [];
  foreach ($db->query("SELECT user_id, perm FROM user_perms WHERE val=1")->fetchAll() as $r) $pm[(int)$r['user_id']][] = $r['perm'];
  foreach ($rows as &$rw) { $rw['mods'] = $pm[(int)$rw['id']] ?? []; $rw['modn'] = ((int)$rw['id'] === 1) ? 7 : 0; }
  $out(['rows' => $rows, 'mods' => $mods]);
}
if ($act === 'usr_add') {
  $needAdmin();
  $r = user_save(['username' => $g('u', ''), 'pwd' => $g('p', '')]);
  if (empty($r['ok'])) $bad($r['error'] ?? 'خطأ');
  $out(['id' => $r['id']]);
}
if ($act === 'usr_save') {
  $needAdmin();
  $id = (int)$g('id', 0);
  if ($id === 1) $bad('المالك admin لا يُعدل');
  if ($id <= 0) $bad('بيانات ناقصة');
  $r = user_save([
    'edit_inv' => !empty($g('ei')), 'edit_disc' => !empty($g('ed')),
    'edit_price' => !empty($g('ep')), 'edit_date' => !empty($g('edt')),
    'off' => !empty($g('off')), 'pwd' => $g('pwn', ''),
    'mods' => (array)($g('mods') ?? []),
  ], $id);
  if (empty($r['ok'])) $bad($r['error'] ?? 'خطأ');
  $out();
}
if ($act === 'usr_reset') {
  $needAdmin();
  $id = (int)$g('id', 0);
  if ($id <= 1) $bad('بيانات ناقصة');
  $db->prepare("DELETE FROM user_perms WHERE user_id=?")->execute([$id]);
  $out();
}

$bad('أمر غير معروف');
