<?php
// pages/api_sale_load.php — تحميل فاتورة محفوظة (فرع 99 محلي + فواتير مستوردة للعرض فقط)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
$id = (int)($_GET['id'] ?? 0);
$pt = ((int)($_GET['pt'] ?? 1)) === 2 ? 2 : 1;
$db = db();
$st = $db->prepare("SELECT proc_id, branch, DATE(date) d, cust_id, salesman, pay_type, bank, tot_sale-tot_net hdisc, paid, delivery, reserv_custname, reserv_custtel, reserv_custtel2, reserv_custadd, DATE(reserv_date) dlvDate, reserv_notes, DATE(date_delevGoes) dlvGoes, DATE(date_delevCome) dlvCome FROM inv WHERE id=? AND branch IN (1,99) AND IS_Buy=0 AND proc_type=$pt AND IS_Deleted=0");
$st->execute([$id]);
$h = $st->fetch();
if (!$h) { echo json_encode(['ok' => false, 'error' => 'الفاتورة غير موجودة أو محذوفة'], JSON_UNESCAPED_UNICODE); exit; }
$readonly = (int)$h['branch'] !== 99;
$st = $db->prepare("SELECT s.currency_from id, c.symbol name, c.purch_price purch, s.val qty, s.exchange_price price, s.discount disc FROM inv_sub s LEFT JOIN currencies c ON c.id=s.currency_from WHERE s.proc_id=? AND s.proc_type=2");
$st->execute([(int)$h['proc_id']]);
echo json_encode(['ok' => true, 'readonly' => $readonly, 'branch' => (int)$h['branch'], 'date' => $h['d'], 'cust' => (int)$h['cust_id'], 'sman' => (int)($h['salesman'] ?? 0), 'pay' => (int)($h['pay_type'] ?? 1), 'acc' => (int)($h['bank'] ?? 0), 'hdisc' => (float)$h['hdisc'], 'paid' => (float)$h['paid'],
  'delivery' => (int)($h['delivery'] ?? 0), 'dlvName' => (string)($h['reserv_custname'] ?? ''), 'dlvTel' => (string)($h['reserv_custtel'] ?? ''), 'dlvTel2' => (string)($h['reserv_custtel2'] ?? ''), 'dlvAdd' => (string)($h['reserv_custadd'] ?? ''), 'dlvDate' => (string)($h['dlvDate'] ?? ''), 'dlvGoes' => (string)($h['dlvGoes'] ?? ''), 'dlvCome' => (string)($h['dlvCome'] ?? ''), 'dlvNotes' => (string)($h['reserv_notes'] ?? ''),
  'lines' => array_map(fn($r) => ['id' => (int)$r['id'], 'name' => $r['name'], 'purch' => (float)($r['purch'] ?? 0), 'qty' => (float)$r['qty'], 'price' => (float)$r['price'], 'disc' => (float)$r['disc']], $st->fetchAll())], JSON_UNESCAPED_UNICODE);
