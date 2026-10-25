<?php
// pages/board.php — لوحة الداشبورد الحية (نسخة rs_own طبق الأصل من 8765)
// الأقسام: اليوم | المبيعات والأرباح | المخزون | صافي الشهور — نفس الصيغ: الإيراد من الرؤوس، الربح = إيراد − تكلفة معاصرة (آخر شراء ≤ تاريخ البيع)
require __DIR__ . '/../lib/db.php';
$db = db();
function q1($sql) { try { $r = db()->query($sql)->fetch(); return $r ?: []; } catch (Throwable $t) { return []; } }
function qq($sql) { try { return db()->query($sql)->fetchAll(); } catch (Throwable $t) { return []; } }
function n0($v) { return number_format((float)$v, 0); }
function gr($g) {
  if ($g === null || $g === '') return '<span style="color:#64748b">—</span>';
  $v = (float)$g; $c = $v >= 0 ? '#16a34a' : '#dc2626';
  return '<b style="color:' . $c . '">' . ($v >= 0 ? '+' : '') . $g . '%</b>';
}
// التكلفة المعاصرة لسطر بيع s في فاتورة i (آخر شراء قبل البيع — نفس صيغة الحية)
define('COST', "(SELECT b.exchange_price-IFNULL(b.discount,0) FROM inv_sub b JOIN inv ib ON ib.proc_id=b.proc_id WHERE b.currency_from=s.currency_from AND ib.IS_Deleted=0 AND ib.IS_Buy=1 AND ib.proc_type IN (1,3) AND ib.date<=i.date ORDER BY ib.date DESC LIMIT 1)");
function summ($f, $t) {
  return q1("SELECT SUM(IF(IS_Buy=0 AND proc_type=1,tot_net,0))-SUM(IF(IS_Buy=0 AND proc_type=2,tot_net,0)) s, COUNT(DISTINCT IF(IS_Buy=0 AND proc_type=1,proc_id,NULL)) n, SUM(IF(IS_Buy=1 AND proc_type=1,tot_net,0)) b FROM inv WHERE IS_Deleted=0 AND DATE(date) BETWEEN '$f' AND '$t'");
}
function units($f, $t) {
  $r = q1("SELECT SUM(s.val) u FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND DATE(i.date) BETWEEN '$f' AND '$t'");
  return $r['u'] ?? 0;
}
function profit($f, $t) {
  return q1("SELECT SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, SUM(s.val*" . COST . ") cost FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND DATE(i.date) BETWEEN '$f' AND '$t'");
}
// ربح سريع Oracle-style: آخر سعر شراء حتى نهاية الفترة (ROW_NUMBER) — للمدد الطويلة، مع fallback تلقائي
function pfast($f, $t) {
  try {
    return db()->query("WITH lb AS (SELECT b.currency_from item, (b.exchange_price-IFNULL(b.discount,0)) price, ROW_NUMBER() OVER (PARTITION BY b.currency_from ORDER BY ib.date DESC) rn FROM inv_sub b JOIN inv ib ON ib.proc_id=b.proc_id WHERE ib.IS_Deleted=0 AND ib.IS_Buy=1 AND ib.proc_type IN (1,3) AND ib.date<='$t 23:59:59') SELECT SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, SUM(s.val*IFNULL(lb.price,s.exchange_price)) cost FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN lb ON lb.item=s.currency_from AND lb.rn=1 WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND DATE(i.date) BETWEEN '$f' AND '$t'")->fetch() ?: [];
  } catch (Throwable $e) { return profit($f, $t); }
}
function pauto($f, $t) {
  try { $days = (strtotime($t) - strtotime($f)) / 86400; } catch (Throwable $e) { $days = 0; }
  return $days > 93 ? pfast($f, $t) : profit($f, $t);
}
$t = $_GET['t'] ?? 'home';
$today = date('Y-m-d'); $m1 = date('Y-m-01');
$u = ($_SESSION['u'] ?? null);
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>الداشبورد الحية — النسخة الحرة</title>
<style>
*{box-sizing:border-box}body{font-family:Tahoma,Arial;margin:0;background:#0f172a;color:#e2e8f0}
header{background:#1e293b;padding:10px 14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;position:sticky;top:0;z-index:5}
header b{color:#fff}header a{color:#e2e8f0;text-decoration:none;background:#334155;padding:7px 13px;border-radius:8px;font-size:13px}
header a.on{background:#0f766e;color:#fff;font-weight:800}
main{padding:14px;max-width:1150px;margin:auto}
.cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:13px 15px;flex:1;min-width:190px}
.card .t{font-size:12px;color:#94a3b8}.card .v{font-size:23px;font-weight:800;color:#4ade80}
.card .s{font-size:12px;color:#cbd5e1}
table{width:100%;border-collapse:collapse;font-size:13px;background:#1e293b}td,th{border:1px solid #334155;padding:6px 8px;text-align:right}th{background:#0f766e;color:#fff}
.sub{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}.sub a{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:8px 13px;color:#e2e8f0;text-decoration:none;font-size:13px}.sub a.on{background:#7c3aed;color:#fff;font-weight:800}
form.f{display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-bottom:12px;background:#1e293b;padding:10px;border-radius:10px}
input,select{background:#0f172a;color:#fff;border:1px solid #475569;border-radius:8px;padding:8px 10px;font-family:inherit}
button{background:#0f766e;color:#fff;border:none;border-radius:8px;padding:9px 16px;font-family:inherit;font-weight:700;cursor:pointer}
.note{font-size:12px;color:#94a3b8;margin:8px 0}
h3{color:#fff}
@media(max-width:760px){header{overflow-x:auto;flex-wrap:nowrap}header a{white-space:nowrap}.cards{flex-direction:column}.card{min-width:0}table{display:block;overflow-x:auto;white-space:nowrap}main{padding:10px}}
</style></head>
<body>
<header><b>📊 الداشبورد الحية</b>
<a class="<?=$t==='home'?'on':''?>" href="?t=home">اليوم</a>
<a class="<?=$t==='sales'?'on':''?>" href="?t=sales">المبيعات والأرباح</a>
<a class="<?=$t==='stock'?'on':''?>" href="?t=stock">المخزون 📦</a>
<a class="<?=$t==='monthly'?'on':''?>" href="?t=monthly">صافي الشهور 📅</a>
<span style="margin-right:auto"></span>
<a href="main.php">القوائم</a><a href="sale_rs.php">بيع</a><a href="logout.php">خروج</a></header>
<main>
<?php if ($t === 'home'):
  $td = summ($today, $today); $tu = units($today, $today); $tp = profit($today, $today);
  $mo = summ($m1, $today); $mu = units($m1, $today); $mp = profit($m1, $today);
  $pm1 = date('Y-m-01', strtotime('-1 year')); $ptd = date('Y-m-d', strtotime('-1 year'));
  $pv = summ($pm1, $ptd); $pp = profit($pm1, $ptd);
  $gs = ($pv['s'] ?? 0) == 0 ? null : round((($mo['s'] ?? 0) - $pv['s']) / abs($pv['s']) * 100, 1);
  $gp = (($pp['rev'] ?? 0) - ($pp['cost'] ?? 0)) == 0 ? null : round(((($mp['rev'] ?? 0) - ($mp['cost'] ?? 0)) - (($pp['rev'] ?? 0) - ($pp['cost'] ?? 0))) / abs(($pp['rev'] ?? 0) - ($pp['cost'] ?? 0)) * 100, 1);
  $st = q1("SELECT SUM(b.bal*c.purch_price) vc, SUM(b.bal*c.sale_price) vs, SUM(IF(b.bal<=0,1,0)) zc FROM item_bal b JOIN currencies c ON c.id=b.id WHERE b.bal>0");
  $zc = q1("SELECT COUNT(*) n FROM item_bal WHERE bal<=0");
  $sh = q1("SELECT COUNT(*) n FROM item_bal b WHERE b.bal<2 AND EXISTS (SELECT 1 FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=b.id AND i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND i.date>=DATE_SUB(NOW(),INTERVAL 90 DAY))");
  $ex = q1("SELECT COUNT(DISTINCT s.currency_from) n FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN item_bal b ON b.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=1 AND s.expire_date IS NOT NULL AND s.expire_date<=DATE_ADD(CURDATE(),INTERVAL 90 DAY) AND IFNULL(b.bal,0)>0");
?>
<div class="cards">
<div class="card"><div class="t">📅 مبيعات اليوم (<?=$today?>)</div><div class="v"><?=n0($td['s'])?></div><div class="s">فواتير <?=n0($td['n'])?> | وحدات <?=n0($tu)?> | متوسط <?=($td['n'] ? n0($td['s'] / $td['n']) : 0)?></div><div class="s">ربح: <b style="color:#4ade80"><?=n0(($tp['rev'] ?? 0) - ($tp['cost'] ?? 0))?></b> | مشتريات <b style="color:#f87171"><?=n0($td['b'])?></b></div></div>
<div class="card"><div class="t">📅 الشهر (من <?=$m1?>)</div><div class="v"><?=n0($mo['s'])?></div><div class="s">فواتير <?=n0($mo['n'])?> | وحدات <?=n0($mu)?></div><div class="s">ربح: <b style="color:#4ade80"><?=n0(($mp['rev'] ?? 0) - ($mp['cost'] ?? 0))?></b> | مشتريات <b style="color:#f87171"><?=n0($mo['b'])?></b></div></div>
<div class="card"><div class="t">⚖️ نفس الفترة العام الماضي</div><div class="v" style="color:#e2e8f0"><?=n0($pv['s'])?></div><div class="s">نمو المبيعات <?=gr($gs)?> | نمو الربح <?=gr($gp)?></div></div>
</div>
<div class="cards">
<div class="card"><div class="t">📦 المخزون الدفتري</div><div class="v"><?=n0($st['vc'])?></div><div class="s">الاستبدال (بسعر البيع): <?=n0($st['vs'])?> | أصناف صفر/سالب: <?=n0($zc['n'])?></div></div>
<div class="card"><div class="t">🚨 تنبيهات</div><div class="s">نواقص تحتاج طلبية: <b><?=n0($sh['n'])?></b> (<a href="?t=stock&s=short" style="color:#4ade80">الطلبية</a>)</div><div class="s">أصناف قرب إكسبير (90 يوم): <b><?=n0($ex['n'])?></b> (<a href="?t=stock&s=expiry" style="color:#4ade80">الإكسبير</a>)</div></div>
</div>
<?php elseif ($t === 'sales'):
  $f = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days')); $t2 = $_GET['to'] ?? $today;
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) $f = date('Y-m-d', strtotime('-30 days'));
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $t2)) $t2 = $today;
  $sm = summ($f, $t2); $su = units($f, $t2); $sp = pauto($f, $t2);
  $daily = qq("SELECT DATE(i.date) d, SUM(s.val*s.exchange_price-IFNULL(s.discount,0)) rev, SUM(s.val*" . COST . ") cost FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND DATE(i.date) BETWEEN '$f' AND '$t2' GROUP BY DATE(i.date) ORDER BY d");
  $last = qq("SELECT id, DATE(date) d, tot_net net, paid, proc_type pt FROM inv WHERE IS_Deleted=0 AND IS_Buy=0 AND proc_type IN (1,2) ORDER BY date DESC LIMIT 15");
  $tops = qq("WITH lb AS (SELECT b.currency_from item, (b.exchange_price-IFNULL(b.discount,0)) price, ROW_NUMBER() OVER (PARTITION BY b.currency_from ORDER BY ib.date DESC) rn FROM inv_sub b JOIN inv ib ON ib.proc_id=b.proc_id WHERE ib.IS_Deleted=0 AND ib.IS_Buy=1 AND ib.proc_type IN (1,3) AND ib.date<='$t2 23:59:59') SELECT c.symbol nm, SUM(s.val) q, SUM(s.val*s.exchange_price) rev, SUM(s.val*(s.exchange_price-IFNULL(lb.price,s.exchange_price))) prof FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN lb ON lb.item=s.currency_from AND lb.rn=1 LEFT JOIN currencies c ON c.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND DATE(i.date) BETWEEN '$f' AND '$t2' GROUP BY c.symbol ORDER BY rev DESC LIMIT 15");
  $pnl = qq("SELECT DATE_FORMAT(date,'%Y-%m') m, SUM(IF(IS_Buy=0 AND proc_type=1,tot_net,0))-SUM(IF(IS_Buy=0 AND proc_type=2,tot_net,0)) sales, SUM(IF(IS_Buy=1 AND proc_type=1,tot_net,0)) purch, COUNT(DISTINCT IF(IS_Buy=0 AND proc_type=1,proc_id,NULL)) n FROM inv WHERE IS_Deleted=0 GROUP BY DATE_FORMAT(date,'%Y-%m') ORDER BY m DESC LIMIT 24");
?>
<form class="f" method="get"><input type="hidden" name="t" value="sales"><label>من <input type="date" name="from" value="<?=e($f)?>"></label><label>إلى <input type="date" name="to" value="<?=e($t2)?>"></label><button>عرض</button>
<a href="?t=sales&from=<?=$today?>&to=<?=$today?>" style="color:#4ade80">اليوم</a><a href="?t=sales&from=<?=date('Y-m-d', strtotime('-7 days'))?>&to=<?=$today?>" style="color:#4ade80">7 أيام</a><a href="?t=sales&from=<?=date('Y-m-d', strtotime('-30 days'))?>&to=<?=$today?>" style="color:#4ade80">30 يوم</a><a href="?t=sales&from=<?=date('Y-m-d', strtotime('-365 days'))?>&to=<?=$today?>" style="color:#4ade80">سنة</a></form>
<div class="cards">
<div class="card"><div class="t">الفترة <?=$f?> ← <?=$t2?></div><div class="v"><?=n0($sm['s'])?></div><div class="s">فواتير <?=n0($sm['n'])?> | وحدات <?=n0($su)?> | متوسط <?=($sm['n'] ? n0($sm['s'] / $sm['n']) : 0)?></div><div class="s">إيراد <?=n0($sp['rev'])?> − تكلفة <?=n0($sp['cost'])?> = ربح <b style="color:#4ade80"><?=n0(($sp['rev'] ?? 0) - ($sp['cost'] ?? 0))?></b></div><div class="s">مشتريات <b style="color:#f87171"><?=n0($sm['b'])?></b></div></div>
</div>
<h3>📈 الربح اليومي</h3>
<table><tr><th>التاريخ</th><th>إيراد</th><th>تكلفة</th><th>ربح</th></tr>
<?php foreach ($daily as $x): ?><tr><td><?=$x['d']?></td><td><?=n0($x['rev'])?></td><td><?=n0($x['cost'])?></td><td><b style="color:#4ade80"><?=n0($x['rev'] - $x['cost'])?></b></td></tr><?php endforeach; ?></table>
<h3>🧾 آخر 15 فاتورة</h3>
<table><tr><th>فاتورة</th><th>التاريخ</th><th>النوع</th><th>الصافي</th><th>المدفوع</th></tr>
<?php foreach ($last as $x): ?><tr><td><?=$x['id']?></td><td><?=$x['d']?></td><td><?=($x['pt'] == 2 ? 'مرتجع' : 'بيع')?></td><td><?=n0($x['net'])?></td><td><?=n0($x['paid'])?></td></tr><?php endforeach; ?></table>
<h3>🏆 الأعلى إيراداً (الفترة)</h3>
<table><tr><th>الصنف</th><th>كمية</th><th>إيراد</th><th>ربح</th></tr>
<?php foreach ($tops as $x): ?><tr><td><?=e($x['nm'])?></td><td><?=$x['q']?></td><td><?=n0($x['rev'])?></td><td><?=n0($x['prof'])?></td></tr><?php endforeach; ?></table>
<h3>📅 شهريات (إيراد/مشتريات)</h3>
<table><tr><th>الشهر</th><th>مبيعات</th><th>مشتريات</th><th>فواتير</th></tr>
<?php foreach ($pnl as $x): ?><tr><td><?=$x['m']?></td><td><?=n0($x['sales'])?></td><td><?=n0($x['purch'])?></td><td><?=$x['n']?></td></tr><?php endforeach; ?></table>
<?php elseif ($t === 'stock'):
  $s = $_GET['s'] ?? 'tops';
  echo '<div class="sub"><a href="order.php">🧠 غرفة الطلبية</a><a class="' . ($s === 'tops' ? 'on' : '') . '" href="?t=stock&s=tops">🏆 توب مبيعات</a><a class="' . ($s === 'profit' ? 'on' : '') . '" href="?t=stock&s=profit">💰 توب أرباح</a><a class="' . ($s === 'dead' ? 'on' : '') . '" href="?t=stock&s=dead">💤 الرواكد</a><a class="' . ($s === 'short' ? 'on' : '') . '" href="?t=stock&s=short">🚨 النواقص</a><a class="' . ($s === 'predict' ? 'on' : '') . '" href="?t=stock&s=predict">🔮 تنبؤ ذكي</a><a class="' . ($s === 'ideal' ? 'on' : '') . '" href="?t=stock&s=ideal">📦 المثالي</a><a class="' . ($s === 'search' ? 'on' : '') . '" href="?t=stock&s=search">🔍 بحث</a><a class="' . ($s === 'expiry' ? 'on' : '') . '" href="?t=stock&s=expiry">☠️ إكسبير</a></div>';
  if ($s === 'tops' || $s === 'profit') {
    $ob = $s === 'profit' ? 'prof' : 'q';
    $rows = qq("WITH lb AS (SELECT b.currency_from item, (b.exchange_price-IFNULL(b.discount,0)) price, ROW_NUMBER() OVER (PARTITION BY b.currency_from ORDER BY ib.date DESC) rn FROM inv_sub b JOIN inv ib ON ib.proc_id=b.proc_id WHERE ib.IS_Deleted=0 AND ib.IS_Buy=1 AND ib.proc_type IN (1,3)) SELECT c.symbol nm, SUM(s.val) q, SUM(s.val*s.exchange_price) rev, SUM(s.val*(s.exchange_price-IFNULL(lb.price,s.exchange_price))) prof FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN lb ON lb.item=s.currency_from AND lb.rn=1 LEFT JOIN currencies c ON c.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2 AND i.date>=DATE_SUB(NOW(),INTERVAL 365 DAY) GROUP BY c.symbol ORDER BY $ob DESC LIMIT 100");
    echo '<table><tr><th>الصنف</th><th>كمية (سنة)</th><th>إيراد</th><th>ربح</th></tr>';
    foreach ($rows as $x) echo '<tr><td>' . e($x['nm']) . '</td><td>' . $x['q'] . '</td><td>' . n0($x['rev']) . '</td><td>' . n0($x['prof']) . '</td></tr>';
    echo '</table>';
  } elseif ($s === 'dead') {
    $rows = qq("SELECT c.id, c.symbol nm, b.bal, c.sale_price price, (SELECT MAX(i2.date) FROM inv_sub s2 JOIN inv i2 ON i2.proc_id=s2.proc_id WHERE s2.currency_from=c.id AND i2.IS_Deleted=0 AND i2.IS_Buy=0 AND i2.proc_type=1) lastS, (SELECT MAX(i3.date) FROM inv_sub s3 JOIN inv i3 ON i3.proc_id=s3.proc_id WHERE s3.currency_from=c.id AND i3.IS_Deleted=0 AND i3.IS_Buy=1 AND i3.proc_type IN (1,3)) lastB FROM currencies c JOIN item_bal b ON b.id=c.id WHERE b.bal>0 HAVING (lastS IS NULL OR lastS<DATE_SUB(NOW(),INTERVAL 365 DAY)) AND (lastB IS NULL OR lastB<DATE_SUB(NOW(),INTERVAL 180 DAY)) ORDER BY b.bal DESC LIMIT 100");
    echo '<div class="note">راكد = رصيد موجب + آخر بيع من سنة + آخر دخول من 6 شهور</div><table><tr><th>الكود</th><th>الصنف</th><th>الرصيد</th><th>السعر</th><th>آخر بيع</th><th>آخر دخول</th></tr>';
    foreach ($rows as $x) echo '<tr><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td>' . $x['bal'] . '</td><td>' . $x['price'] . '</td><td>' . ($x['lastS'] ?? '—') . '</td><td>' . ($x['lastB'] ?? '—') . '</td></tr>';
    echo '</table>';
  } elseif ($s === 'short') {
    $rows = qq("SELECT c.id, c.symbol nm, c.sale_price price, c.purch_price cost, IFNULL(b.bal,0) bal, SUM(IF(i.IS_Buy=0 AND i.proc_type=1,s.val,0)) sold FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from LEFT JOIN item_bal b ON b.id=c.id WHERE i.IS_Deleted=0 AND i.date>=DATE_SUB(NOW(),INTERVAL 90 DAY) GROUP BY c.id HAVING bal<2 AND sold>0 ORDER BY sold DESC LIMIT 200");
    echo '<div class="note">شهري = مبيع 90 يوم × 30/90 | مثالي = شهري × 2 | اطلب = مثالي − رصيد</div><table><tr><th>الصنف</th><th>مبيع 90ي</th><th>الرصيد</th><th>شهري</th><th>مثالي</th><th>اطلب</th><th>التكلفة</th></tr>';
    foreach ($rows as $x) { $m = round($x['sold'] * 30 / 90); $id2 = ceil($m * 2); $ord = max(0, $id2 - $x['bal']); echo '<tr><td>' . e($x['nm']) . '</td><td>' . $x['sold'] . '</td><td>' . $x['bal'] . '</td><td>' . $m . '</td><td>' . $id2 . '</td><td><b>' . $ord . '</b></td><td>' . n0($ord * $x['cost']) . '</td></tr>'; }
    echo '</table>';
  } elseif ($s === 'predict') {
    // 🔮 التنبؤ الذكي — متوسط البيع اليومي + أيام متبقية + كمية طلب مقترحة
    $days = max(30, min(730, (int)($_GET['d'] ?? 365)));
    $rows = qq("SELECT c.id, c.symbol nm, c.purch_price cost, IFNULL(b.bal,0) bal,
      SUM(s.val) total_qty,
      ROUND(SUM(s.val)/$days, 3) avg_day,
      ROUND(IFNULL(b.bal,0)/NULLIF(SUM(s.val)/$days,0), 1) days_left,
      ROUND((SUM(s.val)/$days)*30 - IFNULL(b.bal,0), 0) suggest_qty
    FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id
    LEFT JOIN currencies c ON c.id=s.currency_from
    LEFT JOIN item_bal b ON b.id=c.id
    WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND s.proc_type=2
      AND i.date>=DATE_SUB(NOW(), INTERVAL $days DAY)
      AND (c.IS_Deleted=0 OR c.IS_Deleted IS NULL)
    GROUP BY c.id, c.symbol, c.purch_price, b.bal
    HAVING total_qty >= 5
    ORDER BY avg_day DESC");
    $red = []; $yellow = []; $okc = 0;
    foreach ($rows as $x) {
      $dl = $x['days_left'] !== null ? (float)$x['days_left'] : 9999;
      if ($x['bal'] <= 0 || $dl <= 7) $red[] = $x;
      elseif ($dl <= 30) $yellow[] = $x;
      else $okc++;
    }
    echo '<div class="cards">';
    echo '<div class="card"><div class="t">🔴 هينفد خلال أسبوع</div><div class="v" style="color:#f87171">' . count($red) . '</div><div class="s">يحتاج طلب فوري</div></div>';
    echo '<div class="card"><div class="t">🟡 هينفد خلال شهر</div><div class="v" style="color:#fbbf24">' . count($yellow) . '</div><div class="s">يحتاج مراجعة</div></div>';
    echo '<div class="card"><div class="t">✅ المخزون سليم</div><div class="v">' . $okc . '</div><div class="s">أكثر من 30 يوم</div></div>';
    echo '</div>';
    echo '<form class="f" method="get"><input type="hidden" name="t" value="stock"><input type="hidden" name="s" value="predict"><label>فترة التحليل (يوم) <input type="number" name="d" value="' . $days . '" style="width:90px" min="30" max="730"></label><button>حساب</button><span style="margin-right:auto"></span><button type="button" onclick="exportWA(\'red\')" style="background:#16a34a">📱 إرسال قائمة الطلب (واتساب)</button></form>';
    echo '<h3>🔴 طلب فوري (' . count($red) . ')</h3>';
    echo '<table><tr><th>#</th><th>الصنف</th><th>الرصيد</th><th>متوسط/يوم</th><th>يكفي (يوم)</th><th>كمية مقترحة</th><th>تكلفة</th></tr>';
    $n = 1;
    foreach ($red as $x) {
      $dl = $x['days_left'] ?? '∞';
      $sq = max(1, $x['suggest_qty']);
      echo '<tr data-nm="' . e($x['nm']) . '" data-q="' . $sq . '"><td>' . $n++ . '</td><td><b>' . e($x['nm']) . '</b></td><td style="color:#f87171">' . ($x['bal'] <= 0 ? 'خلص' : $x['bal']) . '</td><td>' . $x['avg_day'] . '</td><td>' . $dl . '</td><td><b>' . $sq . '</b></td><td>' . n0($sq * $x['cost']) . '</td></tr>';
    }
    echo '</table>';
    echo '<h3>🟡 مراجعة خلال شهر (' . count($yellow) . ')</h3>';
    echo '<table><tr><th>#</th><th>الصنف</th><th>الرصيد</th><th>متوسط/يوم</th><th>يكفي (يوم)</th><th>كمية مقترحة</th><th>تكلفة</th></tr>';
    $n = 1;
    foreach ($yellow as $x) {
      echo '<tr data-nm="' . e($x['nm']) . '" data-q="' . $x['suggest_qty'] . '"><td>' . $n++ . '</td><td>' . e($x['nm']) . '</td><td style="color:#fbbf24">' . $x['bal'] . '</td><td>' . $x['avg_day'] . '</td><td>' . $x['days_left'] . '</td><td><b>' . $x['suggest_qty'] . '</b></td><td>' . n0($x['suggest_qty'] * $x['cost']) . '</td></tr>';
    }
    echo '</table>';
    echo '<script>function exportWA(tbl){var rows=document.querySelectorAll("#tbl"+tbl+" tr[data-nm]");if(!rows.length)rows=document.querySelectorAll("tr[data-nm]");var msg="📋 *طلب شراء مقترح — تنبؤ المخزون*\\n\\n";var n=1;rows.forEach(function(r){msg+=n+". "+r.dataset.nm+" — "+r.dataset.q+" قطعة\\n";n++;});msg+="\\n(محسوب تلقائياً من متوسط البيع)";window.open("https://wa.me/?text="+encodeURIComponent(msg),"_blank");}</script>';
  } elseif ($s === 'ideal') {
    $cov = max(7, min(180, (int)($_GET['cov'] ?? 30)));
    $rows = qq("SELECT c.id, c.symbol nm, c.sale_price price, c.purch_price cost, IFNULL(b.bal,0) bal, SUM(IF(i.IS_Buy=0 AND i.proc_type=1,s.val,0)) sold FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from LEFT JOIN item_bal b ON b.id=c.id WHERE i.IS_Deleted=0 AND i.date>=DATE_SUB(NOW(),INTERVAL 365 DAY) GROUP BY c.id HAVING sold>0 ORDER BY sold DESC LIMIT 300");
    echo '<form class="f" method="get"><input type="hidden" name="t" value="stock"><input type="hidden" name="s" value="ideal"><label>تغطية (يوم) <input type="number" name="cov" value="' . $cov . '" style="width:80px"></label><button>حساب</button></form><table><tr><th>الصنف</th><th>مبيع سنة</th><th>شهري</th><th>الرصيد</th><th>مثالي (' . $cov . 'ي)</th><th>اطلب</th></tr>';
    $tc = 0;
    foreach ($rows as $x) { $m = $x['sold'] / 12; $id2 = ceil($m * $cov / 30); $ord = max(0, $id2 - $x['bal']); if ($ord <= 0) continue; $tc += $ord * $x['cost']; echo '<tr><td>' . e($x['nm']) . '</td><td>' . $x['sold'] . '</td><td>' . round($m, 1) . '</td><td>' . $x['bal'] . '</td><td>' . $id2 . '</td><td><b>' . $ord . '</b></td></tr>'; }
    echo '</table><div class="note">إجمالي تكلفة الطلبية المثالية: <b>' . n0($tc) . '</b></div>';
  } elseif ($s === 'search') {
    $q = trim($_GET['q'] ?? '');
    echo '<form class="f" method="get"><input type="hidden" name="t" value="stock"><input type="hidden" name="s" value="search"><input name="q" value="' . e($q) . '" placeholder="كود / باركود / اسم" style="width:260px"><button>بحث</button></form>';
    if ($q !== '') {
      $like = '%' . str_replace(['%', '_'], '', $q) . '%';
      $st = db()->prepare("SELECT id, symbol nm, sale_price, purch_price FROM currencies WHERE (IS_Deleted=0 OR IS_Deleted IS NULL) AND (CAST(id AS CHAR)=? OR barcode=? OR symbol LIKE ?) LIMIT 20");
      try { $st->execute([$q, $q, $like]); $items = $st->fetchAll(); } catch (Throwable $t) { $items = []; }
      foreach ($items as $it) {
        $id = (int)$it['id'];
        $mv = q1("SELECT SUM(IF(i.IS_Buy=0 AND i.proc_type=1,s.val,0)) sold, SUM(IF(i.IS_Buy=0 AND i.proc_type=1,s.val*s.exchange_price,0)) soldV, SUM(IF(i.IS_Buy=0 AND i.proc_type=2,s.val,0)) retS, SUM(IF(i.IS_Buy=1 AND i.proc_type IN (1,3),s.val,0)) bought FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=$id AND i.IS_Deleted=0");
        $bal = q1("SELECT IFNULL(bal,0) b FROM item_bal WHERE id=$id");
        $last = q1("SELECT MAX(i.date) d FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=$id AND i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1");
        echo '<div class="card"><div class="t">' . $id . ' — ' . e($it['nm']) . '</div><div class="s">رصيد: <b>' . ($bal['b'] ?? 0) . '</b> | مبيع: ' . n0($mv['sold'] ?? 0) . ' (' . n0($mv['soldV'] ?? 0) . ') | مرتجع بيع: ' . n0($mv['retS'] ?? 0) . ' | مشتريات: ' . n0($mv['bought'] ?? 0) . ' | بيع: ' . $it['sale_price'] . ' | شراء: ' . $it['purch_price'] . ' | آخر بيع: ' . ($last['d'] ?? '—') . '</div></div>';
      }
    }
  } else {
    $rows = qq("SELECT c.id, c.symbol nm, IFNULL(b.bal,0) bal, c.purch_price cost, MIN(s.expire_date) exp FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from LEFT JOIN item_bal b ON b.id=c.id WHERE i.IS_Deleted=0 AND i.IS_Buy=1 AND s.expire_date IS NOT NULL AND s.expire_date<=DATE_ADD(CURDATE(),INTERVAL 90 DAY) GROUP BY c.id HAVING bal>0 ORDER BY exp LIMIT 200");
    echo '<div class="note">أصناف برصيد موجب وصلاحية تنتهي خلال 90 يوم</div><table><tr><th>الكود</th><th>الصنف</th><th>الرصيد</th><th>الصلاحية</th><th>التكلفة</th></tr>';
    foreach ($rows as $x) echo '<tr><td>' . $x['id'] . '</td><td>' . e($x['nm']) . '</td><td>' . $x['bal'] . '</td><td>' . $x['exp'] . '</td><td>' . n0($x['bal'] * $x['cost']) . '</td></tr>';
    echo '</table>';
  }
elseif ($t === 'monthly'):
  try {
    db()->exec("CREATE TEMPORARY TABLE _avg AS SELECT s.currency_from item, SUM(s.val*s.exchange_price-IFNULL(s.discount,0))/NULLIF(SUM(s.val),0) avgc FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE i.IS_Deleted=0 AND i.IS_Buy=1 AND i.proc_type IN (1,3) GROUP BY s.currency_from");
    try { db()->exec("ALTER TABLE _avg ADD PRIMARY KEY (item)"); } catch (Throwable $t) {}
  } catch (Throwable $t) {}
  $rows = qq("SELECT DATE_FORMAT(i.date,'%Y-%m') m, SUM(IF(i.IS_Buy=0 AND i.proc_type=1,s.val*s.exchange_price-IFNULL(s.discount,0),0)) rev, SUM(IF(i.IS_Buy=0 AND i.proc_type=1,s.val*IFNULL(a.avgc,c.purch_price),0)) cogs, COUNT(DISTINCT IF(i.IS_Buy=0 AND i.proc_type=1,i.proc_id,NULL)) n FROM inv i JOIN inv_sub s ON s.proc_id=i.proc_id AND s.proc_type=2 LEFT JOIN _avg a ON a.item=s.currency_from LEFT JOIN currencies c ON c.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 GROUP BY DATE_FORMAT(i.date,'%Y-%m') ORDER BY m DESC LIMIT 36");
  echo '<div class="note">التكلفة هنا بالمتوسط (المعاصرة للفترات المحددة في تبويب المبيعات) — من أول 2023-09</div><table><tr><th>الشهر</th><th>إيراد</th><th>تكلفة</th><th>مجمل</th><th>هامش</th><th>فواتير</th></tr>';
  foreach ($rows as $x) { $g = $x['rev'] - $x['cogs']; $mg = $x['rev'] ? round($g / $x['rev'] * 100, 1) : 0; echo '<tr><td>' . $x['m'] . '</td><td>' . n0($x['rev']) . '</td><td>' . n0($x['cogs']) . '</td><td><b>' . n0($g) . '</b></td><td>' . $mg . '%</td><td>' . $x['n'] . '</td></tr>'; }
  echo '</table>';
endif; ?>
</main></body></html>
