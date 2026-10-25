<?php
// pages/areas.php — تعريف المناطق (دولة/مدينة/منطقة)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['add'] ?? '') !== '') {
  $co = (int)($_POST['co'] ?? 0); $ci = (int)($_POST['ci'] ?? 0); $nm = mb_substr(trim($_POST['nm'] ?? ''), 0, 128);
  if ($nm !== '' && $ci > 0) {
    $mx = seq_next_table('areas');
    $db->prepare("INSERT INTO areas (id, name, city) VALUES (?,?,?)")->execute([$mx, $nm, $ci]);
    $msg = '✅ اتضافت';
  }
}
$cos = $db->query("SELECT id, name FROM countries ORDER BY id")->fetchAll();
$cis = $db->query("SELECT id, name, country FROM cities ORDER BY id")->fetchAll();
$rows = $db->query("SELECT ar.name a, ci.name c, co.name co FROM areas ar LEFT JOIN cities ci ON ci.id=ar.city LEFT JOIN countries co ON co.id=ci.country ORDER BY co, ci.name, ar.name LIMIT 500")->fetchAll();
layout_open('تعريف المناطق', '');
echo '<div class="card"><h3>ادخل بيانات المناطق</h3><form method="post" class="row"><div><label>اسم الدولة</label><br><select name="co" id="coSel">';
foreach ($cos as $c) echo '<option value="' . $c['id'] . '">' . e($c['name']) . '</option>';
echo '</select></div><div><label>اسم المدينة</label><br><select name="ci" id="ciSel">';
foreach ($cis as $c) echo '<option value="' . $c['id'] . '" data-co="' . $c['country'] . '">' . e($c['name']) . '</option>';
echo '</select></div><div><label>اسم المنطقة</label><br><input name="nm" style="width:200px"></div><div><br><button name="add" value="1">➕ إضافة</button> <span>' . $msg . '</span></div></form></div>';
echo '<div class="card"><table><tr><th>اسم الدولة</th><th>اسم المدينة</th><th>اسم المنطقة</th></tr>';
foreach ($rows as $r) echo '<tr><td>' . e($r['co']) . '</td><td>' . e($r['c']) . '</td><td>' . e($r['a']) . '</td></tr>';
echo '</table><div class="note">العدد: ' . count($rows) . '</div></div>';
layout_close();
