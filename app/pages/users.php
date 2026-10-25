<?php
// pages/users.php — المستخدمون والصلاحيات (نسخة حرة)
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['add'] ?? '') !== '') {
  $r = user_save(['username' => $_POST['u'] ?? '', 'pwd' => $_POST['p'] ?? '']);
  $msg = !empty($r['ok']) ? '✅ اتضاف ' . mb_substr(trim($_POST['u'] ?? ''), 0, 64) : '❌ ' . ($r['error'] ?? 'اكتب الاسم والباسورد');
}
if (isset($_POST['perm'])) {
  foreach ($_POST['uids'] ?? [] as $id) {
    $id = (int)$id;
    $r = user_save([
      'edit_inv' => !empty($_POST['ei'][$id]), 'edit_disc' => !empty($_POST['ed'][$id]),
      'edit_price' => !empty($_POST['ep'][$id]), 'edit_date' => !empty($_POST['edt'][$id]),
      'pwd' => $_POST['pwn'][$id] ?? '',
    ], $id);
    if (empty($r['ok'])) { $msg = '❌ ' . ($r['error'] ?? 'خطأ'); break; }
  }
  if ($msg === '') $msg = '✅ اتحفظت الصلاحيات';
}
$rows = $db->query("SELECT id, username, edit_inv, edit_disc, edit_price, edit_date FROM users WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY id")->fetchAll();
layout_open('المستخدمون', '');
echo '<div class="card"><h3>➕ إضافة مستخدم</h3><form method="post" class="row"><div><label>الاسم</label><br><input name="u"></div><div><label>كلمة المرور</label><br><input name="p"></div><div><br><button name="add" value="1">إضافة</button> <span>' . $msg . '</span></div></form></div>';
echo '<div class="card"><form method="post"><table><tr><th>م</th><th>المستخدم</th><th>تعديل فواتير</th><th>خصم</th><th>سعر</th><th>تاريخ</th><th>كلمة مرور جديدة</th></tr>';
foreach ($rows as $r) {
  $i = $r['id'];
  echo '<tr><td>' . $i . '<input type="hidden" name="uids[]" value="' . $i . '"></td><td><b>' . e($r['username']) . '</b></td>'
    . '<td><input type="checkbox" name="ei[' . $i . ']" ' . ($r['edit_inv'] ? 'checked' : '') . '></td>'
    . '<td><input type="checkbox" name="ed[' . $i . ']" ' . ($r['edit_disc'] ? 'checked' : '') . '></td>'
    . '<td><input type="checkbox" name="ep[' . $i . ']" ' . ($r['edit_price'] ? 'checked' : '') . '></td>'
    . '<td><input type="checkbox" name="edt[' . $i . ']" ' . ($r['edit_date'] ? 'checked' : '') . '></td>'
    . '<td><input name="pwn[' . $i . ']" style="width:100px" placeholder="جديدة"></td></tr>';
}
echo '</table><button name="perm" value="1">💾 حفظ الصلاحيات</button> <span>' . $msg . '</span></form>';
echo '<div class="note">تغيير كلمة المرور: اكتب الجديدة في خانة المستخدم ودوس حفظ الصلاحيات — تُحفظ مشفّرة (bcrypt) ولا تظهر مرة أخرى.</div></div>';
layout_close();
