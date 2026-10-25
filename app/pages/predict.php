<?php
// pages/predict.php — التنبؤ الذكي للمخزون: هينفد إمتى + كمية الطلب
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$u = auth_user();
$days = max(1, min(730, (int)($_GET['d'] ?? 365)));
$db = db();

// إحصائيات عامة
$totalItems = (int)$db->query("SELECT COUNT(DISTINCT s.currency_from) FROM inv i JOIN inv_sub s ON i.proc_id=s.proc_id WHERE i.proc_type=1 AND i.IS_Buy=0 AND s.proc_type=2 AND IFNULL(i.IS_Temp,0)=0 AND i.IS_Deleted=0 AND i.date >= DATE_SUB(NOW(), INTERVAL $days DAY)")->fetchColumn();

// التنبؤ الكامل
$q = "SELECT s.currency_from AS item_id, c.symbol AS nm, c.limit AS lim, c.sale_price, c.purch_price,
  IFNULL(s2.bal, 0) AS bal,
  SUM(s.val) AS total_qty,
  ROUND(SUM(s.val)/$days, 3) AS avg_day,
  ROUND(IFNULL(s2.bal,0) / NULLIF(SUM(s.val)/$days, 0), 1) AS days_left,
  ROUND((SUM(s.val)/$days) * 30 - IFNULL(s2.bal,0), 0) AS suggest_qty
FROM inv i
JOIN inv_sub s ON i.proc_id = s.proc_id
JOIN currencies c ON c.id = s.currency_from
LEFT JOIN item_bal s2 ON s2.id = s.currency_from
WHERE i.proc_type=1 AND i.IS_Buy=0 AND s.proc_type=2 AND IFNULL(i.IS_Temp,0)=0 AND i.IS_Deleted=0 AND i.date >= DATE_SUB(NOW(), INTERVAL $days DAY)
  AND c.IS_Deleted = 0
GROUP BY s.currency_from, c.symbol, c.limit, c.sale_price, c.purch_price, s2.bal
HAVING total_qty >= 5
ORDER BY avg_day DESC";

$rows = $db->query($q)->fetchAll(PDO::FETCH_ASSOC);
$red = []; $yellow = []; $ok = [];
foreach ($rows as $r) {
  $dl = $r['days_left'] !== null ? (float)$r['days_left'] : 9999;
  $bal = (float)$r['bal'];
  if ($bal <= 0 || $dl <= 7) $red[] = $r;
  elseif ($dl <= 30) $yellow[] = $r;
  else $ok[] = $r;
}
$redCount = count($red); $yellowCount = count($yellow);
$avgSuggest = count($yellow) ? round(array_sum(array_column($yellow, 'suggest_qty')) / count($yellow)) : 0;
$costEst = 0;
foreach ($yellow as $r) $costEst += $r['suggest_qty'] * $r['purch_price'];
layout_open("تنبؤ المخزون");
?>
<style>
.pcard{background:linear-gradient(135deg,#0f172a,#1e293b);border:1px solid #334155;border-radius:14px;padding:20px;color:#e2e8f0;box-shadow:0 8px 30px #0006}
.pcard h3{margin:0 0 6px;font-size:15px;color:#94a3b8}
.pcard .num{font-size:32px;font-weight:800}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
@media(max-width:700px){.stat-grid{grid-template-columns:1fr 1fr}}
.s-red{background:linear-gradient(135deg,#450a0a,#7f1d1d);border-color:#dc2626}.s-red .num{color:#fca5a5}
.s-yellow{background:linear-gradient(135deg,#451a03,#78350f);border-color:#f59e0b}.s-yellow .num{color:#fcd34d}
.s-green{background:linear-gradient(135deg,#052e16,#14532d);border-color:#16a34a}.s-green .num{color:#86efac}
.s-blue{background:linear-gradient(135deg,#0c1e3e,#1e3a5f);border-color:#3b82f6}.s-blue .num{color:#93c5fd}
table.pred{width:100%;border-collapse:collapse;font-size:13px;background:#0f172a;border-radius:10px;overflow:hidden}
table.pred th{background:#1e293b;color:#94a3b8;padding:10px 8px;text-align:right;font-weight:600;position:sticky;top:0}
table.pred td{padding:8px;border-bottom:1px solid #1e293b;color:#cbd5e1}
table.pred tr:hover td{background:#1e293b55}
.tag{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700}
.tag-red{background:#dc262622;color:#fca5a5;border:1px solid #dc262655}
.tag-yellow{background:#f59e0b22;color:#fcd34d;border:1px solid #f59e0b55}
.tag-green{background:#16a34a22;color:#86efac;border:1px solid #16a34a55}
.sec-title{font-size:16px;font-weight:700;color:#e2e8f0;margin:18px 0 10px;display:flex;align-items:center;gap:8px}
.sec-title .count{background:#dc2626;color:#fff;border-radius:8px;padding:1px 8px;font-size:13px}
.sec-title .count.y{background:#f59e0b}
.export-row{margin:10px 0}
.btn-exp{background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;padding:10px 22px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
.btn-exp:hover{filter:brightness(1.1)}
.days-filter{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.days-filter a{padding:6px 16px;border-radius:8px;background:#1e293b;color:#94a3b8;text-decoration:none;font-size:13px;font-weight:600}
.days-filter a.active{background:linear-gradient(135deg,#16a34a,#15803d);color:#fff}
input.qsearch{background:#1e293b;border:1px solid #334155;color:#e2e8f0;padding:8px 14px;border-radius:8px;font-size:14px;width:260px;font-family:inherit}
</style>

<div class="stat-grid">
  <div class="pcard s-red"><h3>🔴 هينفد خلال أسبوع</h3><div class="num"><?= $redCount ?></div><small>صنف يحتاج طلب فوري</small></div>
  <div class="pcard s-yellow"><h3>🟡 هينفد خلال شهر</h3><div class="num"><?= $yellowCount ?></div><small>صنف يحتاج مراجعة</small></div>
  <div class="pcard s-green"><h3>✅ المخزون سليم</h3><div class="num"><?= count($ok) ?></div><small>أكثر من 30 يوم</small></div>
  <div class="pcard s-blue"><h3>📊 إجمالي الأصناف</h3><div class="num"><?= $totalItems ?></div><small>مباعة خلال <?= $days ?> يوم</small></div>
</div>

<div class="days-filter">
  <span style="color:#94a3b8;align-self:center;margin-left:6px">فترة التحليل:</span>
  <?php foreach([30=>'شهر',90=>'3 شهور',180=>'6 شهور',365=>'سنة'] as $d=>$l): ?>
    <a href="?d=<?= $d ?>" class="<?= $days===$d?'active':'' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<!-- 🔴 طلب فوري -->
<div class="sec-title">🔴 طلب فوري <span class="count"><?= $redCount ?></span></div>
<div class="export-row">
  <button class="btn-exp" onclick="exportWA('red')">📱 إرسال قائمة الطلب للمورد (واتساب)</button>
</div>
<table class="pred" id="tblRed">
<thead><tr><th>#</th><th>الصنف</th><th>الرصيد</th><th>متوسط البيع/يوم</th><th>يكفي (يوم)</th><th>كمية مقترحة</th><th>تكلفة تقريبية</th></tr></thead>
<tbody>
<?php foreach ($red as $i => $r): ?>
<tr data-nm="<?= e($r['nm']) ?>" data-q="<?= $r['suggest_qty'] ?>">
  <td><?= $i+1 ?></td>
  <td><strong><?= e($r['nm']) ?></strong></td>
  <td><span class="tag tag-red"><?= $r['bal'] <= 0 ? 'خلص' : $r['bal'] ?></span></td>
  <td><?= $r['avg_day'] ?></td>
  <td><?= $r['days_left'] ?? '∞' ?></td>
  <td><strong><?= max(1, $r['suggest_qty']) ?></strong></td>
  <td><?= fmt($r['suggest_qty'] * $r['purch_price']) ?> ج</td>
</tr>
<?php endforeach; ?>
</tbody></table>

<!-- 🟡 مراجعة -->
<div class="sec-title" style="margin-top:24px">🟡 مراجعة خلال شهر <span class="count y"><?= $yellowCount ?></span></div>
<table class="pred" id="tblYellow">
<thead><tr><th>#</th><th>الصنف</th><th>الرصيد</th><th>متوسط البيع/يوم</th><th>يكفي (يوم)</th><th>كمية مقترحة</th><th>تكلفة تقريبية</th></tr></thead>
<tbody>
<?php foreach ($yellow as $i => $r): ?>
<tr data-nm="<?= e($r['nm']) ?>" data-q="<?= $r['suggest_qty'] ?>">
  <td><?= $i+1 ?></td>
  <td><?= e($r['nm']) ?></td>
  <td><span class="tag tag-yellow"><?= $r['bal'] ?></span></td>
  <td><?= $r['avg_day'] ?></td>
  <td><?= $r['days_left'] ?></td>
  <td><strong><?= $r['suggest_qty'] ?></strong></td>
  <td><?= fmt($r['suggest_qty'] * $r['purch_price']) ?> ج</td>
</tr>
<?php endforeach; ?>
</tbody></table>

<script>
function exportWA(tbl){
  const rows = document.querySelectorAll('#tbl'+tbl+' tbody tr');
  let msg = '📋 *طلب شراء مقترح — تنبؤ المخزون*\n\n';
  let n = 1;
  rows.forEach(r=>{
    msg += n+'. '+r.dataset.nm+' — '+r.dataset.q+' قطعة\n';
    n++;
  });
  msg += '\n(محسوب تلقائياً من متوسط البيع)';
  window.open('https://wa.me/?text='+encodeURIComponent(msg), '_blank');
}
</script>
<?php layout_close(); ?>
