<?php
// pages/dups.php — الأصناف المكررة (نفس الباركود / نفس الاسم)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$byBarcode = $db->query("SELECT barcode, GROUP_CONCAT(id ORDER BY id) ids, COUNT(*) n FROM currencies WHERE IS_Deleted=0 AND barcode IS NOT NULL AND barcode<>'' GROUP BY barcode HAVING n>1 ORDER BY n DESC LIMIT 100")->fetchAll();
$byName = $db->query("SELECT symbol, GROUP_CONCAT(id ORDER BY id) ids, COUNT(*) n FROM currencies WHERE IS_Deleted=0 GROUP BY symbol HAVING n>1 ORDER BY n DESC LIMIT 100")->fetchAll();
$fuzzy = $db->query("SELECT LEFT(symbol,18) stem, GROUP_CONCAT(CONCAT(id,':',symbol) SEPARATOR ' | ') members, COUNT(*) n FROM currencies WHERE IS_Deleted=0 AND CHAR_LENGTH(symbol)>18 GROUP BY stem HAVING n>1 AND n<30 ORDER BY n DESC LIMIT 100")->fetchAll();
$info = function ($ids) use ($db) {
  $ids = array_map('intval', explode(',', $ids));
  $ph = implode(',', array_fill(0, count($ids), '?'));
  $st = $db->prepare("SELECT c.id, c.symbol, c.barcode, c.sale_price, c.purch_price,
    (SELECT IFNULL((SELECT bal FROM item_bal b WHERE b.id=c.id),0)) bal,
    (SELECT COUNT(*) FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=c.id AND i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1) sales_n
    FROM currencies c WHERE c.id IN ($ph) ORDER BY c.id");
  $st->execute($ids);
  return $st->fetchAll();
};
layout_open('الأصناف المكررة', '');
echo '<div class="card"><b>🔁 نفس الباركود على أكثر من صنف:</b> ' . count($byBarcode) . ' مجموعة<table><tr><th>الباركود</th><th>الأكواد</th><th>التفاصيل (رصيد/مبيعات)</th></tr>';
foreach ($byBarcode as $g) {
  echo '<tr><td><b>' . e($g['barcode']) . '</b></td><td>' . e($g['ids']) . '</td><td><table>';
  foreach ($info($g['ids']) as $r) echo '<tr><td>' . $r['id'] . '</td><td>' . e($r['symbol']) . '</td><td>بيع ' . e($r['sale_price']) . '</td><td>تكلفة ' . e($r['purch_price']) . '</td><td>رصيد ' . e($r['bal']) . '</td><td>فواتير بيع ' . e($r['sales_n']) . '</td></tr>';
  echo '</table></td></tr>';
}
echo '</table></div>';
echo '<div class="card"><b>📝 نفس الاسم مكرر:</b> ' . count($byName) . ' مجموعة<table><tr><th>الاسم</th><th>الأكواد</th></tr>';
foreach ($byName as $g) echo '<tr><td>' . e($g['symbol']) . '</td><td>' . e($g['ids']) . '</td></tr>';
echo '</table></div>';
echo '<div class="card"><b>🔍 أسماء متشابهة (نفس البداية):</b> ' . count($fuzzy) . ' مجموعة<table><tr><th>البداية</th><th>الأصناف</th></tr>';
foreach ($fuzzy as $g) echo '<tr><td><b>' . e($g['stem']) . '</b> (' . $g['n'] . ')</td><td style="font-size:13px">' . e($g['members']) . '</td></tr>';
echo '</table></div>';
layout_close();
