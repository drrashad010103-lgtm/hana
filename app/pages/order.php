<?php
// pages/order.php — غرفة الطلبية الذكية 🧠 (نواقص + مثالي + تصفية إكسبير — grouped by قسم)
// الصيغ: شهري(90ي)=مبيع×30/90، مثالي نواقص=شهري×2 | مثالي عام=شهري(سنة)×تغطية/30
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
function n0($v) { return number_format((float)$v, 0); }
$cov = max(7, min(180, (int)($_GET['cov'] ?? 30)));
// تخصيص الطلبية (حذف/تعديل كمية) — محفوظ في الجلسة
$_SESSION['ord_del'] ??= []; $_SESSION['ord_qty'] ??= [];
if (($_POST['ord_reset'] ?? '') !== '') { $_SESSION['ord_del'] = []; $_SESSION['ord_qty'] = []; }
if (($dd = (int)($_POST['ord_del'] ?? 0)) > 0) $_SESSION['ord_del'][$dd] = 1;
if (($uu = (int)($_POST['ord_undel'] ?? 0)) > 0) unset($_SESSION['ord_del'][$uu]);
foreach ($_POST['qq'] ?? [] as $id => $q) { $id = (int)$id; $q = (float)$q; if ($q <= 0) unset($_SESSION['ord_qty'][$id]); else $_SESSION['ord_qty'][$id] = $q; }
function baltd($b) {
  $b = (float)$b;
  if ($b <= 0) return '<td style="background:#dc2626;color:#fff"><b>' . $b . '</b></td>'; // صفر=أحمر
  if ($b < 2) return '<td style="background:#facc15;color:#000"><b>' . $b . '</b></td>'; // 1=أصفر
  return '<td style="background:#16a34a;color:#fff"><b>' . $b . '</b></td>'; // 2+=أخضر
}
// 1) النواقص: مبيع 90 يوم + رصيد<2
$short = $db->query("SELECT c.id, c.symbol nm, g.name grp, c.purch_price cost, IFNULL(b.bal,0) bal, SUM(s.val) sold FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from LEFT JOIN `groups` g ON g.id=c.group_id LEFT JOIN item_bal b ON b.id=c.id WHERE i.IS_Deleted=0 AND IFNULL(i.IS_Temp,0)=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND c.IS_Deleted=0 AND i.date>=DATE_SUB(NOW(),INTERVAL 90 DAY) GROUP BY c.id HAVING bal<2 AND sold>0 ORDER BY g.name, sold DESC")->fetchAll();
$shortIds = [];
foreach ($short as &$x) { $m = round($x['sold'] * 30 / 90, 1); $x['m'] = $m; $x['ideal'] = ceil($m * 2); $x['ord'] = max(0, $x['ideal'] - $x['bal']); $x['line'] = $x['ord'] * $x['cost']; $shortIds[(int)$x['id']] = 1; }
unset($x);
// 2) تكميل المثالي: مبيع سنة، رصيد>=2، مش في النواقص
$ideal = $db->query("SELECT c.id, c.symbol nm, g.name grp, c.purch_price cost, IFNULL(b.bal,0) bal, SUM(s.val) sold FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from LEFT JOIN `groups` g ON g.id=c.group_id LEFT JOIN item_bal b ON b.id=c.id WHERE i.IS_Deleted=0 AND IFNULL(i.IS_Temp,0)=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND c.IS_Deleted=0 AND i.date>=DATE_SUB(NOW(),INTERVAL 365 DAY) GROUP BY c.id HAVING sold>0 ORDER BY sold DESC LIMIT 600")->fetchAll();
$idealRows = [];
foreach ($ideal as $x) {
  if (isset($shortIds[(int)$x['id']]) || $x['bal'] < 2) continue;
  $m = $x['sold'] / 12; $id2 = ceil($m * $cov / 30); $ord = max(0, $id2 - $x['bal']);
  if ($ord <= 0) continue;
  $x['m'] = round($m, 1); $x['ideal'] = $id2; $x['ord'] = $ord; $x['line'] = $ord * $x['cost'];
  $idealRows[] = $x;
}
// 3) التصفية: صلاحية ≤90 يوم + رصيد>0
$exp = $db->query("SELECT c.id, c.symbol nm, g.name grp, IFNULL(b.bal,0) bal, c.purch_price cost, c.sale_price price, MIN(s.expire_date) exp FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id AND i.proc_type=s.proc_type LEFT JOIN currencies c ON c.id=s.currency_from LEFT JOIN `groups` g ON g.id=c.group_id LEFT JOIN item_bal b ON b.id=c.id WHERE i.IS_Deleted=0 AND IFNULL(i.IS_Temp,0)=0 AND i.IS_Buy=1 AND c.IS_Deleted=0 AND s.expire_date IS NOT NULL AND s.expire_date<=DATE_ADD(CURDATE(),INTERVAL 90 DAY) GROUP BY c.id HAVING bal>0 ORDER BY exp LIMIT 200")->fetchAll();
$D = $_SESSION['ord_del']; $Q = $_SESSION['ord_qty'];
$apply = function ($rows) use ($D, $Q) {
  $out = [];
  foreach ($rows as $x) {
    $id = (int)$x['id'];
    if (isset($D[$id])) continue;
    if (isset($Q[$id])) { $x['ord'] = $Q[$id]; $x['line'] = $Q[$id] * $x['cost']; $x['custom'] = 1; }
    $out[] = $x;
  }
  return $out;
};
$short = $apply($short); $idealRows = $apply($idealRows);
$ndel = count($D);
$ts = array_sum(array_column($short, 'line'));
$ti = array_sum(array_column($idealRows, 'line'));
$te = 0; foreach ($exp as $x) $te += $x['bal'] * $x['cost'];
layout_open('غرفة الطلبية الذكية', '');
echo '<style>@media print{.no-print,aside{display:none!important}main{max-width:100%}}</style>';
echo '<div class="cards" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">'
  . '<div class="card"><b style="font-size:20px">🚨 ' . count($short) . ' صنف ناقص</b><br>تكلفة الطلبية: <b>' . n0($ts) . '</b></div>'
  . '<div class="card"><b style="font-size:20px">📦 ' . count($idealRows) . ' تكميل مثالي</b><br>التكلفة: <b>' . n0($ti) . '</b> (تغطية ' . $cov . ' يوم)</div>'
  . '<div class="card"><b style="font-size:20px">☠️ ' . count($exp) . ' تصفية</b><br>قيمة مهددة: <b style="color:red">' . n0($te) . '</b></div>'
  . '<div class="card"><b>الإجمالي المطلوب: ' . n0($ts + $ti) . '</b><br><span class="no-print"><a href="board.php?t=stock&s=short">النواقص</a> | <a href="board.php?t=stock&s=ideal">المثالي</a></span></div></div>';
echo '<div class="card no-print"><form class="row" method="get"><div><label>تغطية المثالي (يوم)</label><br><input type="number" name="cov" value="' . $cov . '" style="width:90px"></div><div><br><button>حساب</button> <button type="button" onclick="window.print()">🖨️ طباعة الطلبية</button></div></form>'
  . '<form method="post" style="margin-top:8px"><button name="ord_reset" value="1" class="warn">↩️ استرجاع المحذوف ( ' . $ndel . ' ) وتصفير التعديلات</button></form></div>';
function ptable($title, $rows, $showM) {
  usort($rows, fn($a, $b) => (float)$a['bal'] <=> (float)$b['bal']); // ترتيب بالرصيد تصاعدي
  echo '<div class="card"><form method="post"><h3>' . $title . ' (' . count($rows) . ')</h3><table><tr><th>القسم</th><th>الصنف</th><th>مبيع</th><th>الرصيد</th><th>شهري</th><th>مثالي</th><th>اطلب (عدّل)</th><th>التكلفة</th><th class="no-print">✕</th></tr>';
  foreach ($rows as $x) {
    echo '<tr><td>' . e($x['grp'] ?? '') . '</td><td>' . e($x['nm']) . (!empty($x['custom']) ? ' ✏️' : '') . '</td><td>' . $x['sold'] . '</td>' . baltd($x['bal']) . '<td>' . $x['m'] . '</td><td style="background:#fff"><b>' . $x['ideal'] . '</b></td>'
      . '<td><input type="number" name="qq[' . (int)$x['id'] . ']" value="' . $x['ord'] . '" min="0" step="any" style="width:70px"></td><td>' . n0($x['line']) . '</td>'
      . '<td class="no-print"><button name="ord_del" value="' . (int)$x['id'] . '" title="حذف من الطلبية" style="background:#dc2626;padding:4px 10px">✕</button></td></tr>';
  }
  echo '</table><br><button class="no-print">💾 حفظ الكميات المعدلة</button></form></div>';
}
$delIds = array_keys($_SESSION['ord_del']);
$delNames = [];
if ($delIds) {
  $in = implode(',', array_map('intval', array_slice($delIds, 0, 500)));
  try { foreach ($db->query("SELECT id, symbol nm FROM currencies WHERE id IN ($in)")->fetchAll() as $r) $delNames[(int)$r['id']] = $r['nm']; } catch (Throwable $t) {}
  foreach ($delIds as $id) if (!isset($delNames[(int)$id])) $delNames[(int)$id] = 'صنف ' . (int)$id;
}
echo '<div class="card"><h3>🗑️ الأصناف المحذوفة من الطلبية (' . count($delIds) . ')</h3>';
if (!$delIds) echo '<div class="note">مفيش محذوفات — كل الأصناف في الطلبية.</div>';
else {
  echo '<table><tr><th>الكود</th><th>الصنف</th><th class="no-print"></th></tr>';
  foreach ($delNames as $id => $nm) echo '<tr><td>' . $id . '</td><td>' . e($nm) . '</td><td class="no-print"><form method="post" style="margin:0"><button name="ord_undel" value="' . $id . '">↩️ استرجاع</button></form></td></tr>';
  echo '</table>';
}
echo '</div>';
ptable('🚨 طلبية النواقص — شهري=مبيع 90يوم×30/90، مثالي=شهري×2', $short, true);
ptable('📦 تكميل المثالي — شهري=مبيع سنة/12، مثالي=شهري×تغطية/30', $idealRows, true);
echo '<div class="card"><h3>☠️ التصفية العاجلة — بيعها قبل ما تموت</h3><table><tr><th>القسم</th><th>الصنف</th><th>الرصيد</th><th>الصلاحية</th><th>التكلفة المهددة</th><th>بسعر البيع</th></tr>';
foreach ($exp as $x) echo '<tr><td>' . e($x['grp'] ?? '') . '</td><td>' . e($x['nm']) . '</td>' . baltd($x['bal']) . '<td>' . $x['exp'] . '</td><td style="color:red">' . n0($x['bal'] * $x['cost']) . '</td><td>' . n0($x['bal'] * $x['price']) . '</td></tr>';
echo '</table><div class="note">لو الصنف مات = خسارة التكلفة. اعمل عليه عرض أو بونص.</div></div>';
layout_close();
