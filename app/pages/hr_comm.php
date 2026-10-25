<?php
// pages/hr_comm.php — تقرير عمولة بائعين/دليفري (type=seller|delivery)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$type = ($_GET['type'] ?? 'seller') === 'delivery' ? 'delivery' : 'seller';
$f = dt($_GET['from'] ?? '', '2026-01-01');
$t2 = dt($_GET['to'] ?? '', '2026-12-31');
$sid = (int)($_GET['sid'] ?? 0);
$sellers = $type === 'seller'
  ? $db->query("SELECT id, name, comm FROM salesmen ORDER BY id")->fetchAll()
  : $db->query("SELECT id, name, 0 comm FROM delivery_emps WHERE is_deleted=0 OR is_deleted IS NULL ORDER BY id")->fetchAll();
$rate = 0;
foreach ($sellers as $s) if ($s['id'] == $sid) $rate = (float)$s['comm'];
$fs = $sid > 0 ? "AND i.salesman=$sid" : "";
$rows = $db->query("SELECT 'فاتورة بيع' ty, DATE(i.date) d, i.id, sm.name sm, i.tot_net t FROM inv i LEFT JOIN salesmen sm ON sm.id=i.salesman WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND DATE(i.date)>='$f' AND DATE(i.date)<='$t2' $fs ORDER BY i.date DESC LIMIT 500")->fetchAll();
$tot = array_sum(array_column($rows, 't'));
$comm = $tot * $rate / 100;
layout_open($type === 'seller' ? 'تقرير عمولة بائعين' : 'تقرير عمولة دليفري', '');
echo '<div class="card"><form class="row"><input type="hidden" name="type" value="' . $type . '">'
  . '<div><label>' . ($type === 'seller' ? 'بائع' : 'دليفري') . '</label><br><select name="sid"><option value="0">الكل</option>';
foreach ($sellers as $s) echo '<option value="' . $s['id'] . '"' . ($s['id'] == $sid ? ' selected' : '') . '>' . e($s['name']) . ' (' . $s['comm'] . '%)</option>';
echo '</select></div>'
  . '<div><label>من تاريخ</label><br><input type="date" name="from" value="' . e($f) . '"></div>'
  . '<div><label>إلى تاريخ</label><br><input type="date" name="to" value="' . e($t2) . '"></div>'
  . '<div><br><button>عرض</button> <button type="button" onclick="window.print()">طباعة</button></div></form></div>';
echo '<div class="card"><table><tr><th>نوع الحركة</th><th>التاريخ</th><th>رقم الفاتورة</th><th>' . ($type === 'seller' ? 'المندوب' : 'دليفري') . '</th><th>قيمة الفاتورة</th><th>العمولة</th><th>المبلغ المحصل</th></tr>';
foreach ($rows as $r) echo '<tr><td>' . $r['ty'] . '</td><td>' . $r['d'] . '</td><td>' . $r['id'] . '</td><td>' . e($r['sm'] ?? '') . '</td><td>' . number_format($r['t']) . '</td><td>' . number_format($r['t'] * $rate / 100) . '</td><td>' . number_format($r['t']) . '</td></tr>';
echo '</table><div class="note">فواتير مبيعات: <b>' . count($rows) . '</b> — إجمالي بالقيمة: <b>' . number_format($tot) . '</b> — إجمالي بالنسبة (' . $rate . '%): <b>' . number_format($comm) . '</b></div></div>';
layout_close();
