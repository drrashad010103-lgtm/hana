<?php
// pages/maingroups.php — مجموعات رئيسية الأصناف + مجموعات الأصناف (محرر كامل)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $id = (int)($_POST['id'] ?? 0);
  $f = [mb_substr(trim($_POST['nm'] ?? ''), 0, 128), mb_substr(trim($_POST['en'] ?? ''), 0, 128), (int)($_POST['parent'] ?? 0), (float)($_POST['dis'] ?? 0), (int)($_POST['ord'] ?? 0)];
  if ($id > 0) {
    $db->prepare("UPDATE `groups` SET name=?, nameEN=?, parent_id=?, dis=?, group_orders=? WHERE id=?")->execute([...$f, $id]);
    $msg = '✅ اتعدلت';
  } elseif ($f[0] !== '') {
    $mx = seq_next_table('groups');
    $db->prepare("INSERT INTO `groups` (id, name, nameEN, parent_id, dis, group_orders) VALUES (?,?,?,?,?,?)")->execute([$mx, ...$f]);
    $msg = '✅ اتضافت (' . $mx . ')';
  }
}
if (($_POST['del'] ?? '') !== '' && (int)$_POST['id'] > 0) {
  $n = $db->query("SELECT COUNT(*) n FROM currencies WHERE group_id=" . (int)$_POST['id'])->fetch()['n'];
  if ($n > 0) $msg = '❌ المجموعة عليها ' . $n . ' صنف — انقلهم الأول';
  else { $db->exec("DELETE FROM `groups` WHERE id=" . (int)$_POST['id']); $msg = '🗑️ اتحذفت'; }
}
$cur = isset($_GET['id']) ? $db->query("SELECT * FROM `groups` WHERE id=" . (int)$_GET['id'])->fetch() : null;
$rows = $db->query("SELECT g.id, g.name, g.nameEN, g.dis, g.group_orders, p.name pname FROM `groups` g LEFT JOIN `groups` p ON p.id=g.parent_id ORDER BY g.id")->fetchAll();
layout_open('مجموعات الأصناف', '');
echo '<div class="card"><h3>' . ($cur ? 'تعديل مجموعة ' . $cur['id'] : 'مجموعة جديدة') . '</h3><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">'
  . '<div><label>مجموعة الصنف</label><br><input name="nm" style="width:200px" value="' . e($cur['name'] ?? '') . '"></div>'
  . '<div><label>مجموعة الصنف (EN)</label><br><input name="en" style="width:160px" value="' . e($cur['nameEN'] ?? '') . '" dir="ltr"></div>'
  . '<div><label>مجموعة رئيسية</label><br><select name="parent"><option value="0">— بدون —</option>';
foreach ($rows as $g) echo '<option value="' . $g['id'] . '"' . (($cur && $cur['parent_id'] == $g['id']) ? ' selected' : '') . '>' . e($g['name']) . '</option>';
echo '</select></div>'
  . '<div><label>خصم المجموعة %</label><br><input name="dis" type="number" step="0.01" value="' . e($cur['dis'] ?? 0) . '" style="width:80px"></div>'
  . '<div><label>ترتيب العرض</label><br><input name="ord" type="number" value="' . e($cur['group_orders'] ?? 0) . '" style="width:80px"></div>'
  . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
echo '<div class="card"><table><tr><th>الرقم</th><th>مجموعة الصنف</th><th>EN</th><th>خصم %</th><th>مجموعة رئيسية</th><th>ترتيب</th><th></th></tr>';
foreach ($rows as $r) echo '<tr><td>' . $r['id'] . '</td><td><b>' . e($r['name']) . '</b></td><td>' . e($r['nameEN'] ?? '') . '</td><td>' . e($r['dis'] ?? 0) . '</td><td>' . e($r['pname'] ?? '—') . '</td><td>' . e($r['group_orders'] ?? 0) . '</td><td><a href="?id=' . $r['id'] . '">فتح</a> <form method="post" style="display:inline" onsubmit="return confirm(\'حذف المجموعة؟\')"><input type="hidden" name="id" value="' . $r['id'] . '"><button name="del" value="1">🗑️</button></form></td></tr>';
echo '</table></div>';
layout_close();
