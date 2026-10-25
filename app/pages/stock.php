<?php
// pages/stock.php — تقارير المخازن (سعر بيع/شراء/إجمالي/ربح لكل تقرير + CSV + فلاتر)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$r = $_GET['r'] ?? 'balances';
$titles = ['opening' => 'مخزون أول المدة', 'moves' => 'حركات المخازن', 'transfers' => 'تقرير تحويلات المخازن', 'gardprep' => 'كشف تجهيز الجرد', 'gard' => 'كشف جرد المخازن', 'gardval' => 'كشف جرد المخازن بقيم', 'garddetail' => 'كشف جرد المخازن تفصيلي', 'balances' => 'أرصدة المخزن حتى تاريخ', 'item' => 'حركة صنف محددة', 'itemtot' => 'صنف بإجمالي الحركات', 'expiry' => 'أصناف سنتهتي تاريخها', 'center' => 'جرد مخزن سنتر'];
layout_open($titles[$r] ?? 'المخازن', '');
$code = (int)($_GET['code'] ?? 0);
$export = $_GET['export'] ?? '';
$limit = 5000;
$LASTCOST = "(SELECT b.exchange_price-IFNULL(b.discount,0) FROM inv_sub b JOIN inv ib ON ib.proc_id=b.proc_id WHERE b.currency_from=s.currency_from AND ib.IS_Deleted=0 AND ib.IS_Buy=1 AND ib.proc_type IN (1,3) ORDER BY ib.date DESC LIMIT 1)";

echo '<style>.qf{display:inline-block;background:#0f766e;color:#fff;padding:5px 12px;border-radius:6px;text-decoration:none;font-size:12px;margin:2px}.qf:hover{background:#14b8a6}.csv-btn{background:#16a34a;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:700;display:inline-block}</style>';

if ($r === 'balances' || $r === 'opening') {
  $op = $r === 'opening' ? "AND EXISTS (SELECT 1 FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.proc_type=3 AND i.IS_Deleted=0)" : "";
  $mode = $_GET['mode'] ?? 'all';
  $fm = $mode === 'zero' ? "HAVING bal=0" : ($mode === 'neg' ? "HAVING bal<0" : ($mode === 'pos' ? "HAVING bal>0" : "HAVING bal<>0"));
  $pg = max(1, (int)($_GET['pg'] ?? 1));
  $per = 200;
  $off = ($pg - 1) * $per;
  if ($export === 'csv') {
    $rows = $db->query("SELECT c.id, c.symbol nm, IFNULL(b.bal,0) bal, c.sale_price, c.purch_price FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.IS_Deleted=0 $op ORDER BY c.id")->fetchAll();
    $data = []; $sBal = $sSale = $sCost = 0;
    foreach ($rows as $x) { $v1 = $x['bal'] * $x['sale_price']; $v2 = $x['bal'] * $x['purch_price']; $sBal += $x['bal']; $sSale += $v1; $sCost += $v2; $data[] = [$x['id'], $x['nm'], $x['bal'], $x['sale_price'], $x['purch_price'], $v1, $v2]; }
    $data[] = ['الإجمالي', '', $sBal, '', '', $sSale, $sCost];
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="balances.csv"'); $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['الكود', 'الصنف', 'الرصيد', 'سعر البيع', 'سعر الشراء', 'قيمة البيع', 'قيمة التكلفة']); foreach ($data as $x) fputcsv($o, $x); fclose($o); exit;
  }
  $total = (int)$db->query("SELECT COUNT(*) FROM (SELECT IFNULL(b.bal,0) bal FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.IS_Deleted=0 $op $fm) x")->fetchColumn();
  $rows = $db->query("SELECT c.id, c.symbol nm, IFNULL(b.bal,0) bal, c.sale_price, c.purch_price FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.IS_Deleted=0 $op $fm ORDER BY bal DESC LIMIT $per OFFSET $off")->fetchAll();
  $upd = $db->query("SELECT MAX(updated) u FROM item_bal")->fetch()['u'] ?? '';
  echo '<div class="card"><form class="row"><input type="hidden" name="r" value="' . e($r) . '"><div><label>نوع الرصيد</label><br><select name="mode"><option value="all"' . ($mode === 'all' ? ' selected' : '') . '>الكل</option><option value="pos"' . ($mode === 'pos' ? ' selected' : '') . '>موجب</option><option value="zero"' . ($mode === 'zero' ? ' selected' : '') . '>= 0</option><option value="neg"' . ($mode === 'neg' ? ' selected' : '') . '>سالب</option></select></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">🖨️</button> <a class="csv-btn" href="?r=' . $r . '&mode=' . $mode . '&export=csv">📥 CSV</a></div></form><div class="note">آخر تحديث: ' . e($upd) . ' — <a href="../refresh_bal.php" style="color:#4ade80">تحديث الآن</a></div></div>';
  echo '<div class="card"><table><tr><th>الكود</th><th>الصنف</th><th>الرصيد</th><th>سعر البيع</th><th>سعر الشراء</th><th>قيمة البيع</th><th>قيمة التكلفة</th><th>الربح المتوقع</th></tr>';
  $tb = $tv = $tc = $tpr = 0;
  foreach ($rows as $x) { $v1 = $x['bal'] * $x['sale_price']; $v2 = $x['bal'] * $x['purch_price']; $pr = $v1 - $v2; $tb += $x['bal']; $tv += $v1; $tc += $v2; $tpr += $pr; echo '<tr><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td class="' . ($x['bal'] < 0 ? 'red' : 'green') . '"><b>' . $x['bal'] . '</b></td><td>' . $x['sale_price'] . '</td><td>' . $x['purch_price'] . '</td><td>' . number_format($v1) . '</td><td>' . number_format($v2) . '</td><td class="green"><b>' . number_format($pr) . '</b></td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="2">الإجمالي</td><td>' . $tb . '</td><td></td><td></td><td>' . number_format($tv) . '</td><td>' . number_format($tc) . '</td><td>' . number_format($tpr) . '</td></tr>';
  echo '</table><div class="note">عدد الأصناف: ' . count($rows) . ' من ' . number_format($total) . '</div>';
  $pages = ceil($total / $per);
  if ($pages > 1) {
    echo '<div style="text-align:center;margin-top:10px">';
    if ($pg > 1) echo '<a class="qf" href="?r=' . $r . '&mode=' . $mode . '&pg=' . ($pg - 1) . '">◀ السابق</a>';
    echo ' <span style="color:#94a3b8">صفحة ' . $pg . ' من ' . $pages . '</span> ';
    if ($pg < $pages) echo '<a class="qf" href="?r=' . $r . '&mode=' . $mode . '&pg=' . ($pg + 1) . '">التالي ▶</a>';
    echo '</div>';
  }
  echo '</div>';

} elseif ($r === 'moves' || $r === 'item') {
  $f = dt($_GET['from'] ?? '', date('Y-m-01'));
  $t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
  $fc = $code > 0 ? "AND s.currency_from=$code" : "";
  $ty = $_GET['ty'] ?? 'all';
  $ft = $ty === 'buy' ? "AND i.IS_Buy=1" : ($ty === 'sale' ? "AND i.IS_Buy=0" : "");
  $q = "SELECT IF(i.IS_Buy=1,'شراء','بيع') ty, DATE(i.date) d, i.id, IFNULL(cu.name,'') cust, c.symbol nm, c.sale_price sale_pr, c.purch_price cost_pr, s.val q, s.exchange_price-IFNULL(s.discount,0) p, s.val*(s.exchange_price-IFNULL(s.discount,0)) t, IF(i.IS_Buy=0, s.val*(s.exchange_price-IFNULL(s.discount,0))-s.val*IFNULL($LASTCOST, s.exchange_price), 0) profit FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from LEFT JOIN customers cu ON cu.id=i.cust_id WHERE i.IS_Deleted=0 AND i.date>='$f 00:00:00' AND i.date<='$t2 23:59:59' $fc $ft ORDER BY i.date DESC";
  if ($export === 'csv') {
    $rows = $db->query($q)->fetchAll();
    $data = []; foreach ($rows as $x) $data[] = [$x['ty'], $x['d'], $x['id'], $x['cust'], $x['nm'], $x['sale_pr'], $x['cost_pr'], $x['q'], $x['p'], $x['t'], $x['profit']];
    $data[] = ['الإجمالي', '', '', '', '', '', '', array_sum(array_column($rows, 'q')), '', array_sum(array_column($rows, 't')), array_sum(array_column($rows, 'profit'))];
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="moves.csv"'); $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['النوع', 'التاريخ', 'فاتورة', 'عميل/مورد', 'الصنف', 'سعر البيع', 'سعر الشراء', 'الكمية', 'سعر الحركة', 'الإجمالي', 'الربح']); foreach ($data as $x) fputcsv($o, $x); fclose($o); exit;
  }
  $rows = $db->query($q . " LIMIT $limit")->fetchAll();
  $tabs = '<a class="qf" href="?r=' . $r . '&ty=all&from=' . $f . '&to=' . $t2 . '&code=' . $code . '">الكل</a><a class="qf" href="?r=' . $r . '&ty=buy&from=' . $f . '&to=' . $t2 . '&code=' . $code . '">📦 شراء</a><a class="qf" href="?r=' . $r . '&ty=sale&from=' . $f . '&to=' . $t2 . '&code=' . $code . '">🧾 بيع</a>';
  echo '<div class="card"><form class="row"><input type="hidden" name="r" value="' . e($r) . '"><div><label>كود الصنف</label><br><input name="code" value="' . $code . '" style="width:100px"></div><div><label>من</label><br><input type="date" name="from" value="' . e($f) . '"></div><div><label>إلى</label><br><input type="date" name="to" value="' . e($t2) . '"></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">🖨️</button> <a class="csv-btn" href="?r=' . $r . '&code=' . $code . '&ty=' . $ty . '&from=' . $f . '&to=' . $t2 . '&export=csv">📥 CSV</a></div></form><div>' . $tabs . '</div></div>';
  echo '<div class="card"><table><tr><th>النوع</th><th>التاريخ</th><th>فاتورة</th><th>عميل/مورد</th><th>الصنف</th><th>سعر البيع</th><th>سعر الشراء</th><th>الكمية</th><th>سعر الحركة</th><th>الإجمالي</th><th>الربح</th></tr>';
  $tq = $tt = $tpr = 0;
  foreach ($rows as $x) { $tq += $x['q']; $tt += $x['t']; $tpr += $x['profit']; echo '<tr><td>' . $x['ty'] . '</td><td>' . $x['d'] . '</td><td>' . $x['id'] . '</td><td>' . e($x['cust']) . '</td><td>' . e($x['nm']) . '</td><td>' . $x['sale_pr'] . '</td><td>' . $x['cost_pr'] . '</td><td>' . $x['q'] . '</td><td>' . $x['p'] . '</td><td>' . number_format($x['t']) . '</td><td class="green"><b>' . number_format($x['profit']) . '</b></td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="7">الإجمالي</td><td>' . $tq . '</td><td></td><td>' . number_format($tt) . '</td><td>' . number_format($tpr) . '</td></tr>';
  echo '</table><div class="note">عدد الحركات: ' . count($rows) . '</div></div>';

} elseif ($r === 'itemtot') {
  $f = dt($_GET['from'] ?? '', '2026-01-01');
  $t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
  $grp = (int)($_GET['grp'] ?? 0);
  $fc = $code > 0 ? "AND c.id=$code" : "";
  $fg = $grp > 0 ? "AND c.group_id=$grp" : "";
  $groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
  $base = "SELECT c.id, c.symbol nm, c.sale_price sale_pr, c.purch_price cost_pr, IFNULL(b.bal,0) bal,
    (SELECT IFNULL(SUM(s.val),0) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=1 AND i.proc_type=3) op,
    (SELECT IFNULL(SUM(s.val),0) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=1 AND i.proc_type=1 AND DATE(i.date)>='$f' AND DATE(i.date)<='$t2') buy,
    (SELECT IFNULL(SUM(s.val),0) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=1 AND i.proc_type=2 AND DATE(i.date)>='$f' AND DATE(i.date)<='$t2') buyr,
    (SELECT IFNULL(SUM(s.val),0) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND DATE(i.date)>='$f' AND DATE(i.date)<='$t2') sell,
    (SELECT IFNULL(SUM(s.val),0) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=2 AND DATE(i.date)>='$f' AND DATE(i.date)<='$t2') sellr,
    (SELECT IFNULL(SUM(s.val*s.exchange_price-IFNULL(s.discount,0)),0) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND DATE(i.date)>='$f' AND DATE(i.date)<='$t2') sellrev,
    IFNULL((SELECT SUM(s.val*s.exchange_price)/NULLIF(SUM(s.val),0) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=1 AND i.proc_type IN (1,3)), c.purch_price) avgc
    FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.IS_Deleted=0 $fc $fg HAVING (op+buy-buyr-sell+sellr)<>0 OR bal<>0 OR sellrev>0 ORDER BY c.id";
  $rows = $db->query($base)->fetchAll();
  if ($export === 'csv') {
    $data = []; $tt = $ts = 0;
    foreach ($rows as $x) { $bal = $x['op'] + $x['buy'] - $x['buyr'] - $x['sell'] + $x['sellr']; $tc2 = $bal * $x['avgc']; $profit = $x['sellrev'] - $x['sell'] * $x['avgc']; $tt += $tc2; $ts += $profit; $data[] = [$x['id'], $x['nm'], $x['sale_pr'], $x['cost_pr'], round($x['avgc'], 2), round($tc2), $x['op'], $x['buy'], $x['buyr'], $x['sell'], $x['sellr'], $bal, $x['sellrev'], round($profit)]; }
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="item_totals.csv"'); $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['الرقم', 'الصنف', 'سعر البيع', 'سعر الشراء', 'متوسط التكلفة', 'إجمالي التكلفة', 'رصيد أول', 'مشتريات', 'مردود مشتريات', 'مبيعات', 'مردود مبيعات', 'الرصيد', 'إيراد المبيعات', 'الربح']); foreach ($data as $x) fputcsv($o, $x); fputcsv($o, ['الإجمالي', '', '', '', '', round($tt), '', '', '', '', '', '', '', round($ts)]); fclose($o); exit;
  }
  echo '<div class="card"><form class="row"><input type="hidden" name="r" value="itemtot"><div><label>كود الصنف</label><br><input name="code" value="' . $code . '" style="width:90px"></div><div><label>المجموعة</label><br><select name="grp"><option value="0">الكل</option>';
  foreach ($groups as $g) echo '<option value="' . $g['id'] . '"' . ($g['id'] == $grp ? ' selected' : '') . '>' . e($g['name']) . '</option>';
  echo '</select></div><div><label>من</label><br><input type="date" name="from" value="' . e($f) . '"></div><div><label>إلى</label><br><input type="date" name="to" value="' . e($t2) . '"></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">🖨️</button> <a class="csv-btn" href="?r=itemtot&code=' . $code . '&grp=' . $grp . '&from=' . $f . '&to=' . $t2 . '&export=csv">📥 CSV</a></div></form></div>';
  echo '<div class="card"><table><tr><th>الرقم</th><th>الصنف</th><th>سعر البيع</th><th>سعر الشراء</th><th>متوسط التكلفة</th><th>إجمالي التكلفة</th><th>رصيد أول</th><th>مشتريات</th><th>مردود مشتريات</th><th>مبيعات</th><th>مردود مبيعات</th><th>الرصيد</th><th>إيراد المبيعات</th><th>الربح</th></tr>';
  $tt = $ts = 0;
  foreach ($rows as $x) {
    $bal = $x['op'] + $x['buy'] - $x['buyr'] - $x['sell'] + $x['sellr'];
    $tc2 = $bal * $x['avgc']; $profit = $x['sellrev'] - $x['sell'] * $x['avgc'];
    $tt += $tc2; $ts += $profit;
    echo '<tr><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td>' . $x['sale_pr'] . '</td><td>' . $x['cost_pr'] . '</td><td>' . round($x['avgc'], 2) . '</td><td>' . number_format($tc2) . '</td><td>' . $x['op'] . '</td><td>' . $x['buy'] . '</td><td>' . $x['buyr'] . '</td><td>' . $x['sell'] . '</td><td>' . $x['sellr'] . '</td><td><b>' . $bal . '</b></td><td>' . number_format($x['sellrev']) . '</td><td class="green"><b>' . number_format($profit) . '</b></td></tr>';
  }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="5">الإجمالي</td><td>' . number_format($tt) . '</td><td colspan="7"></td><td></td><td>' . number_format($ts) . '</td></tr>';
  echo '</table><div class="note">عدد الأصناف: ' . count($rows) . '</div></div>';

} elseif ($r === 'transfers') {
  $f = dt($_GET['from'] ?? '', '2026-10-01');
  $t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
  $q = "SELECT t.id, DATE(t.date) d, t.type, c.symbol nm, sub.totquan q, sub.price p, sub.totquan*sub.price t FROM twreed_srf t JOIN twreed_srf_sub sub ON sub.proc_id=t.id LEFT JOIN currencies c ON c.id=sub.item WHERE t.is_deleted=0 AND DATE(t.date)>='$f' AND DATE(t.date)<='$t2' ORDER BY t.date DESC";
  if ($export === 'csv') {
    $rows = $db->query($q)->fetchAll();
    $data = []; foreach ($rows as $x) $data[] = [$x['id'], $x['d'], $x['type'] == 1 ? 'توريد' : 'صرف', $x['nm'], $x['q'], $x['p'], $x['t']];
    $data[] = ['الإجمالي', '', '', '', array_sum(array_column($rows, 'q')), '', array_sum(array_column($rows, 't'))];
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="transfers.csv"'); $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['رقم', 'التاريخ', 'النوع', 'الصنف', 'الكمية', 'السعر', 'الإجمالي']); foreach ($data as $x) fputcsv($o, $x); fclose($o); exit;
  }
  $rows = $db->query($q . " LIMIT $limit")->fetchAll();
  echo '<div class="card"><form class="row"><input type="hidden" name="r" value="transfers"><div><label>من</label><br><input type="date" name="from" value="' . e($f) . '"></div><div><label>إلى</label><br><input type="date" name="to" value="' . e($t2) . '"></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">🖨️</button> <a class="csv-btn" href="?r=transfers&from=' . $f . '&to=' . $t2 . '&export=csv">📥 CSV</a></div></form></div>';
  echo '<div class="card"><table><tr><th>رقم</th><th>التاريخ</th><th>النوع</th><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr>';
  $tq = $tt = 0;
  foreach ($rows as $x) { $tq += $x['q']; $tt += $x['t']; echo '<tr><td>' . $x['id'] . '</td><td>' . $x['d'] . '</td><td>' . ($x['type'] == 1 ? 'توريد' : 'صرف') . '</td><td>' . e($x['nm']) . '</td><td>' . $x['q'] . '</td><td>' . $x['p'] . '</td><td>' . number_format($x['t']) . '</td></tr>'; }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="4">الإجمالي</td><td>' . $tq . '</td><td></td><td>' . number_format($tt) . '</td></tr>';
  echo '</table><div class="note">عدد الحركات: ' . count($rows) . '</div></div>';

} elseif ($r === 'gardprep') {
  $grp = (int)($_GET['grp'] ?? 0);
  $mode = $_GET['mode'] ?? 'all';
  $groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
  $fw = $grp > 0 ? "AND c.group_id=$grp" : "";
  $fm = $mode === 'zero' ? "HAVING bal=0" : ($mode === 'pos' ? "HAVING bal>0" : "");
  $q = "SELECT c.id, c.symbol nm, IFNULL(b.bal,0) bal FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.IS_Deleted=0 $fw $fm ORDER BY c.id";
  if ($export === 'csv') {
    $rows = $db->query($q)->fetchAll();
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="inventory_prep.csv"'); $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['الكود', 'الصنف', 'الرصيد']); foreach ($rows as $x) fputcsv($o, [$x['id'], $x['nm'], $x['bal']]); fputcsv($o, ['الإجمالي', '', array_sum(array_column($rows, 'bal'))]); fclose($o); exit;
  }
  $rows = $db->query($q . " LIMIT $limit")->fetchAll();
  $tq = array_sum(array_column($rows, 'bal'));
  echo '<div class="card"><form class="row"><input type="hidden" name="r" value="gardprep"><div><label>بالمجموعة</label><br><select name="grp"><option value="0">الكل</option>';
  foreach ($groups as $g) echo '<option value="' . $g['id'] . '"' . ($g['id'] == $grp ? ' selected' : '') . '>' . e($g['name']) . '</option>';
  echo '</select></div><div><label>الأصناف</label><br><select name="mode"><option value="all">الكل</option><option value="zero"' . ($mode === 'zero' ? ' selected' : '') . '>= 0</option><option value="pos"' . ($mode === 'pos' ? ' selected' : '') . '>0</option></select></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">🖨️</button> <a class="csv-btn" href="?r=gardprep&grp=' . $grp . '&mode=' . $mode . '&export=csv">📥 CSV</a></div></form></div>';
  echo '<div class="card"><table><tr><th>#</th><th>رقم الصنف</th><th>الصنف</th><th>الرصيد</th></tr>';
  $n = 1;
  foreach ($rows as $x) echo '<tr><td>' . $n++ . '</td><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td>' . $x['bal'] . '</td></tr>';
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="3">الإجمالي</td><td>' . $tq . '</td></tr>';
  echo '</table><div class="note">عدد الأصناف: ' . count($rows) . '</div></div>';

} elseif ($r === 'gardval' || $r === 'garddetail') {
  $grp = (int)($_GET['grp'] ?? 0);
  $groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
  $fw = $grp > 0 ? "AND c.group_id=$grp" : "";
  $q = "SELECT c.id, c.symbol nm, c.sale_price sale, c.purch_price cost, IFNULL(b.bal,0) bal FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.IS_Deleted=0 $fw HAVING bal<>0 ORDER BY c.id";
  if ($export === 'csv') {
    $rows = $db->query($q)->fetchAll();
    $data = []; $tc = $ts = 0;
    foreach ($rows as $x) { $v1 = $x['bal'] * $x['sale']; $v2 = $x['bal'] * $x['cost']; $tc += $v2; $ts += $v1; $data[] = [$x['id'], $x['nm'], $x['bal'], $x['cost'], round($v2), $x['sale'], round($v1), round($v1 - $v2)]; }
    $data[] = ['الإجمالي', '', '', '', round($tc), '', round($ts), round($ts - $tc)];
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $r . '.csv"'); $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['الكود', 'الصنف', 'الرصيد', 'سعر الشراء', 'قيمة التكلفة', 'سعر البيع', 'قيمة البيع', 'الربح المتوقع']); foreach ($data as $x) fputcsv($o, $x); fclose($o); exit;
  }
  $rows = $db->query($q . " LIMIT $limit")->fetchAll();
  $tc = $ts = 0;
  echo '<div class="card"><form class="row"><input type="hidden" name="r" value="' . e($r) . '"><div><label>بالمجموعة</label><br><select name="grp"><option value="0">الكل</option>';
  foreach ($groups as $g) echo '<option value="' . $g['id'] . '"' . ($g['id'] == $grp ? ' selected' : '') . '>' . e($g['name']) . '</option>';
  echo '</select></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">🖨️</button> <a class="csv-btn" href="?r=' . $r . '&grp=' . $grp . '&export=csv">📥 CSV</a></div></form></div>';
  echo '<div class="card"><table><tr><th>#</th><th>الكود</th><th>الصنف</th><th>الرصيد</th><th>سعر الشراء</th><th>قيمة التكلفة</th><th>سعر البيع</th><th>قيمة البيع</th><th>الربح المتوقع</th></tr>';
  $n = 1;
  foreach ($rows as $x) {
    $v1 = $x['bal'] * $x['sale']; $v2 = $x['bal'] * $x['cost']; $tc += $v2; $ts += $v1;
    echo '<tr><td>' . $n++ . '</td><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td>' . $x['bal'] . '</td><td>' . $x['cost'] . '</td><td>' . number_format($v2) . '</td><td>' . $x['sale'] . '</td><td>' . number_format($v1) . '</td><td class="green"><b>' . number_format($v1 - $v2) . '</b></td></tr>';
  }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="4">الإجمالي</td><td></td><td>' . number_format($tc) . '</td><td></td><td>' . number_format($ts) . '</td><td>' . number_format($ts - $tc) . '</td></tr>';
  echo '</table><div class="note">إجمالي التكلفة: <b>' . number_format($tc) . '</b> — إجمالي البيع: <b>' . number_format($ts) . '</b> — الربح المتوقع: <b>' . number_format($ts - $tc) . '</b> — عدد الأصناف: ' . count($rows) . '</div></div>';

} elseif ($r === 'gard' || $r === 'center') {
  $rows = $db->query("SELECT g.id, g.date d, COUNT(s.GARD_id) n FROM gard g LEFT JOIN gard_sub s ON s.GARD_id=g.id GROUP BY g.id, g.date ORDER BY g.id DESC LIMIT 50")->fetchAll();
  echo '<div class="card"><table><tr><th>ورقة</th><th>التاريخ</th><th>البنود</th></tr>';
  foreach ($rows as $x) echo '<tr><td>' . $x['id'] . '</td><td>' . e($x['d']) . '</td><td>' . $x['n'] . '</td></tr>';
  echo '</table></div>';

} elseif ($r === 'expiry') {
  $before = dt($_GET['before'] ?? '', date('Y-m-d', strtotime('+90 days')));
  $grp = (int)($_GET['grp'] ?? 0);
  $groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
  $fg = $grp > 0 ? "AND c.group_id=$grp" : "";
  $q = "SELECT c.id, c.symbol nm, c.sale_price sale, c.purch_price cost, IFNULL(b.bal,0) bal, (SELECT MIN(s.expire_date) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=1 AND s.expire_date IS NOT NULL AND s.expire_date<='$before') exp FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.IS_Deleted=0 $fg HAVING exp IS NOT NULL AND bal>0 ORDER BY exp";
  if ($export === 'csv') {
    $rows = $db->query($q)->fetchAll();
    $data = []; $tc = 0;
    foreach ($rows as $x) { $v = $x['bal'] * $x['cost']; $tc += $v; $data[] = [$x['id'], $x['nm'], $x['bal'], $x['cost'], round($v), $x['sale'], round($x['bal'] * $x['sale']), $x['exp']]; }
    $data[] = ['الإجمالي', '', '', '', round($tc), '', '', ''];
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="expiry.csv"'); $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['الكود', 'الصنف', 'الرصيد', 'سعر الشراء', 'قيمة التكلفة', 'سعر البيع', 'قيمة البيع', 'تاريخ الصلاحية']); foreach ($data as $x) fputcsv($o, $x); fclose($o); exit;
  }
  $rows = $db->query($q . " LIMIT $limit")->fetchAll();
  $tt = $tv = 0;
  echo '<div class="card"><form class="row"><input type="hidden" name="r" value="expiry"><div><label>تنتهي قبل</label><br><input type="date" name="before" value="' . e($before) . '"></div><div><label>بالمجموعة</label><br><select name="grp"><option value="0">الكل</option>';
  foreach ($groups as $g) echo '<option value="' . $g['id'] . '"' . ($g['id'] == $grp ? ' selected' : '') . '>' . e($g['name']) . '</option>';
  echo '</select></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">🖨️</button> <a class="csv-btn" href="?r=expiry&before=' . $before . '&grp=' . $grp . '&export=csv">📥 CSV</a></div></form></div>';
  echo '<div class="card"><table><tr><th>#</th><th>الكود</th><th>الصنف</th><th>الرصيد</th><th>سعر الشراء</th><th>قيمة التكلفة</th><th>سعر البيع</th><th>قيمة البيع</th><th>الخسارة المحتملة</th><th>تاريخ الصلاحية</th><th>الأيام</th></tr>';
  $n = 1;
  foreach ($rows as $x) {
    $v = $x['bal'] * $x['cost']; $tv += $x['bal'] * $x['sale']; $tt += $v;
    $days = (strtotime($x['exp']) - time()) / 86400;
    $color = $days <= 30 ? 'red' : ($days <= 60 ? 'orange' : 'green');
    echo '<tr><td>' . $n++ . '</td><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td>' . $x['bal'] . '</td><td>' . $x['cost'] . '</td><td>' . number_format($v) . '</td><td>' . $x['sale'] . '</td><td>' . number_format($x['bal'] * $x['sale']) . '</td><td class="red"><b>' . number_format($v) . '</b></td><td style="color:' . $color . '"><b>' . $x['exp'] . '</b></td><td style="color:' . $color . '">' . round($days) . '</td></tr>';
  }
  echo '<tr style="background:#0f766e;color:#fff;font-weight:800"><td colspan="4">الإجمالي</td><td></td><td>' . number_format($tt) . '</td><td></td><td>' . number_format($tv) . '</td><td>' . number_format($tt) . '</td><td colspan="2"></td></tr>';
  echo '</table><div class="note">عدد الأصناف: ' . count($rows) . '</div></div>';
}
layout_close();
