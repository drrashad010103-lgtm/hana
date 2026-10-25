<?php
// النسخة الحرة — الرئيسية (تقرأ من rs_own فقط)
require __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/cfg.php';
require __DIR__ . '/lib/auth.php';
auth_need();
$tables = db()->query("SELECT TABLE_NAME t, TABLE_ROWS n FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='rs_own' ORDER BY TABLE_ROWS DESC")->fetchAll();
$sales = db()->query("SELECT ROUND(SUM(IF(proc_type=1,tot_net,0))-SUM(IF(proc_type=2,tot_net,0)),2) s, COUNT(*) n FROM inv WHERE IS_Deleted=0 AND IS_Buy=0 AND proc_type IN (1,2)")->fetch();
$items = db()->query("SELECT COUNT(*) n FROM currencies")->fetch();
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(cfg_shop_name())?></title>
<style>body{font-family:Tahoma;background:#f1f5f9;margin:0}header{background:#0f766e;color:#fff;padding:14px 18px}main{padding:16px}.cards{display:flex;gap:10px;flex-wrap:wrap}.card{background:#fff;border-radius:10px;padding:14px 18px;min-width:150px;box-shadow:0 1px 4px #0002}.card b{font-size:22px}table{background:#fff;border-collapse:collapse;width:100%;margin-top:14px}td,th{border:1px solid #e2e8f0;padding:6px 10px;font-size:14px}.ok{color:#16a34a}</style></head>
<body><header><b>🌿 <?=e(cfg_shop_name())?></b> — تعمل على نسخة البيانات، الأصل لم يُلمس | <a href="pages/home.php" style="color:#fff">📊 لوحة التحكم</a> | <a href="pages/main.php" style="color:#fff">القوائم</a> | <a href="pages/logout.php" style="color:#fff">خروج (<?=e(auth_user() ?? '')?>)</a></header>
<main>
<div class="cards">
<div class="card">صافي المبيعات الكلي<br><b><?=fmt($sales['s'])?></b></div>
<div class="card">الفواتير<br><b><?=fmt($sales['n'])?></b></div>
<div class="card">الأصناف<br><b><?=fmt($items['n'])?></b></div>
<div class="card">جداول البيانات الحيّة<br><b><?=fmt(count($tables))?></b></div>
</div>
<table><tr><th>الجدول</th><th>الصفوف</th></tr>
<?php foreach ($tables as $r): ?><tr><td><?=e($r['t'])?></td><td><?=fmt($r['n'])?></td></tr><?php endforeach; ?>
</table>
</main></body></html>
