<?php
// pages/cust_bal.php — أرصدة حسابات العملاء + كشف حساب عميل + كشف بيع/شراء
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$id = (int)($_GET['id'] ?? 0);
$f = dt($_GET['from'] ?? '', '2026-01-01');
$t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
$tp = $_GET['tp'] ?? 'all';
layout_open('أرصدة حسابات العملاء', '');
echo '<div class="card"><form class="row"><div><label>العميل (0=الكل)</label><br><input name="id" value="' . $id . '" style="width:90px"></div>'
  . '<div><label>النوع</label><br><select name="tp"><option value="all">الكل</option><option value="1"' . ($tp === '1' ? ' selected' : '') . '>عملاء</option><option value="2"' . ($tp === '2' ? ' selected' : '') . '>موردين</option></select></div>'
  . '<div><label>من التاريخ</label><br><input type="date" name="from" value="' . e($f) . '"></div>'
  . '<div><label>إلى التاريخ</label><br><input type="date" name="to" value="' . e($t2) . '"></div>'
  . '<div><br><button>عرض</button> <button type="button" onclick="window.print()">طباعة</button></div></form></div>';
$fc = $id > 0 ? "AND cu.id=$id" : "";
$ft = $tp === '1' ? "AND cu.act=1" : ($tp === '2' ? "AND cu.act=2" : "");
$rows = $db->query("SELECT * FROM (SELECT cu.id, cu.name, cu.mobile, SUM(IF(i.IS_Buy=0,i.tot_net-i.paid,0)) debit, SUM(IF(i.IS_Buy=1,i.tot_net-i.paid,0)) credit FROM customers cu LEFT JOIN inv i ON i.cust_id=cu.id AND i.IS_Deleted=0 AND DATE(i.date)>='$f' AND DATE(i.date)<='$t2' WHERE 1=1 $fc $ft GROUP BY cu.id, cu.name, cu.mobile) x WHERE debit<>0 OR credit<>0 ORDER BY (debit-credit) DESC LIMIT 300")->fetchAll();
$td = $tc = 0;
echo '<div class="card"><table><tr><th>رقم الحساب</th><th>اسم العميل</th><th>حركة مدين</th><th>حركة دائن</th><th>الرصيد</th><th>الحالة</th></tr>';
foreach ($rows as $r) {
  $b = $r['debit'] - $r['credit'];
  $td += $r['debit']; $tc += $r['credit'];
  echo '<tr><td>' . $r['id'] . '</td><td><a href="cust_bal.php?id=' . $r['id'] . '">' . e($r['name']) . '</a></td><td>' . number_format($r['debit']) . '</td><td>' . number_format($r['credit']) . '</td><td><b>' . number_format($b) . '</b></td><td>' . ($b > 0 ? 'مدين' : ($b < 0 ? 'دائن' : 'متزن')) . '</td></tr>';
}
echo '</table><div class="note">الإجمالي — مدين: <b>' . number_format($td) . '</b> — دائن: <b>' . number_format($tc) . '</b> — صافي: <b>' . number_format($td - $tc) . '</b></div></div>';
if ($id > 0) {
  $inv = $db->query("SELECT id, DATE(date) d, IF(IS_Buy=0,'بيع','شراء') ty, tot_net, paid, tot_net-paid rest FROM inv WHERE cust_id=$id AND IS_Deleted=0 AND DATE(date)>='$f' AND DATE(date)<='$t2' ORDER BY date DESC LIMIT 200")->fetchAll();
  echo '<div class="card"><h3>📄 كشف حساب عميل ' . $id . '</h3><table><tr><th>مدين</th><th>دائن</th><th>رقم الفاتورة</th><th>النوع</th><th>التاريخ</th><th>الصافي</th><th>المدفوع</th><th>الباقي</th></tr>';
  foreach ($inv as $x) echo '<tr><td>' . ($x['ty'] === 'بيع' ? number_format($x['tot_net']) : '') . '</td><td>' . ($x['ty'] === 'شراء' ? number_format($x['tot_net']) : '') . '</td><td>' . $x['id'] . '</td><td>' . $x['ty'] . '</td><td>' . $x['d'] . '</td><td>' . number_format($x['tot_net']) . '</td><td>' . number_format($x['paid']) . '</td><td><b>' . number_format($x['rest']) . '</b></td></tr>';
  echo '</table></div>';
}
layout_close();
