<?php
// pages/user_mgmt.php — إضافة مستخدمين (موظف + دخول + صلاحيات)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $id = (int)($_POST['id'] ?? 0);
  $r = user_save([
    'username' => $_POST['u'] ?? '', 'pwd' => $_POST['p'] ?? '', 'emp' => $_POST['emp'] ?? 0,
  ], $id);
  $msg = !empty($r['ok']) ? ($id > 0 ? '✅ اتعدل' : '✅ اتضاف') : '❌ ' . ($r['error'] ?? 'خطأ');
}
if (($_POST['del'] ?? '') !== '' && (int)$_POST['id'] > 0) {
  $db->prepare("UPDATE users SET IS_Deleted=1 WHERE id=?")->execute([(int)$_POST['id']]);
  $msg = '🗑️ اتحذف';
}
$emps = $db->query("SELECT id, name FROM employees ORDER BY id")->fetchAll();
$rows = $db->query("SELECT u.id, u.emp, e.name empn, u.username, u.pwd FROM users u LEFT JOIN employees e ON e.id=u.emp WHERE u.IS_Deleted=0 OR u.IS_Deleted IS NULL ORDER BY u.id")->fetchAll();
$cur = isset($_GET['id']) ? $db->query("SELECT * FROM users WHERE id=" . (int)$_GET['id'])->fetch() : null;
layout_open('إضافة مستخدمين', '');
echo '<div class="card"><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">'
  . '<div><label>الموظف</label><br><select name="emp"><option value="0">—</option>';
foreach ($emps as $e) echo '<option value="' . $e['id'] . '"' . (($cur && $cur['emp'] == $e['id']) ? ' selected' : '') . '>' . e($e['name']) . '</option>';
echo '</select></div>'
  . '<div><label>اسم المستخدم</label><br><input name="u" value="' . e($cur['username'] ?? '') . '"></div>'
  . '<div><label>كلمة المرور' . (($cur['pwd'] ?? '') !== '' ? ' (محفوظة — اتركها فارغة للإبقاء)' : '') . '</label><br><input name="p" type="password" value="" autocomplete="new-password"></div>'
  . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
echo '<div class="card"><table><tr><th>الرقم</th><th>رقم الموظف</th><th>الموظف</th><th>اسم المستخدم</th><th>كلمة المرور</th><th></th></tr>';
foreach ($rows as $r) echo '<tr><td>' . $r['id'] . '</td><td>' . $r['emp'] . '</td><td>' . e($r['empn'] ?? '') . '</td><td>' . e($r['username']) . '</td><td>' . (($r['pwd'] ?? '') !== '' ? '🔒 محفوظة' : '⚠️ فارغة') . '</td><td><a href="?id=' . $r['id'] . '">فتح</a> | <a href="company.php?tab=perm&uid=' . $r['id'] . '">Edit Permissions</a> <form method="post" style="display:inline" onsubmit="return confirm(\'حذف؟\')"><input type="hidden" name="id" value="' . $r['id'] . '"><button name="del" value="1">🗑️</button></form></td></tr>';
echo '</table></div>';
layout_close();
