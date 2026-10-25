<?php
// pages/api_item_full.php — بطاقة صنف كاملة (أسعار + رصيد + وحدات)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
$id = (int)($_GET['id'] ?? 0);
$db = db();
  $st = $db->prepare("SELECT c.id, c.symbol name, c.group_id, c.unit, c.purch_price purch, c.sale_price sale, c.whole_price whole, c.barcode, c.offer_price, CASE WHEN c.offersale=1 AND (c.offer_until IS NULL OR c.offer_until>=NOW()) THEN 1 ELSE 0 END offersale FROM currencies c WHERE c.id=? AND c.IS_Deleted=0");
$st->execute([$id]);
$it = $st->fetch();
if (!$it) { echo json_encode(['ok' => false]); exit; }
$st = $db->prepare("SELECT IFNULL((SELECT bal FROM item_bal WHERE id=?),0) b");
$st->execute([$id]);
$bal = (float)($st->fetch()['b'] ?? 0);
$st = $db->prepare("SELECT u.unit uid, un.name nm FROM curr_units u LEFT JOIN units un ON un.id=u.unit WHERE u.curr=?");
$st->execute([$id]);
$units = $st->fetchAll();
$st = $db->prepare("SELECT name FROM units WHERE id=?");
$st->execute([(int)$it['unit']]);
$uname = $st->fetch()['name'] ?? '';
if (!$units) $units = [['uid' => $it['unit'], 'nm' => $uname]];
echo json_encode(['ok' => true, 'item' => [
  'id' => (int)$it['id'], 'name' => $it['name'], 'group_id' => (int)$it['group_id'],
  'purch' => (float)$it['purch'], 'sale' => (float)$it['sale'], 'whole' => (float)$it['whole'],
    'balance' => round($bal, 2), 'unit_name' => $uname, 'offer' => (float)($it['offer_price'] ?? 0), 'onoffer' => (int)($it['offersale'] ?? 0),
  'units' => array_map(fn($u) => ['factor' => 1, 'name' => $u['nm'] ?? $uname], $units),
]], JSON_UNESCAPED_UNICODE);
