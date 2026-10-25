<?php
// pages/api_buy_item.php — بطاقة صنف للشراء (تكلفة + بيع + رصيد)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
$id = (int)($_GET['id'] ?? 0);
$db = db();
$st = $db->prepare("SELECT c.id, c.symbol name, c.group_id, c.purch_price purch, c.sale_price sale FROM currencies c WHERE c.id=? AND c.IS_Deleted=0");
$st->execute([$id]);
$it = $st->fetch();
if (!$it) { echo json_encode(['ok' => false]); exit; }
$st = $db->prepare("SELECT IFNULL((SELECT bal FROM item_bal WHERE id=?),0) b");
$st->execute([$id]);
echo json_encode(['ok' => true, 'item' => ['id' => (int)$it['id'], 'name' => $it['name'], 'group_id' => (int)$it['group_id'],
  'purch' => (float)$it['purch'], 'sale' => (float)$it['sale'], 'balance' => round((float)($st->fetch()['b'] ?? 0), 2)]], JSON_UNESCAPED_UNICODE);
