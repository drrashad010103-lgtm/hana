<?php
// pages/api_buy_load.php — تحميل فاتورة شراء (فرع 99 محلي + فواتير مستوردة للعرض فقط)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
$id = (int)($_GET['id'] ?? 0);
$pt = ((int)($_GET['pt'] ?? 1)) === 2 ? 2 : 1;
$db = db();
$st = $db->prepare("SELECT proc_id, branch, tot_purch-tot_net hdisc, paid FROM inv WHERE id=? AND branch IN (1,99) AND IS_Buy=1 AND proc_type=$pt AND IS_Deleted=0");
$st->execute([$id]);
$h = $st->fetch();
if (!$h) { echo json_encode(['ok' => false, 'error' => 'الفاتورة غير موجودة أو محذوفة']); exit; }
$readonly = (int)$h['branch'] !== 99;
$st = $db->prepare("SELECT s.currency_from id, c.symbol name, c.sale_price sale, s.val qty, s.exchange_price price, s.discount disc, DATE(s.expire_date) exp FROM inv_sub s LEFT JOIN currencies c ON c.id=s.currency_from WHERE s.proc_id=? AND s.proc_type=1");
$st->execute([(int)$h['proc_id']]);
echo json_encode(['ok' => true, 'readonly' => $readonly, 'branch' => (int)$h['branch'], 'hdisc' => (float)$h['hdisc'], 'paid' => (float)$h['paid'],
  'lines' => array_map(fn($r) => ['id' => (int)$r['id'], 'name' => $r['name'], 'sale' => (float)$r['sale'], 'qty' => (float)$r['qty'], 'price' => (float)$r['price'], 'disc' => (float)$r['disc'], 'discp' => 0, 'exp' => $r['exp'], 'note' => ''], $st->fetchAll())], JSON_UNESCAPED_UNICODE);
