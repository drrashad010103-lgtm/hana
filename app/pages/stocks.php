<?php
// pages/safes.php + stocks.php + banks.php — تعريف (نموذج موحد)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$which = basename($_SERVER['SCRIPT_NAME'], '.php');
$cfg = [
  'safes' => ['تعريف الخزن', 'safes', [['name', 'اسم الخزينة', 200], ['accountNo', 'رقم الحساب', 120], ['tel', 'تليفون', 120], ['mob', 'موبايل', 120], ['address', 'العنوان', 200], ['notes', 'ملاحظات', 250]]],
  'stocks' => ['تعريف المخازن', 'stocks', [['name', 'اسم المخزن', 200], ['accountNo', 'رقم الحساب', 120], ['notes', 'ملاحظات', 250]]],
  'banks' => ['تعريف البنوك', 'banks', [['name', 'اسم البنك', 200], ['accountNo', 'رقم الحساب', 140], ['tel', 'تليفون', 120], ['mobile', 'موبايل', 120], ['notes', 'ملاحظات', 220]]],
][$which];
[$title, $table, $fields] = $cfg;
$msg = '';
$cols = array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field');
if (($_POST['save'] ?? '') !== '') {
  $id = (int)($_POST['id'] ?? 0);
  $vals = [];
  foreach ($fields as $f) $vals[] = mb_substr(trim($_POST['f_' . $f[0]] ?? ''), 0, 255);
  $sets = implode(',', array_map(fn($f) => "`{$f[0]}`=?", $fields));
  if ($id > 0) {
    $db->prepare("UPDATE `$table` SET $sets WHERE id=?")->execute([...$vals, $id]);
    $msg = '✅ اتعدل';
  } else {
    $mx = seq_next_table($table);
    $db->prepare("INSERT INTO `$table` (id," . implode(',', array_map(fn($f) => "`{$f[0]}`", $fields)) . ") VALUES ($mx," . implode(',', array_fill(0, count($fields), '?')) . ")")->execute($vals);
    $msg = '✅ اتضاف';
  }
}
if (($_POST['del'] ?? '') !== '' && (int)$_POST['id'] > 0) {
  if (in_array('IS_Deleted', $cols, true)) $db->prepare("UPDATE `$table` SET IS_Deleted=1 WHERE id=?")->execute([(int)$_POST['id']]);
  else $db->prepare("DELETE FROM `$table` WHERE id=?")->execute([(int)$_POST['id']]);
  $msg = '🗑️ اتحذف';
}
$sel = implode(',', array_merge(['id'], array_map(fn($f) => "`{$f[0]}`", $fields)));
$rows = $db->query("SELECT $sel FROM `$table`" . (in_array('IS_Deleted', $cols, true) ? " WHERE IS_Deleted=0 OR IS_Deleted IS NULL" : "") . " ORDER BY id LIMIT 100")->fetchAll();
$cur = null;
if (isset($_GET['id'])) foreach ($rows as $r) if ($r['id'] == (int)$_GET['id']) $cur = $r;
layout_open($title, '');
echo '<div class="card"><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">';
foreach ($fields as $f) echo '<div><label>' . $f[1] . '</label><br><input name="f_' . $f[0] . '" style="width:' . $f[2] . 'px" value="' . e($cur[$f[0]] ?? '') . '"></div>';
echo '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
echo '<div class="card"><table><tr><th>م</th>';
foreach ($fields as $f) echo '<th>' . $f[1] . '</th>';
echo '<th></th></tr>';
foreach ($rows as $r) {
  echo '<tr><td>' . $r['id'] . '</td>';
  foreach ($fields as $f) echo '<td>' . e(mb_substr((string)($r[$f[0]] ?? ''), 0, 40)) . '</td>';
  echo '<td><a href="?id=' . $r['id'] . '">فتح</a> <form method="post" style="display:inline" onsubmit="return confirm(\'حذف؟\')"><input type="hidden" name="id" value="' . $r['id'] . '"><button name="del" value="1">🗑️</button></form></td></tr>';
}
echo '</table></div>';
layout_close();
