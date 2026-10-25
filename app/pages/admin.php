<?php
// pages/admin.php — لوحة تحكم الباك إند (يوزرات + باسوردات + صلاحيات) — rs_own فقط
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
$mods_all = ['m_sale' => '🧾 بيع', 'm_buy' => '📦 شراء', 'm_stock' => '📊 مخزون', 'm_reports' => '📈 تقارير', 'm_money' => '💰 أموال', 'm_cust' => '👥 عملاء', 'm_admin' => '🛠️ إدارة'];
if (($_POST['add'] ?? '') !== '') {
  $r = user_save(['username' => $_POST['u'] ?? '', 'pwd' => $_POST['p'] ?? '']);
  $msg = !empty($r['ok']) ? '✅ اتضاف ' . mb_substr(trim($_POST['u'] ?? ''), 0, 64) : '❌ ' . ($r['error'] ?? 'خطأ');
}
if (isset($_POST['save'])) {
  foreach ($_POST['uids'] ?? [] as $id) {
    $id = (int)$id;
    if ($id === 1) continue; // admin الرئيسي لا يُمس
    $mods = [];
    foreach (array_keys($mods_all) as $m) if (!empty($_POST['pm'][$id][$m])) $mods[] = $m;
    $r = user_save([
      'edit_inv' => !empty($_POST['ei'][$id]), 'edit_disc' => !empty($_POST['ed'][$id]),
      'edit_price' => !empty($_POST['ep'][$id]), 'edit_date' => !empty($_POST['edt'][$id]),
      'off' => !empty($_POST['off'][$id]), 'pwd' => $_POST['pwn'][$id] ?? '', 'mods' => $mods,
    ], $id);
    if (empty($r['ok'])) { $msg = '❌ ' . ($r['error'] ?? 'خطأ'); break; }
  }
  if ($msg === '') $msg = '✅ اتحفظ الكل';
}
if (($_POST['reset'] ?? '') !== '' && ($_SESSION['u'] ?? '') === 'admin') {
  $id = (int)$_POST['reset'];
  if ($id > 1) { $db->prepare("DELETE FROM user_perms WHERE user_id=?")->execute([$id]); $msg = '✅ اتمسحت الصلاحيات — المستخدم دلوقتي بلا وصول'; }
}
$rows = $db->query("SELECT id, username, edit_inv, edit_disc, edit_price, edit_date, IS_Deleted FROM users ORDER BY id")->fetchAll();
$pm = [];
foreach ($db->query("SELECT user_id, perm FROM user_perms WHERE val=1")->fetchAll() as $r) $pm[(int)$r['user_id']][$r['perm']] = 1;
layout_open('لوحة التحكم — الباك إند', '');
echo '<div class="card"><h3>➕ مستخدم جديد</h3><form method="post" class="row"><div><label>الاسم</label><br><input name="u"></div><div><label>الباسورد</label><br><input name="p"></div><div><br><button name="add" value="1">إضافة (حدد الصلاحيات)</button> <span>' . e($msg) . '</span></div></form>'
  . '<div class="note">الجديد بيبدأ بلا وصول — فعّل له الصلاحيات من الجدول تحت. المالك <b>admin</b> دائماً كامل ولا يُعدل. الباسوردات بتتخزن مشفرة bcrypt.</div></div>';
echo '<div class="card"><form method="post"><table><tr><th>م</th><th>المستخدم</th><th>تعديل<br>فواتير</th><th>خصم</th><th>سعر</th><th>تاريخ</th><th>موقوف</th><th>باسورد جديدة</th>';
foreach ($mods_all as $m) echo '<th>' . $m . '</th>';
echo '<th></th></tr>';
foreach ($rows as $r) {
  $i = (int)$r['id']; $lock = $i === 1 ? ' disabled' : '';
  echo '<tr><td>' . $i . '<input type="hidden" name="uids[]" value="' . $i . '"></td><td><b>' . e($r['username']) . '</b>' . ($r['IS_Deleted'] ? ' <span style="color:red">(موقوف)</span>' : '') . '</td>'
    . '<td><input type="checkbox" name="ei[' . $i . ']"' . ($r['edit_inv'] ? ' checked' : '') . $lock . '></td>'
    . '<td><input type="checkbox" name="ed[' . $i . ']"' . ($r['edit_disc'] ? ' checked' : '') . $lock . '></td>'
    . '<td><input type="checkbox" name="ep[' . $i . ']"' . ($r['edit_price'] ? ' checked' : '') . $lock . '></td>'
    . '<td><input type="checkbox" name="edt[' . $i . ']"' . ($r['edit_date'] ? ' checked' : '') . $lock . '></td>'
    . '<td><input type="checkbox" name="off[' . $i . ']"' . ($r['IS_Deleted'] ? ' checked' : '') . $lock . '></td>'
    . '<td><input name="pwn[' . $i . ']" style="width:90px" placeholder="جديدة"' . $lock . '></td>';
  foreach (array_keys($mods_all) as $m) {
    $on = !empty($pm[$i][$m]); // بلا صفوف = لا وصول
    echo '<td><input type="checkbox" name="pm[' . $i . '][' . $m . ']"' . ($on ? ' checked' : '') . $lock . '></td>';
  }
  echo '<td>' . ($i > 1 && ($_SESSION['u'] ?? '') === 'admin'
    ? '<form method="post" style="margin:0" onsubmit="return confirm(\'مسح كل صلاحيات هذا المستخدم؟\')"><input type="hidden" name="reset" value="' . $i . '"><button type="submit">مسح الصلاحيات</button></form>'
    : '') . '</td></tr>';
}
echo '</table><br><button name="save" value="1">💾 حفظ الكل</button> <span>' . e($msg) . '</span></form>'
  . '<div class="note">علامة صح = مسموح. لو شلت كل صح من مستخدم يبقى بلا وصول. صلاحيات الحقول (خصم/سعر/تاريخ) تُحفظ وتُطبق في شاشات البيع تباعاً.</div></div>';
layout_close();
