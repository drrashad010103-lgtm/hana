<?php
// pages/defs.php — تعريف جداول أساسية (عرض + إضافة) — فرع 99 للجديد
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$allow = ['safes' => ['الخزن', 'name'], 'stocks' => ['المخازن', 'name'], 'banks' => ['البنوك', 'name'],
  'groups' => ['المجموعات', 'name'], 'units' => ['الوحدات', 'name'], 'cities' => ['المناطق', 'name'],
  'departments' => ['الإدارات والأقسام', 'name'], 'employees' => ['الموظفون', 'name'],
  'salesmen' => ['البائعون', 'name'], 'delivery_emps' => ['الدليفري', 'name'],
  'accounts_index' => ['الحسابات', 'aname']];
$t = $_GET['t'] ?? 'groups';
if (!isset($allow[$t])) $t = 'groups';
[$title, $nm] = $allow[$t];
$db = db();
if (($_POST['add'] ?? '') !== '' && trim($_POST['add'] ?? '') !== '') {
  try {
    $cols = array_column($db->query("SHOW COLUMNS FROM `$t`")->fetchAll(), 'Field');
    if (in_array('name', $cols, true)) $db->prepare("INSERT INTO `$t` (name) VALUES (?)")->execute([mb_substr(trim($_POST['add']), 0, 128)]);
    elseif (in_array('aname', $cols, true)) $db->prepare("INSERT INTO `$t` (aname) VALUES (?)")->execute([mb_substr(trim($_POST['add']), 0, 128)]);
  } catch (Throwable $e) {}
}
$cols = array_column($db->query("SHOW COLUMNS FROM `$t`")->fetchAll(), 'Field');
$show = array_values(array_intersect(['id', 'no', 'code', $nm], $cols)) ?: array_slice($cols, 0, 3);
$rows = $db->query("SELECT `" . implode('`,`', $show) . "` FROM `$t` ORDER BY 1 LIMIT 200")->fetchAll();
layout_open($title, '');
echo '<div class="card"><form method="post" class="row"><div><label>إضافة جديد</label><br><input name="add" style="width:240px"></div><div><br><button>➕ إضافة</button></div></form></div>';
echo '<div class="card"><table><tr>';
foreach ($show as $c) echo '<th>' . e($c) . '</th>';
echo '</tr>';
foreach ($rows as $r) { echo '<tr>'; foreach ($show as $c) echo '<td>' . e(mb_substr((string)($r[$c] ?? ''), 0, 60)) . '</td>'; echo '</tr>'; }
echo '</table><div class="note">العدد: ' . count($rows) . '</div></div>';
layout_close();
