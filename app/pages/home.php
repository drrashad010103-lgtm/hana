<?php
// pages/home.php — لوحة تحكم النسخة الحرة (تقرأ من rs_own مباشرة — خفيفة لإنفينيتي)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require __DIR__ . '/../lib/auth.php';
auth_need();
$db = db();
function q1($sql, $def = []) {
  try { $r = db()->query($sql)->fetch(); return $r ?: $def; }
  catch (Throwable $t) { return $def; }
}
function qq($sql) {
  try { return db()->query($sql)->fetchAll(); }
  catch (Throwable $t) { return []; }
}
$td = q1("SELECT SUM(IF(IS_Buy=0 AND proc_type=1,tot_net,0))-SUM(IF(IS_Buy=0 AND proc_type=2,tot_net,0)) s, COUNT(DISTINCT IF(IS_Buy=0 AND proc_type=1,proc_id,NULL)) n, SUM(IF(IS_Buy=1 AND proc_type=1,tot_net,0)) b FROM inv WHERE IS_Deleted=0 AND DATE(date)=CURDATE()", ['s'=>0,'n'=>0,'b'=>0]);
$mo = q1("SELECT SUM(IF(IS_Buy=0 AND proc_type=1,tot_net,0))-SUM(IF(IS_Buy=0 AND proc_type=2,tot_net,0)) s, COUNT(DISTINCT IF(IS_Buy=0 AND proc_type=1,proc_id,NULL)) n, SUM(IF(IS_Buy=1 AND proc_type=1,tot_net,0)) b FROM inv WHERE IS_Deleted=0 AND DATE_FORMAT(date,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')", ['s'=>0,'n'=>0,'b'=>0]);
$yr = q1("SELECT SUM(IF(IS_Buy=0 AND proc_type=1,tot_net,0))-SUM(IF(IS_Buy=0 AND proc_type=2,tot_net,0)) s, COUNT(DISTINCT IF(IS_Buy=0 AND proc_type=1,proc_id,NULL)) n, SUM(IF(IS_Buy=1 AND proc_type=1,tot_net,0)) b FROM inv WHERE IS_Deleted=0 AND YEAR(date)=YEAR(NOW())", ['s'=>0,'n'=>0,'b'=>0]);
$pf_t = q1("SELECT SUM(s.val*s.exchange_price) rev, SUM(s.val*c.purch_price) cost FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND DATE(i.date)=CURDATE()", ['rev'=>0,'cost'=>0]);
$pf_m = q1("SELECT SUM(s.val*s.exchange_price) rev, SUM(s.val*c.purch_price) cost FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND DATE_FORMAT(i.date,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')", ['rev'=>0,'cost'=>0]);
$st = q1("SELECT COUNT(*) n, SUM(IF(bal>0,1,0)) pos, SUM(IF(bal<=0,1,0)) zero_q FROM item_bal", ['n'=>0,'pos'=>0,'zero_q'=>0]);
$stv = q1("SELECT SUM(b.bal*c.purch_price) vc, SUM(b.bal*c.sale_price) vs FROM item_bal b JOIN currencies c ON c.id=b.id WHERE b.bal>0", ['vc'=>0,'vs'=>0]);
$upd = q1("SELECT MAX(updated) u FROM item_bal", ['u'=>'—']);
$tops = qq("SELECT c.symbol nm, SUM(s.val*s.exchange_price) rev FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id LEFT JOIN currencies c ON c.id=s.currency_from WHERE i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 AND DATE_FORMAT(i.date,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m') GROUP BY c.symbol ORDER BY rev DESC LIMIT 10");
$last = qq("SELECT id, DATE(date) d, tot_net net, paid FROM inv WHERE IS_Deleted=0 AND IS_Buy=0 AND proc_type=1 ORDER BY date DESC LIMIT 10");
$u = auth_user();
function n0($v){ return number_format((float)$v,0); }
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>لوحة التحكم — <?=e(cfg_shop_name())?></title>
<style>
*{box-sizing:border-box}body{font-family:Tahoma,Arial;margin:0;background:#f1f5f9;color:#0f172a}
header{background:#0f766e;color:#fff;padding:12px 16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
header b{font-size:17px}header .sp{margin-right:auto;display:flex;gap:8px;align-items:center;font-size:13px}
header a{color:#fff;text-decoration:none;background:#ffffff2e;padding:6px 12px;border-radius:8px}
main{padding:14px;max-width:1100px;margin:auto}
.cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.card{background:#fff;border-radius:14px;padding:14px 16px;box-shadow:0 1px 5px #0002;flex:1;min-width:200px}
.card .t{font-size:13px;color:#64748b}.card .v{font-size:24px;font-weight:800;color:#16a34a}
.card .s{font-size:12px;color:#475569}.green{color:#16a34a}.red{color:#dc2626}
.nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.nav a{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;text-decoration:none;color:#0f172a;font-weight:700;font-size:14px}
.grid2{display:flex;gap:10px;flex-wrap:wrap}.grid2 .card{flex:1;min-width:280px}
table{width:100%;border-collapse:collapse;font-size:13px}td,th{border:1px solid #e2e8f0;padding:6px 8px;text-align:right}th{background:#f8fafc}
.note{font-size:12px;color:#64748b;margin-top:6px}
.card{overflow-x:auto}
@media(max-width:760px){.cards{flex-direction:column}.card{min-width:0}table{display:block;overflow-x:auto;white-space:nowrap}main{padding:10px}header{flex-wrap:wrap}}
</style></head>
<body>
<header><b>🌿 لوحة التحكم — <?=e(cfg_shop_name())?></b>
<span class="sp"><span><?=htmlspecialchars($u)?></span><a href="main.php">القوائم</a><a href="board.php">📊 الداشبورد</a><a href="sale_rs.php">بيع</a><a href="buy_rs.php">شراء</a><a href="settings.php">🎛️ إعدادات</a><a href="logout.php">خروج</a></span></header>
<main>
<div class="cards">
<div class="card"><div class="t">📅 مبيعات اليوم</div><div class="v"><?=n0($td['s'])?></div><div class="s">فواتير: <b><?=n0($td['n'])?></b> | مشتريات: <b class="red"><?=n0($td['b'])?></b></div><div class="s">ربح تقريبي: <b class="green"><?=n0(($pf_t['rev']??0)-($pf_t['cost']??0))?></b></div></div>
<div class="card"><div class="t">📅 مبيعات الشهر</div><div class="v"><?=n0($mo['s'])?></div><div class="s">فواتير: <b><?=n0($mo['n'])?></b> | مشتريات: <b class="red"><?=n0($mo['b'])?></b></div><div class="s">ربح تقريبي: <b class="green"><?=n0(($pf_m['rev']??0)-($pf_m['cost']??0))?></b></div></div>
<div class="card"><div class="t">📅 مبيعات السنة</div><div class="v"><?=n0($yr['s'])?></div><div class="s">فواتير: <b><?=n0($yr['n'])?></b> | مشتريات: <b class="red"><?=n0($yr['b'])?></b></div></div>
</div>
<div class="cards">
<div class="card"><div class="t">📦 المخزون (حتى <?=htmlspecialchars($upd['u'] ?? '—')?>)</div><div class="v"><?=n0($st['pos'])?> <span style="font-size:13px;color:#64748b">صنف برصيد</span></div><div class="s">إجمالي الأصناف: <?=n0($st['n'])?> | صفر/سالب: <?=n0($st['zero_q'])?></div><div class="s">قيمة التكلفة: <b><?=n0($stv['vc'])?></b> | قيمة البيع: <b class="green"><?=n0($stv['vs'])?></b></div><div class="note"><a href="stock.php?r=balances">الأرصدة</a> | <a href="refresh_bal.php">تحديث الأرصدة</a></div></div>
<div class="card"><div class="t">🧾 آخر 10 فواتير بيع</div><table><tr><th>فاتورة</th><th>التاريخ</th><th>الصافي</th></tr><?php foreach($last as $x): ?><tr><td><?=$x['id']?></td><td><?=$x['d']?></td><td><?=n0($x['net'])?></td></tr><?php endforeach; ?></table></div>
</div>
<div class="card"><div class="t">🏆 توب 10 أصناف هذا الشهر (إيراد)</div><table><tr><th>الصنف</th><th>الإيراد</th></tr><?php foreach($tops as $x): ?><tr><td><?=htmlspecialchars($x['nm'])?></td><td><?=n0($x['rev'])?></td></tr><?php endforeach; ?></table>
<div class="nav" style="margin-top:10px"><a href="reports.php?r=daily">📆 اليومية</a><a href="reports.php?r=profit">💰 الأرباح</a><a href="reports.php?r=detail">📝 التفصيلية</a><a href="stock.php?r=itemtot">📈 إجمالي الحركات</a></div>
</div>
</main></body></html>
