<?php
// pages/api_last_buy.php — آخر شراء لصنف
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
$id = (int)($_GET['id'] ?? 0);
$db = db();
$st = $db->prepare("SELECT s.val qty, s.exchange_price price, DATE(i.date) d FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=? AND i.IS_Deleted=0 AND i.IS_Buy=1 AND i.proc_type IN (1,3) AND s.proc_type=1 ORDER BY i.date DESC LIMIT 1");
$st->execute([$id]);
$r = $st->fetch();
if (!$r) { echo json_encode(['ok' => false]); exit; }
echo json_encode(['ok' => true, 'qty' => (float)$r['qty'], 'price' => (float)$r['price'], 'date' => $r['d']], JSON_UNESCAPED_UNICODE);
