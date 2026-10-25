<?php
// pages/reports.php — تقارير بيع وشراء (سعر بيع/شراء/إجمالي/ربح لكل تقرير + CSV + فلاتر)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/cfg.php';
$db = db();
$r = $_GET['r'] ?? 'daily';
$titles = ['dates' => 'تقارير بالتواريخ', 'custinv' => 'فواتير بالعملاء', 'detail' => 'مبيعات ومشتريات تفصيلية', 'profit' => 'أرباح مبيعات', 'daily' => 'حركة يومية', 'anal' => 'تحليل الحركات', 'totals' => 'إجمالي المبيعات والمشتريات', 'paym' => 'مبيعات طرق الدفع', 'reorder' => 'أصناف وصلت حد الطلب', 'qtyrev' => 'الكميات والإيرادات', 'accmain' => 'أرباح حسابات رئيسية', 'acpnl' => 'أرباح وخسائر حسابات عامة', 'accanal' => 'أرباح حسابات تحليلية'];
$paynames = [1 => '💵 كاش', 2 => '💳 فيزا', 3 => '📱 محفظة', 4 => '⚡ انستاباى'];
$picn = [1 => '💵', 2 => '💳', 3 => '📱', 4 => '⚡'];
foreach (cfg_pay_methods(true) as $__pm) $paynames[(int)$__pm['id']] = ($picn[(int)$__pm['id']] ?? '⚙️') . ' ' . $__pm['name'];
layout_open($titles[$r] ?? 'تقارير', '');
$f = dt($_GET['from'] ?? '', date('Y-m-01'));
$t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
$export = $_GET['export'] ?? '';
$limit = 5000;

// تكلفة الشراء الأخيرة لصنف
$LASTCOST = "(SELECT b.exchange_price-IFNULL(b.discount,0) FROM inv_sub b JOIN inv ib ON ib.proc_id=b.proc_id WHERE b.currency_from=s.currency_from AND ib.IS_Deleted=0 AND ib.IS_Buy=1 AND ib.proc_type IN (1,3) AND DATE(ib.date)<='$t2' ORDER BY ib.date DESC LIMIT 1)";

if ($export === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="report_' . $r . '_' . $f . '_' . $t2 . '.csv"');
  $out = fopen('php://output', 'w');
  fwrite($out, "\xEF\xBB\xBF");
}

$qs = 'r=' . $r;
$quick = [
  'اليوم' => [date('Y-m-d'), date('Y-m-d')],
  'أمس' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
  '7 أيام' => [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')],
  '30 يوم' => [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')],
  'هذا الشهر' => [date('Y-m-01'), date('Y-m-d')],
  'الشهر الماضي' => [date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month'))],
  'سنة' => [date('Y-01-01'), date('Y-m-d')],
];
$flinks = '';
foreach ($quick as $lbl => [$qf, $qt]) $flinks .= '<a class="qf" href="?' . $qs . '&from=' . $qf . '&to=' . $qt . '">' . $lbl . '</a>';
echo '<style>.qf{display:inline-block;background:#0f766e;color:#fff;padding:5px 12px;border-radius:6px;text-decoration:none;font-size:12px;margin:2px}.qf:hover{background:#14b8a6}.csv-btn{background:#16a34a;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:700;display:inline-block}</style>';
echo '<div class="card"><form class="row"><input type="hidden" name="r" value="' . e($r) . '"><div><label>من</label><br><input type="date" name="from" value="' . e($f) . '"></div><div><label>إلى</label><br><input type="date" name="to" value="' . e($t2) . '"></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">🖨️ طباعة</button> <a class="csv-btn" href="?' . $qs . '&from=' . $f . '&to=' . $t2 . '&export=csv">📥 تصدير CSV</a></div></form><div style="margin-top:8px">' . $flinks . '</div></div>';
  $wh = "i.date>='$f 00:00:00' AND i.date<='$t2 23:59:59'";

if ($r === 'dates' || $r === 'daily') {
  $rows = $db->query("SELECT DATE(i.date) d, SUM(IF(i.IS_Buy=0 AND i.proc_type=1,i.tot_net,0))-SUM(IF(i.IS_Buy=0 AND i.proc_type=2,i.tot_net,0)) sales, SUM(IF(i.IS_Buy=1 AND i.proc_type=1,i.tot_net,0)) buys, COUNT(DISTINCT IF(i.IS_Buy=0 AND i.proc_type=1,i.proc_id,NULL)) n, SUM(IF(i.IS_Buy=0 AND i.proc_type=1,i.paid,0)) paid FROM inv i WHERE i.IS_Deleted=0 AND $wh GROUP BY DATE(i.date) ORDER BY d DESC LIMIT $limit")->fetchAll();
  // ربح يومي
  $profs = $db->query("SELECT DATE(i.date) d, SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, SUM(s.val*IFNULL($LASTCOST, s.exchange_price)) cost FROM inv i JOIN inv_sub s ON s.proc_id=i.proc_id AND s.proc_type=2 WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND $wh GROUP BY DATE(i.date)")->fetchAll();
  $pm = []; foreach ($profs as $row) { $pm[$row['d']] = (float)$row['rev'] - (float)$row['cost']; }
  if ($export === 'csv') { fputcsv($out, ['التاريخ', 'مبيعات', 'مشتريات', 'فواتير', 'ربح']); foreach ($rows as $x) fputcsv($out, [$x['d'], $x['sales'], $x['buys'], $x['n'], $pm[$x['d']] ?? 0]); fclose($out); exit; }
  echo '<div class="card"><table><tr><th>التاريخ</th><th>مبيعات</th><th>مشتريات</th><th>فواتير</th><th>ربح</th><th>الصافي</th></tr>';
  $ts = $tb = $tn = $tp = 0;
  foreach ($rows as $x) { $g = $pm[$x['d']] ?? 0; $ts += $x['sales']; $tb += $x['buys']; $tn += $x['n']; $tp += $g; echo '<tr><td>' . $x['d'] . '</td><td>' . number_format($x['sales']) . '</td><td>' . number_format($x['buys']) . '</td><td>' . $x['n'] . '</td><td class="green"><b>' . number_format($g) . '</b></td><td>' . number_format($x['sales'] - $x['paid']) . '</td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td>الإجمالي</td><td>' . number_format($ts) . '</td><td>' . number_format($tb) . '</td><td>' . $tn . '</td><td>' . number_format($tp) . '</td><td></td></tr>';
  echo '</table><div class="note">عدد الأيام: ' . count($rows) . '</div></div>';

} elseif ($r === 'custinv') {
  $rows = $db->query("SELECT i.id, DATE(i.date) d, IFNULL(cu.name,'') cust, IFNULL(cu.mobile,'') mob, i.tot_net, i.paid, i.tot_net-i.paid rest, (SELECT COUNT(*) FROM inv_sub s2 WHERE s2.proc_id=i.proc_id) items FROM inv i LEFT JOIN customers cu ON cu.id=i.cust_id WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND $wh ORDER BY i.date DESC LIMIT $limit")->fetchAll();
  if ($export === 'csv') { fputcsv($out, ['فاتورة', 'التاريخ', 'العميل', 'الموبايل', 'الأصناف', 'الصافي', 'المدفوع', 'الباقي']); foreach ($rows as $x) fputcsv($out, [$x['id'], $x['d'], $x['cust'], $x['mob'], $x['items'], $x['tot_net'], $x['paid'], $x['rest']]); fclose($out); exit; }
  echo '<div class="card"><table><tr><th>فاتورة</th><th>التاريخ</th><th>العميل</th><th>الموبايل</th><th>الأصناف</th><th>الصافي</th><th>المدفوع</th><th>الباقي</th></tr>';
  $tt = $tp = $tr = 0;
  foreach ($rows as $x) { $tt += $x['tot_net']; $tp += $x['paid']; $tr += $x['rest']; echo '<tr><td>' . $x['id'] . '</td><td>' . $x['d'] . '</td><td>' . e($x['cust']) . '</td><td>' . e($x['mob']) . '</td><td>' . $x['items'] . '</td><td>' . number_format($x['tot_net']) . '</td><td>' . number_format($x['paid']) . '</td><td><b>' . number_format($x['rest']) . '</b></td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="5">الإجمالي</td><td>' . number_format($tt) . '</td><td>' . number_format($tp) . '</td><td>' . number_format($tr) . '</td></tr>';
  echo '</table><div class="note">عدد الفواتير: ' . count($rows) . '</div></div>';

} elseif ($r === 'detail') {
  $ty = $_GET['ty'] ?? 'sale';
  $fc = $ty === 'sale' ? "AND i.IS_Buy=0 AND i.proc_type=1" : ($ty === 'buy' ? "AND i.IS_Buy=1 AND i.proc_type=1" : "");
  $rows = $db->query("SELECT DATE(i.date) d, i.id, IF(i.IS_Buy=1,'شراء','بيع') ty, IFNULL(cu.name,'') cust, c.symbol nm, c.sale_price sale_pr, c.purch_price cost_pr, s.val q, s.exchange_price p, s.val*s.exchange_price-IFNULL(s.discount,0) t, IF(i.IS_Buy=1, s.val*(s.exchange_price-IFNULL(s.discount,0)), s.val*s.exchange_price-IFNULL(s.discount,0)-s.val*IFNULL($LASTCOST, s.exchange_price)) profit FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from LEFT JOIN customers cu ON cu.id=i.cust_id WHERE i.IS_Deleted=0 $fc AND $wh ORDER BY i.date DESC LIMIT $limit")->fetchAll();
  if ($export === 'csv') { fputcsv($out, ['التاريخ', 'فاتورة', 'النوع', 'عميل/مورد', 'الصنف', 'سعر البيع', 'سعر الشراء', 'كمية', 'سعر الحركة', 'الإجمالي', 'الربح']); foreach ($rows as $x) fputcsv($out, [$x['d'], $x['id'], $x['ty'], $x['cust'], $x['nm'], $x['sale_pr'], $x['cost_pr'], $x['q'], $x['p'], $x['t'], $x['profit']]); fclose($out); exit; }
  $tabs = '<a class="qf" href="?r=detail&ty=sale&from=' . $f . '&to=' . $t2 . '">🧾 بيع</a><a class="qf" href="?r=detail&ty=buy&from=' . $f . '&to=' . $t2 . '">📦 شراء</a><a class="qf" href="?r=detail&ty=both&from=' . $f . '&to=' . $t2 . '">الكل</a>';
  echo '<div style="margin-bottom:10px">' . $tabs . '</div>';
  echo '<div class="card"><table><tr><th>التاريخ</th><th>فاتورة</th><th>النوع</th><th>عميل/مورد</th><th>الصنف</th><th>سعر البيع</th><th>سعر الشراء</th><th>كمية</th><th>سعر الحركة</th><th>الإجمالي</th><th>الربح</th></tr>';
  $tt = $tp = 0;
  foreach ($rows as $x) { $tt += $x['t']; $tp += $x['profit']; echo '<tr><td>' . $x['d'] . '</td><td>' . $x['id'] . '</td><td>' . $x['ty'] . '</td><td>' . e($x['cust']) . '</td><td>' . e($x['nm']) . '</td><td>' . $x['sale_pr'] . '</td><td>' . $x['cost_pr'] . '</td><td>' . $x['q'] . '</td><td>' . $x['p'] . '</td><td>' . number_format($x['t']) . '</td><td class="green"><b>' . number_format($x['profit']) . '</b></td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="9">الإجمالي</td><td>' . number_format($tt) . '</td><td>' . number_format($tp) . '</td></tr>';
  echo '</table><div class="note">عدد البنود: ' . count($rows) . '</div></div>';

} elseif ($r === 'profit') {
  $db->exec("CREATE TEMPORARY TABLE IF NOT EXISTS _lc AS SELECT s.currency_from item, (SELECT b.exchange_price-IFNULL(b.discount,0) FROM inv_sub b JOIN inv ib ON ib.proc_id=b.proc_id WHERE b.currency_from=s.currency_from AND ib.IS_Deleted=0 AND ib.IS_Buy=1 AND ib.proc_type IN (1,3) AND DATE(ib.date)<='$t2' ORDER BY ib.date DESC LIMIT 1) c FROM (SELECT DISTINCT currency_from FROM inv_sub) s");
  $rows = $db->query("SELECT DATE(i.date) d, SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, SUM(s.val*IFNULL(lc.c,0)) cost, COUNT(DISTINCT i.proc_id) n FROM inv i JOIN inv_sub s ON s.proc_id=i.proc_id AND s.proc_type=2 LEFT JOIN _lc lc ON lc.item=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND $wh GROUP BY DATE(i.date) ORDER BY d DESC LIMIT $limit")->fetchAll();
  if ($export === 'csv') { fputcsv($out, ['التاريخ', 'إيراد', 'تكلفة', 'ربح', 'هامش %', 'فواتير']); foreach ($rows as $x) { $g = $x['rev'] - $x['cost']; fputcsv($out, [$x['d'], $x['rev'], $x['cost'], $g, $x['rev'] ? round($g / $x['rev'] * 100, 1) : 0, $x['n']]); } fclose($out); exit; }
  echo '<div class="card"><table><tr><th>التاريخ</th><th>إيراد</th><th>تكلفة</th><th>ربح</th><th>هامش</th><th>فواتير</th></tr>';
  $tr = $tc = 0;
  foreach ($rows as $x) { $g = $x['rev'] - $x['cost']; $tr += $x['rev']; $tc += $x['cost']; $mg = $x['rev'] ? round($g / $x['rev'] * 100, 1) : 0; echo '<tr><td>' . $x['d'] . '</td><td>' . number_format($x['rev']) . '</td><td class="red">' . number_format($x['cost']) . '</td><td class="green"><b>' . number_format($g) . '</b></td><td>' . $mg . '%</td><td>' . $x['n'] . '</td></tr>'; }
  $tg = $tr - $tc; $mg = $tr ? round($tg / $tr * 100, 1) : 0;
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td>الإجمالي</td><td>' . number_format($tr) . '</td><td>' . number_format($tc) . '</td><td>' . number_format($tg) . '</td><td>' . $mg . '%</td><td></td></tr>';
  echo '</table></div>';

} elseif ($r === 'anal') {
  $rows = $db->query("SELECT c.id, c.symbol nm, c.sale_price sale_pr, c.purch_price cost_pr, SUM(s.val) q, SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, AVG(s.exchange_price) avgp, SUM(s.val*s.exchange_price-IFNULL(s.discount,0)-s.val*IFNULL($LASTCOST, s.exchange_price)) profit FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id AND s.proc_type=2 LEFT JOIN currencies c ON c.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND $wh GROUP BY c.id, c.symbol, c.sale_price, c.purch_price ORDER BY rev DESC LIMIT $limit")->fetchAll();
  if ($export === 'csv') { fputcsv($out, ['الكود', 'الصنف', 'سعر البيع', 'سعر الشراء', 'كمية', 'متوسط سعر البيع', 'إيراد', 'الربح']); foreach ($rows as $x) fputcsv($out, [$x['id'], $x['nm'], $x['sale_pr'], $x['cost_pr'], $x['q'], round($x['avgp'], 2), $x['rev'], $x['profit']]); fclose($out); exit; }
  echo '<div class="card"><table><tr><th>#</th><th>الكود</th><th>الصنف</th><th>سعر البيع</th><th>سعر الشراء</th><th>كمية</th><th>متوسط سعر البيع</th><th>إيراد</th><th>الربح</th><th>نسبة الربح</th></tr>';
  $ti = $tq = $tp = 0; $n = 1;
  foreach ($rows as $x) { $ti += $x['rev']; $tq += $x['q']; $tp += $x['profit']; $margin = $x['rev'] ? round($x['profit'] / $x['rev'] * 100, 1) : 0; echo '<tr><td>' . $n++ . '</td><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td>' . $x['sale_pr'] . '</td><td>' . $x['cost_pr'] . '</td><td>' . $x['q'] . '</td><td>' . round($x['avgp'], 2) . '</td><td>' . number_format($x['rev']) . '</td><td class="green"><b>' . number_format($x['profit']) . '</b></td><td>' . $margin . '%</td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="5">الإجمالي</td><td>' . $tq . '</td><td></td><td>' . number_format($ti) . '</td><td>' . number_format($tp) . '</td><td>' . ($ti ? round($tp / $ti * 100, 1) : 0) . '%</td></tr>';
  echo '</table><div class="note">عدد الأصناف: ' . count($rows) . '</div></div>';

} elseif ($r === 'totals') {
  $x = $db->query("SELECT SUM(IF(IS_Buy=0 AND proc_type=1,tot_net,0))-SUM(IF(IS_Buy=0 AND proc_type=2,tot_net,0)) s, SUM(IF(IS_Buy=1 AND proc_type=1,tot_net,0)) b, COUNT(DISTINCT IF(IS_Buy=0 AND proc_type=1,proc_id,NULL)) ns, COUNT(DISTINCT IF(IS_Buy=1 AND proc_type=1,proc_id,NULL)) nb FROM inv WHERE IS_Deleted=0 AND $wh")->fetch();
  $p = $db->query("SELECT SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, SUM(s.val*IFNULL($LASTCOST, s.exchange_price)) cost FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id AND s.proc_type=2 WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND $wh")->fetch();
  $pb = $db->query("SELECT SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) cost FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id AND s.proc_type=2 WHERE i.IS_Deleted=0 AND i.IS_Buy=1 AND i.proc_type=1 AND $wh")->fetch();
  $g = ($p['rev'] ?? 0) - ($p['cost'] ?? 0);
  if ($export === 'csv') { fputcsv($out, ['البيان', 'القيمة']); fputcsv($out, ['إجمالي المبيعات', $x['s']]); fputcsv($out, ['إجمالي المشتريات', $x['b']]); fputcsv($out, ['عدد فواتير البيع', $x['ns']]); fputcsv($out, ['عدد فواتير الشراء', $x['nb']]); fputcsv($out, ['إيراد', $p['rev']]); fputcsv($out, ['تكلفة بيع', $p['cost']]); fputcsv($out, ['تكلفة شراء', $pb['cost']]); fputcsv($out, ['ربح', $g]); fclose($out); exit; }
  echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">';
  echo '<div class="card" style="background:linear-gradient(135deg,#052e16,#14532d);border-radius:12px;padding:18px;flex:1;min-width:180px;color:#fff"><div style="font-size:12px;opacity:.8">🧾 إجمالي المبيعات</div><div style="font-size:26px;font-weight:800;color:#4ade80">' . number_format($x['s']) . '</div><div style="font-size:12px;opacity:.7">' . $x['ns'] . ' فاتورة</div></div>';
  echo '<div class="card" style="background:linear-gradient(135deg,#450a0a,#7f1d1d);border-radius:12px;padding:18px;flex:1;min-width:180px;color:#fff"><div style="font-size:12px;opacity:.8">📦 إجمالي المشتريات</div><div style="font-size:26px;font-weight:800;color:#fca5a5">' . number_format($x['b']) . '</div><div style="font-size:12px;opacity:.7">' . $x['nb'] . ' فاتورة</div></div>';
  echo '<div class="card" style="background:linear-gradient(135deg,#422006,#78350f);border-radius:12px;padding:18px;flex:1;min-width:180px;color:#fff"><div style="font-size:12px;opacity:.8">💰 الربح</div><div style="font-size:26px;font-weight:800;color:#fbbf24">' . number_format($g) . '</div><div style="font-size:12px;opacity:.7">هامش ' . ($p['rev'] ? round($g / $p['rev'] * 100, 1) : 0) . '%</div></div>';
  echo '<div class="card" style="background:linear-gradient(135deg,#0c1e3e,#1e3a5f);border-radius:12px;padding:18px;flex:1;min-width:180px;color:#fff"><div style="font-size:12px;opacity:.8">📊 إيراد المبيعات</div><div style="font-size:26px;font-weight:800;color:#93c5fd">' . number_format($p['rev'] ?? 0) . '</div><div style="font-size:12px;opacity:.7">تكلفة: ' . number_format($p['cost'] ?? 0) . '</div></div>';
  echo '</div>';

} elseif ($r === 'paym') {
  $rows = $db->query("SELECT i.pay_type pt, IFNULL(b.name,'') acc, COUNT(*) n, SUM(i.tot_net) s, SUM(i.paid) p FROM inv i LEFT JOIN banks b ON b.id=i.bank WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND $wh GROUP BY i.pay_type, b.name ORDER BY s DESC")->fetchAll();
  if ($export === 'csv') { fputcsv($out, ['الطريقة', 'الحساب', 'فواتير', 'مبيعات', 'محصل', 'باقي']); foreach ($rows as $x) fputcsv($out, [$paynames[(int)$x['pt']] ?? $x['pt'], $x['acc'], $x['n'], $x['s'], $x['p'], $x['s'] - $x['p']]); fclose($out); exit; }
  echo '<div class="card"><table><tr><th>الطريقة</th><th>الحساب</th><th>فواتير</th><th>مبيعات</th><th>محصل</th><th>باقي</th></tr>';
  $ts = $tp = 0;
  foreach ($rows as $x) { $ts += $x['s']; $tp += $x['p']; echo '<tr><td>' . ($paynames[(int)$x['pt']] ?? $x['pt']) . '</td><td>' . e($x['acc']) . '</td><td>' . $x['n'] . '</td><td>' . number_format($x['s']) . '</td><td>' . number_format($x['p']) . '</td><td><b>' . number_format($x['s'] - $x['p']) . '</b></td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="3">الإجمالي</td><td>' . number_format($ts) . '</td><td>' . number_format($tp) . '</td><td>' . number_format($ts - $tp) . '</td></tr>';
  echo '</table></div>';

} elseif ($r === 'reorder') {
  $rows = $db->query("SELECT c.id, c.symbol nm, c.`limit` lim, IFNULL(b.bal,0) bal, c.sale_price, c.purch_price FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.IS_Deleted=0 HAVING bal <= lim ORDER BY bal LIMIT $limit")->fetchAll();
  if ($export === 'csv') { fputcsv($out, ['الكود', 'الصنف', 'الرصيد', 'الحد الأدنى', 'الفرق', 'سعر البيع', 'سعر الشراء', 'قيمة الطلب']); foreach ($rows as $x) fputcsv($out, [$x['id'], $x['nm'], $x['bal'], $x['lim'], $x['bal'] - $x['lim'], $x['sale_price'], $x['purch_price'], max(0, $x['lim'] - $x['bal']) * $x['purch_price']]); fclose($out); exit; }
  echo '<div class="card"><table><tr><th>الكود</th><th>الصنف</th><th>الرصيد</th><th>الحد الأدنى</th><th>الفرق</th><th>سعر البيع</th><th>سعر الشراء</th><th>قيمة الطلب المقترح</th></tr>';
  $tv = 0;
  foreach ($rows as $x) { $need = max(0, $x['lim'] - $x['bal']); $tv += $need * $x['purch_price']; echo '<tr><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td class="red"><b>' . $x['bal'] . '</b></td><td>' . $x['lim'] . '</td><td class="red">' . ($x['bal'] - $x['lim']) . '</td><td>' . $x['sale_price'] . '</td><td>' . $x['purch_price'] . '</td><td><b>' . number_format($need * $x['purch_price']) . '</b></td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="7">إجمالي قيمة الطلب</td><td>' . number_format($tv) . '</td></tr>';
  echo '</table><div class="note">عدد الأصناف: ' . count($rows) . '</div></div>';

} elseif ($r === 'qtyrev') {
  $rows = $db->query("SELECT c.id, c.symbol nm, c.sale_price sale_pr, c.purch_price cost_pr, SUM(s.val) q, SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, SUM(s.val*IFNULL($LASTCOST, s.exchange_price)) cost, COUNT(DISTINCT i.proc_id) n FROM inv i JOIN inv_sub s ON s.proc_id=i.proc_id AND s.proc_type=2 LEFT JOIN currencies c ON c.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND $wh GROUP BY c.id, c.symbol, c.sale_price, c.purch_price ORDER BY rev DESC LIMIT $limit")->fetchAll();
  $all = $db->query("SELECT SUM(s.val) q, SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, SUM(s.val*IFNULL($LASTCOST, s.exchange_price)) cost, COUNT(DISTINCT i.proc_id) n FROM inv i JOIN inv_sub s ON s.proc_id=i.proc_id AND s.proc_type=2 WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND $wh")->fetch();
  if ($export === 'csv') { fputcsv($out, ['الكود', 'الصنف', 'سعر البيع', 'سعر الشراء', 'الكمية', 'إيراد', 'تكلفة', 'الربح', 'نسبة %']); foreach ($rows as $x) { $g = $x['rev'] - $x['cost']; fputcsv($out, [$x['id'], $x['nm'], $x['sale_pr'], $x['cost_pr'], $x['q'], $x['rev'], $x['cost'], $g, $all['rev'] ? round($x['rev'] / $all['rev'] * 100, 1) : 0]); } fclose($out); exit; }
  $tg = $all['rev'] - $all['cost'];
  echo '<div style="display:flex;gap:10px;margin-bottom:12px">';
  echo '<div class="card" style="background:linear-gradient(135deg,#052e16,#14532d);border-radius:12px;padding:15px;flex:1;color:#fff"><div style="font-size:12px;opacity:.8">📦 إجمالي الكمية</div><div style="font-size:24px;font-weight:800;color:#4ade80">' . number_format($all['q']) . '</div></div>';
  echo '<div class="card" style="background:linear-gradient(135deg,#0c1e3e,#1e3a5f);border-radius:12px;padding:15px;flex:1;color:#fff"><div style="font-size:12px;opacity:.8">💰 الإيراد</div><div style="font-size:24px;font-weight:800;color:#93c5fd">' . number_format($all['rev']) . '</div></div>';
  echo '<div class="card" style="background:linear-gradient(135deg,#450a0a,#7f1d1d);border-radius:12px;padding:15px;flex:1;color:#fff"><div style="font-size:12px;opacity:.8">💸 التكلفة</div><div style="font-size:24px;font-weight:800;color:#fca5a5">' . number_format($all['cost']) . '</div></div>';
  echo '<div class="card" style="background:linear-gradient(135deg,#422006,#78350f);border-radius:12px;padding:15px;flex:1;color:#fff"><div style="font-size:12px;opacity:.8">🏆 الربح</div><div style="font-size:24px;font-weight:800;color:#fbbf24">' . number_format($tg) . '</div><div style="font-size:12px;opacity:.7">' . ($all['rev'] ? round($tg / $all['rev'] * 100, 1) : 0) . '%</div></div>';
  echo '</div>';
  echo '<div class="card"><table><tr><th>#</th><th>الكود</th><th>الصنف</th><th>سعر البيع</th><th>سعر الشراء</th><th>الكمية</th><th>إيراد</th><th>تكلفة</th><th>الربح</th><th>نسبة</th></tr>';
  $n = 1;
  foreach ($rows as $x) { $g = $x['rev'] - $x['cost']; $pct = $all['rev'] ? round($x['rev'] / $all['rev'] * 100, 1) : 0; echo '<tr><td>' . $n++ . '</td><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td>' . $x['sale_pr'] . '</td><td>' . $x['cost_pr'] . '</td><td>' . $x['q'] . '</td><td>' . number_format($x['rev']) . '</td><td>' . number_format($x['cost']) . '</td><td class="green"><b>' . number_format($g) . '</b></td><td>' . $pct . '%</td></tr>'; }
  echo '</table><div class="note">عدد الأصناف: ' . count($rows) . '</div></div>';

} else {
  $rows = $db->query("SELECT a.AName nm, SUM(IF(s.dept>0,s.dept,0)) d, SUM(IF(s.credit>0,s.credit,0)) c FROM restrictions_sub s JOIN restrictions r ON r.id=s.res_id LEFT JOIN accounts_index a ON a.Code=s.acc_no WHERE r.IS_Deleted=0 AND DATE(r.date)>='$f' AND DATE(r.date)<='$t2' GROUP BY a.AName ORDER BY d DESC LIMIT $limit")->fetchAll();
  if ($export === 'csv') { fputcsv($out, ['الحساب', 'مدين', 'دائن', 'الرصيد']); foreach ($rows as $x) fputcsv($out, [$x['nm'] ?? '', $x['d'], $x['c'], $x['d'] - $x['c']]); fclose($out); exit; }
  echo '<div class="card"><table><tr><th>الحساب</th><th>مدين</th><th>دائن</th><th>الرصيد</th></tr>';
  $td = $tc2 = 0;
  foreach ($rows as $x) { $td += $x['d']; $tc2 += $x['c']; echo '<tr><td>' . e($x['nm'] ?? '') . '</td><td>' . number_format($x['d']) . '</td><td>' . number_format($x['c']) . '</td><td><b>' . number_format($x['d'] - $x['c']) . '</b></td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td>الإجمالي</td><td>' . number_format($td) . '</td><td>' . number_format($tc2) . '</td><td>' . number_format($td - $tc2) . '</td></tr>';
  echo '</table><div class="note">من القيود المحاسبية — عدد القيود: ' . count($rows) . '</div></div>';
}
layout_close();
