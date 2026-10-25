<?php
// pages/hr_depts.php — الإدارات والأقسام (mode=dept|sec)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$mode = ($_GET['mode'] ?? 'dept') === 'sec' ? 'sec' : 'dept';
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $nm = mb_substr(trim($_POST['nm'] ?? ''), 0, 128);
  $parent = (int)($_POST['parent'] ?? 0);
  if ($nm !== '') {
    $mx = seq_next_table('departments');
    $db->prepare("INSERT INTO departments (id, manag_id, name) VALUES (?,?,?)")->execute([$mx, $mode === 'sec' ? $parent : 1, $nm]);
    $msg = '✅ اتضافت';
  }
}
if (($_POST['del'] ?? '') !== '') { $db->prepare("DELETE FROM departments WHERE id=?")->execute([(int)$_POST['id']]); $msg = '🗑️'; }
$depts = $db->query("SELECT id, name FROM departments ORDER BY id")->fetchAll();
$rows = $mode === 'sec'
  ? $db->query("SELECT d.id, d.name sec, p.name dep FROM departments d LEFT JOIN departments p ON p.id=d.manag_id WHERE d.manag_id<>1 OR d.id<>d.manag_id ORDER BY d.id")->fetchAll()
  : $db->query("SELECT id, name FROM departments ORDER BY id")->fetchAll();
layout_open($mode === 'sec' ? 'الأقسام' : 'الإدارات', '');
echo '<div class="card"><h3>ادخل بيانات ' . ($mode === 'sec' ? 'الادارات والاقسام' : 'الادارات') . '</h3><form method="post" class="row">';
if ($mode === 'sec') {
  echo '<div><label>اسم الادارة</label><br><select name="parent">';
  foreach ($depts as $d) echo '<option value="' . $d['id'] . '">' . e($d['name']) . '</option>';
  echo '</select></div><div><label>اسم القسم</label><br><input name="nm" style="width:220px"></div>';
} else echo '<div><label>اسم الادارة</label><br><input name="nm" style="width:260px"></div>';
echo '<div><br><button name="save" value="1">➕ إضافة</button> <span>' . $msg . '</span></div></form></div>';
echo '<div class="card"><table><tr>';
if ($mode === 'sec') echo '<th>اسم الادارة التابع لها القسم</th><th>اسم القسم</th>';
else echo '<th>اسم الادارة</th>';
echo '<th></th></tr>';
foreach ($rows as $r) {
  if ($mode === 'sec') echo '<tr><td>' . e($r['dep'] ?? '') . '</td><td>' . e($r['sec']) . '</td>';
  else echo '<tr><td>' . e($r['name']) . '</td>';
  echo '<td><form method="post" onsubmit="return confirm(\'حذف؟\')"><input type="hidden" name="id" value="' . $r['id'] . '"><button name="del" value="1">🗑️</button></form></td></tr>';
}
echo '</table></div>';
layout_close();
