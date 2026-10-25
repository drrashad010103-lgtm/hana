<?php
// pages/print_inv.php — طباعة فاتورة (A4 + ريسيت 80مم) — فرع 99 والمستورد
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/i18n.php';
$db = db();
$__lang = strtolower(trim((string)($_GET['lang'] ?? 'ar')));
if (!i18n_valid($__lang)) $__lang = 'ar';
$__dir = i18n_dir($__lang);
$__printLangs = '';
foreach (i18n_langs() as $__lc => $__ln) {
  $__printLangs .= '<option value="' . e($__lc) . '"' . ($__lc === $__lang ? ' selected' : '') . '>' . e($__ln) . '</option>';
}
$T = function (string $s) use ($__lang): string { return i18n_t($s, $__lang); };
$id = (int)($_GET['id'] ?? 0);
$f = ($_GET['f'] ?? '') === '80' ? '80' : 'A4';
$h = $db->prepare("SELECT i.*, DATE(i.date) d, IFNULL(cu.name,'عميل نقدي') cust FROM inv i LEFT JOIN customers cu ON cu.id=i.cust_id WHERE i.id=? AND i.branch IN (1,99) AND i.IS_Deleted=0 LIMIT 1");
$h->execute([$id]); $h = $h->fetch();
if (!$h) exit('لا فاتورة');
$ls = $db->prepare("SELECT c.symbol nm, s.val q, s.exchange_price p, s.discount d FROM inv_sub s LEFT JOIN currencies c ON c.id=s.currency_from WHERE s.proc_id=? ORDER BY s.id");
$ls->execute([$h['proc_id']]); $lines = $ls->fetchAll();
$S = cfg_shop();
$payn = [1 => 'كاش', 2 => 'فيزا', 3 => 'محفظة', 4 => 'انستاباى'];
foreach (cfg_pay_methods(true) as $__pm) $payn[(int)$__pm['id']] = (string)$__pm['name'];
$w = $f === '80' ? '280px' : '700px';
?>
<!DOCTYPE html><html lang="<?=e($__lang)?>" dir="<?=$__dir?>"><head><meta charset="utf-8"><title><?=e($T('فاتورة'))?> <?=$id?></title>
<style>
*{box-sizing:border-box}body{font-family:Tahoma,Arial;margin:0;background:#eee}
.sheet{background:#fff;margin:10px auto;padding:14px;width:<?=$w?>;max-width:96vw}
h2{text-align:center;margin:4px 0;font-size:<?= $f === '80' ? '16px' : '22px' ?>}
.c{text-align:center;font-size:12px;color:#333}
.meta{text-align:center;font-size:13px;margin:4px 0}
table{width:100%;border-collapse:collapse;font-size:<?= $f === '80' ? '12px' : '14px' ?>;margin-top:8px}
td,th{border:1px solid #000;padding:4px 6px;text-align:center}
.tot{font-size:<?= $f === '80' ? '14px' : '18px' ?>;font-weight:800;text-align:left;margin-top:8px}
.bar{margin:10px 0;text-align:center}
.bar button{font-size:15px;padding:10px 18px;border:none;border-radius:8px;background:#0f766e;color:#fff;font-weight:800;cursor:pointer;font-family:inherit;margin:0 4px}
.bar select{font-size:14px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-family:inherit;margin:0 4px}
<?php if ($f === '80'): ?>table{table-layout:fixed;font-size:11px}td,th{padding:3px 2px}th{font-size:10px}th:nth-child(1),td:nth-child(1){width:10%}th:nth-child(2),td:nth-child(2){width:30%;text-align:right}th:nth-child(3),td:nth-child(3){width:12%}th:nth-child(4),td:nth-child(4){width:14%}th:nth-child(5),td:nth-child(5){width:18%}th:nth-child(6),td:nth-child(6){width:16%}th:nth-child(5){font-size:9px}<?php endif; ?>
@media print{.bar{display:none}body{background:#fff}.sheet{margin:0;max-width:100%}@page{size:auto;margin:8mm}}
</style></head>
<body>
<div class="bar"><select aria-label="Print language" data-no-i18n="1" onchange="var u=new URL(location.href);u.searchParams.set('lang',this.value);u.searchParams.delete('auto');location.href=u.toString()"><?=$__printLangs?></select><button onclick="window.print()">🖨️ <?=e($T('طباعة'))?></button><a href="?id=<?=$id?>&f=<?= $f === '80' ? 'A4' : '80' ?>&lang=<?=e($__lang)?>"><button><?= $f === '80' ? '📄 ' : '🧾 ' ?><?=e($T($f === '80' ? 'نسخة A4' : 'نسخة 80مم'))?></button></a><button onclick="location.href='sale_rs.php'"><?=e($T('رجوع'))?></button></div>
<div class="sheet">
<h2><?=e($S['name'])?></h2>
<div class="c"><?= trim(($S['tel'] !== '' ? '☎ ' . e($S['tel']) . '   ' : '') . ($S['mobile'] !== '' ? '📱 ' . e($S['mobile']) : '') . ($S['address'] !== '' ? '   📍 ' . e($S['address']) : '') . ($S['tax_no'] !== '' ? '   ' . e($T('ض.ر:')) . ' ' . e($S['tax_no']) : '')) ?: '&nbsp;' ?></div>
<div class="c"><?=e($T($h['IS_Buy'] ? 'فاتورة مشتريات' : 'فاتورة بيع'))?> <?= $h['proc_type'] == 2 ? e($T('(مرتجع)')) : '' ?> <?=e($T('رقم'))?> <?=$id?> — <?=e((string)$h['d'])?></div>
<div class="meta"><?=e($T('العميل:'))?> <b><?=e($h['cust'])?></b> — <?=e($T('الدفع:'))?> <?=e($T((string)($payn[(int)$h['pay_type']] ?? '')))?></div>
<?php if (!empty($h['delivery']) || !empty($h['reserv_custname']) || !empty($h['reserv_custadd'])): ?>
<div style="background:#e0f2fe;border:1px solid #0284c7;border-radius:6px;padding:6px 10px;margin-top:6px;font-size:<?= $f === '80' ? '11px' : '13px' ?>">
<b style="color:#075985">🚚 <?=e($T('توصيل / حجز'))?></b>
<?php if (!empty($h['reserv_custname'])): ?><br>📦 <?=e($T('المستلم'))?>: <b><?=e($h['reserv_custname'])?></b><?php endif; ?>
<?php if (!empty($h['reserv_custtel']) || !empty($h['reserv_custtel2'])): ?><br>📱 <?=e($T('التليفون'))?>: <?=e($h['reserv_custtel'])?><?= !empty($h['reserv_custtel2']) ? ' / ' . e($h['reserv_custtel2']) : '' ?><?php endif; ?>
<?php if (!empty($h['reserv_custadd'])): ?><br>📍 <?=e($T('العنوان'))?>: <?=e($h['reserv_custadd'])?><?php endif; ?>
<?php if (!empty($h['reserv_date'])): ?><br>📅 <?=e($T('تاريخ التوصيل/الحجز'))?>: <?=e($h['reserv_date'])?><?php endif; ?>
<?php if (!empty($h['date_delevGoes'])): ?><br>📤 <?=e($T('تاريخ الذهاب'))?>: <?=e($h['date_delevGoes'])?><?php endif; ?>
<?php if (!empty($h['date_delevCome'])): ?><br>📥 <?=e($T('تاريخ العودة'))?>: <?=e($h['date_delevCome'])?><?php endif; ?>
<?php if (!empty($h['reserv_notes'])): ?><br>📝 <?=e($h['reserv_notes'])?><?php endif; ?>
</div>
<?php endif; ?>
<table><tr><th><?=e($T('م'))?></th><th><?=e($T('الصنف'))?></th><th><?=e($T('كمية'))?></th><th><?=e($T('سعر'))?></th><th><?=e($T('خصم'))?></th><th><?=e($T('إجمالي'))?></th></tr>
<?php $i = 1; foreach ($lines as $ln): $t = $ln['q'] * $ln['p'] - $ln['d']; ?>
<tr><td><?=$i++?></td><td dir="auto"><?=e($ln['nm'])?></td><td><?=$ln['q']?></td><td><?=$ln['p']?></td><td><?=$ln['d']?></td><td><?=number_format($t, 2)?></td></tr>
<?php endforeach; ?></table>
<div class="tot"><?=e($T('الإجمالي'))?>: <?=number_format($h['tot_net'], 2)?> — <?=e($T('المدفوع'))?>: <?=number_format($h['paid'], 2)?> — <?=e($T('الباقي'))?>: <?=number_format($h['tot_net'] - $h['paid'], 2)?></div>
<div class="c"><?=e($T('شكراً لتعاملكم 🌿'))?></div>
</div>
<script>if (new URLSearchParams(location.search).get('auto') === '1') window.print();</script>
</body></html>
