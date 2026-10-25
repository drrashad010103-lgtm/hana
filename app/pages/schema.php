<?php
// pages/schema.php — متصفح بنية البرنامج (قراءة فقط)
require __DIR__ . '/../lib/db.php';
$db = db();
$tbls = $db->query("SELECT TABLE_NAME t, TABLE_ROWS n FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='rs_own' ORDER BY TABLE_ROWS DESC")->fetchAll();
$sel = $_GET['t'] ?? 'inv';
$valid = array_column($tbls, 't');
if (!in_array($sel, $valid, true)) $sel = 'inv';
$cols = $db->query("SELECT COLUMN_NAME c, COLUMN_TYPE ty FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='rs_own' AND TABLE_NAME=" . $db->quote($sel) . " ORDER BY ORDINAL_POSITION")->fetchAll();
$sample = $db->query("SELECT * FROM `$sel` LIMIT 3")->fetchAll();
// تخمين العلاقات: أعمدة تنتهي بـ id وتطابق جدولاً آخر
$rels = [];
foreach ($cols as $c) {
  $n = $c['c'];
  foreach ($valid as $v) {
    if (strtolower($n) === strtolower($v) . '_id' || strtolower($n) === strtolower($v) . 'id') { $rels[$n] = $v; break; }
  }
}
$ar = ['inv' => 'الفواتير (رؤوس)', 'inv_sub' => 'بنود الفواتير', 'currencies' => 'الأصناف', 'groups' => 'الأقسام', 'restrictions' => 'القيود (رؤوس)', 'restrictions_sub' => 'بنود القيود', 'gard' => 'أوراق الجرد', 'gard_sub' => 'بنود الجرد', 'twreed_srf' => 'التوريد/الصرف', 'twreed_srf_sub' => 'بنود التوريد/الصرف', 'customers' => 'العملاء', 'users' => 'المستخدمون', 'salesmen' => 'المناديب', 'curr_barcodes' => 'باركودات الأصناف', 'safes' => 'الخزن', 'stocks' => 'المخازن', 'branches' => 'الفروع'];
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>بنية البرنامج</title>
<style>body{font-family:Tahoma;background:#f1f5f9;margin:0}header{background:#0f766e;color:#fff;padding:14px 18px}main{padding:16px;display:flex;gap:14px;flex-wrap:wrap}nav{background:#fff;border-radius:10px;padding:10px;min-width:220px}nav a{display:block;padding:5px 8px;color:#0f766e;text-decoration:none;font-size:14px}nav a.on{background:#ccfbf1;border-radius:6px;font-weight:bold}section{flex:1;min-width:300px}table{background:#fff;border-collapse:collapse;width:100%;font-size:13px}td,th{border:1px solid #e2e8f0;padding:5px 8px;text-align:right}.rel{color:#7c3aed}</style></head>
<body><header><b>🧩 بنية البرنامج</b> — <a href="../index.php" style="color:#fff">الرئيسية</a></header>
<main><nav><?php foreach ($tbls as $r): ?><a class="<?=$r['t']===$sel?'on':''?>" href="?t=<?=e($r['t'])?>"><?=e($ar[$r['t']] ?? $r['t'])?> <small>(<?=number_format((float)$r['n'])?>)</small></a><?php endforeach; ?></nav>
<section>
<h3><?=e($ar[$sel] ?? $sel)?> — <small><?=e($sel)?></small></h3>
<table><tr><th>العمود</th><th>النوع</th><th>مرتبط بـ</th></tr>
<?php foreach ($cols as $c): ?><tr><td><b><?=e($c['c'])?></b></td><td><?=e($c['ty'])?></td><td class="rel"><?=e($rels[$c['c']] ?? '')?></td></tr><?php endforeach; ?>
</table>
<h4>عيّنة (3 صفوف)</h4>
<table><tr><?php foreach ($cols as $c): ?><th><?=e($c['c'])?></th><?php endforeach; ?></tr>
<?php foreach ($sample as $row): ?><tr><?php foreach ($cols as $c): ?><td><?=e(mb_substr((string)($row[$c['c']] ?? ''), 0, 40))?></td><?php endforeach; ?></tr><?php endforeach; ?>
</table>
</section></main></body></html>
