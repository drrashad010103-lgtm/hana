<?php
// pages/shifts.php — الورديات وفتح/قفل اليوم (فرع 99 = ورديات النسخة)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['op'] ?? '') === 'open') {
  $mx = $db->query("SELECT IFNULL(MAX(wardya_no),0)+1 n FROM wardya")->fetch()['n'];
  $db->prepare("INSERT INTO wardya (wardya_no, date_open, user_open, IS_open, branch) VALUES (?,?,1,1,99)")->execute([$mx, date('Y-m-d H:i:s')]);
  $msg = '✅ اتفتحت وردية ' . $mx;
} elseif (($_POST['op'] ?? '') === 'close') {
  $id = (int)($_POST['id'] ?? 0);
  $db->prepare("UPDATE wardya SET date_close=NOW(), IS_open=0 WHERE wardya_no=? AND branch=99 AND IS_open=1")->execute([$id]);
  $msg = '🔒 اتقفلت وردية ' . $id;
} elseif (($_POST['op'] ?? '') === 'closeday') {
  $db->exec("UPDATE wardya SET date_close=NOW(), IS_open=0 WHERE branch=99 AND IS_open=1");
  $msg = '🔒 اتقفل اليوم (كل ورديات النسخة المفتوحة)';
}
$open = $db->query("SELECT wardya_no, date_open FROM wardya WHERE branch=99 AND IS_open=1 ORDER BY wardya_no DESC")->fetchAll();
$hist = $db->query("SELECT wardya_no, date_open, date_close FROM wardya WHERE branch=99 ORDER BY wardya_no DESC LIMIT 20")->fetchAll();
layout_open('الورديات واليوم', '');
echo '<div class="card"><h3>🟢 الورديات المفتوحة (النسخة): ' . count($open) . '</h3>'
  . '<form method="post" class="row"><button name="op" value="open">🟢 بدء يوم / فتح وردية</button> <button name="op" value="closeday" onclick="return confirm(\'قفل كل الورديات المفتوحة؟\')">🔴 إنهاء اليوم</button> <span>' . $msg . '</span></form></div>';
echo '<div class="card"><table><tr><th>وردية</th><th>فتح</th><th>قفل</th><th></th></tr>';
foreach ($hist as $r) echo '<tr><td>' . $r['wardya_no'] . '</td><td>' . e($r['date_open'] ?? '') . '</td><td>' . e($r['date_close'] ?? 'مفتوحة') . '</td><td>' . ($r['date_close'] ? '' : '<form method="post"><input type="hidden" name="id" value="' . $r['wardya_no'] . '"><button name="op" value="close">🔒 إغلاق وردية</button></form>') . '</td></tr>';
echo '</table></div>';
layout_close();
