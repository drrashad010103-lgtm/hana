<?php
// pages/units.php — الوحدات (الوحدة + العدد داخل الوحدة)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['add'] ?? '') !== '') {
  $nm = mb_substr(trim($_POST['nm'] ?? ''), 0, 64);
  if ($nm !== '') {
    $mx = seq_next_table('units');
    $db->prepare("INSERT INTO units (id, name, `count`) VALUES (?,?,?)")->execute([$mx, $nm, max(1, (int)($_POST['cnt'] ?? 1))]);
    $msg = '✅ اتضافت';
  }
}
if (($_POST['del'] ?? '') !== '') {
  $db->prepare("DELETE FROM units WHERE id=?")->execute([(int)$_POST['id']]);
  $msg = '🗑️ اتحذفت';
}
$rows = $db->query("SELECT id, name, `count` FROM units ORDER BY id")->fetchAll();
layout_open('الوحدات', '');
echo '<div class="card"><form method="post" class="row"><div><label>الوحدة</label><br><input name="nm" style="width:160px"></div><div><label>العدد داخل الوحدة</label><br><input name="cnt" type="number" value="1" min="1" style="width:90px"></div><div><br><button name="add" value="1">➕ إضافة</button> <span>' . $msg . '</span></div></form></div>';
echo '<div class="card"><table><tr><th>الرقم</th><th>الوحدة</th><th>العدد داخل الوحدة</th><th></th></tr>';
foreach ($rows as $r) echo '<tr><td>' . $r['id'] . '</td><td>' . e($r['name']) . '</td><td>' . e($r['count']) . '</td><td><form method="post" onsubmit="return confirm(\'حذف؟\')"><input type="hidden" name="id" value="' . $r['id'] . '"><button name="del" value="1">🗑️</button></form></td></tr>';
echo '</table></div>';
layout_close();
