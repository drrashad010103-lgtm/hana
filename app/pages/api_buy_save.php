<?php
// pages/api_buy_save.php — حفظ/تعديل/حذف فاتورة شراء (فرع 99 فقط)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/seq.php';
require_once __DIR__ . '/../lib/audit.php';
require_once __DIR__ . '/../lib/invoice_math.php';
require_once __DIR__ . '/../lib/items.php';
$in = json_decode(file_get_contents('php://input'), true);
$db = db();
$__fl = cfg_user_flags((int)($_SESSION['uid'] ?? 0));
$__noDisc = false;
if (empty($__fl['edit_disc'])) { $in['hdisc'] = 0; $__noDisc = true; }
try {
  if (!empty($in['del'])) {
    $ptd = ((int)($in['pt'] ?? 1)) === 2 ? 2 : 1;
    if (empty($__fl['edit_inv'])) { echo json_encode(['ok' => false, 'error' => 'لا صلاحية حذف فاتورة'], JSON_UNESCAPED_UNICODE); exit; }
    $delProc = (int)$db->query("SELECT proc_id FROM inv WHERE id=" . (int)$in['del'] . " AND branch=99 AND IS_Buy=1 AND proc_type=$ptd")->fetchColumn();
    $delItems = $delProc > 0 ? $db->query("SELECT DISTINCT currency_from FROM inv_sub WHERE proc_id=$delProc")->fetchAll(PDO::FETCH_COLUMN) : [];
    $st = $db->prepare("UPDATE inv SET IS_Deleted=1, user_IS_Deleted=? WHERE id=? AND branch=99 AND IS_Buy=1 AND proc_type=$ptd");
    $st->execute([(int)($_SESSION['uid'] ?? 0), (int)$in['del']]);
    if ($st->rowCount() > 0) {
      if ($delItems) stock_recalc_items($db, $delItems);
      audit_log('delete', 'buy', (int)$in['del'], "pt=$ptd");
    }
    echo json_encode(['ok' => $st->rowCount() > 0], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $lines = $in['lines'] ?? [];
  if (!is_array($lines) || !$lines) { echo json_encode(['ok' => false, 'error' => 'فارغة']); exit; }
  $hdisc = max(0, (float)($in['hdisc'] ?? 0));
  $paid = max(0, (float)($in['paid'] ?? 0));
  $editId = (int)($in['id'] ?? 0);
  $temp = !empty($in['temp']) ? 1 : 0;
  $pt = ((int)($in['pt'] ?? 1)) === 2 ? 2 : 1;
  if ($editId > 0 && empty($__fl['edit_inv'])) { echo json_encode(['ok' => false, 'error' => 'لا صلاحية تعديل فاتورة'], JSON_UNESCAPED_UNICODE); exit; }
  seq_init(); // بره الترانزاكشن (DDL بيعمل commit ضمني)
  $db->beginTransaction();
  if ($editId > 0) {
    $st = $db->prepare("SELECT proc_id FROM inv WHERE id=? AND branch=99 AND IS_Buy=1 AND proc_type=$pt AND IS_Deleted=0");
    $st->execute([$editId]);
    $row = $st->fetch();
    if (!$row) throw new RuntimeException('not-ours');
    $proc = (int)$row['proc_id'];
    $invId = $editId;
    $oldItems = $db->query("SELECT DISTINCT currency_from FROM inv_sub WHERE proc_id=$proc")->fetchAll(PDO::FETCH_COLUMN);
    $db->prepare("DELETE FROM inv_sub WHERE proc_id=?")->execute([$proc]);
  } else {
    $proc = seq_next('seq_proc'); // مسلسل آمن ضد التزامن (يبدأ من مليون لفرع 99)
    $invId = seq_next('seq_inv');
  }
  $tot = 0; $subs = [];
  foreach ($lines as $ln) {
    $code = (int)($ln['id'] ?? 0);
    $qty = (float)($ln['qty'] ?? 0);
    $disc = $__noDisc ? 0 : max(0, (float)($ln['disc'] ?? 0));
    $exp = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($ln['exp'] ?? '')) ? $ln['exp'] : null;
    if ($code <= 0 || $qty <= 0) continue;
    $st = $db->prepare("SELECT purch_price FROM currencies WHERE id=?");
    $st->execute([$code]);
    $dbp = $st->fetchColumn();
    if ($dbp === false) continue;
    $cost = empty($__fl['edit_price']) ? (float)$dbp : (float)($ln['price'] ?? 0); // بلا صلاحية سعر = تكلفة الشراء المسجلة في الصنف
    if ($cost < 0) $cost = 0;
    $lineTotals = invoice_line_totals($qty, $cost, $disc);
    $tot += $lineTotals['net'];
    $disc = $lineTotals['discount'];
    $sid = seq_next('seq_sub');
    $subs[] = [$sid, $proc, 1, $code, $qty, $qty, $cost, $disc, $exp];
  }
  if (!$subs) throw new RuntimeException('لا بنود صالحة');
  $hdisc = invoice_clamp_header_discount($tot, $hdisc);
  $totNet = $tot - $hdisc;
  if ($editId > 0) {
    $st = $db->prepare("UPDATE inv SET tot_purch=?, tot_sale=?, tot_net=?, paid=?, IS_Temp=?, user_IS_updated=? WHERE id=? AND branch=99 AND proc_type=$pt");
    $st->execute([$tot, $tot, $totNet, min($paid, $totNet), $temp, (int)($_SESSION['uid'] ?? 0), $invId]);
  } else {
    $st = $db->prepare("INSERT INTO inv (proc_id, id, date, inv_type, safe, stock, branch, IS_Buy, proc_type, IS_Deleted, IS_Temp, tot_purch, tot_sale, tot_net, paid, salesman, pay_type) VALUES (?,?,NOW(),2,1,1,99,1,$pt,0,?,?,?,?,?,1,1)");
    $st->execute([$proc, $invId, $temp, $tot, $tot, $totNet, min($paid, $totNet)]);
  }
  $st = $db->prepare("INSERT INTO inv_sub (id, proc_id, proc_type, currency_from, val, val1, exchange_price, discount, expire_date) VALUES (?,?,?,?,?,?,?,?,?)");
  foreach ($subs as $s) $st->execute($s);
  if (!$temp) {
    $affectedItems = array_unique(array_merge($oldItems ?? [], array_column($subs, 3)));
    stock_recalc_items($db, $affectedItems);
  }
  $db->commit();
  audit_log($editId > 0 ? 'edit' : 'create', 'buy', $invId, "tot=$totNet lines=" . count($subs));
  echo json_encode(['ok' => true, 'id' => $invId, 'total' => round($totNet, 2)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $t) {
  if ($db->inTransaction()) $db->rollBack();
  echo json_encode(['ok' => false, 'error' => $t->getMessage() === 'not-ours' ? 'ليست من فواتير النسخة' : 'db'], JSON_UNESCAPED_UNICODE);
}
