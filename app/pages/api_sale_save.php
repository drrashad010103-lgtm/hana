<?php
// pages/api_sale_save.php — حفظ/تعديل/حذف فاتورة بيع (فرع 99 فقط — حماية للأصل)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/loyalty.php';
require_once __DIR__ . '/../lib/seq.php';
require_once __DIR__ . '/../lib/audit.php';
require_once __DIR__ . '/../lib/invoice_math.php';
require_once __DIR__ . '/../lib/items.php';
$in = json_decode(file_get_contents('php://input'), true);
$db = db();
// —— أعلام المستخدم الحساسة (لوحة التحكم → مستخدمين): تعديل/حذف/خصم/تاريخ ——
$__fl = cfg_user_flags((int)($_SESSION['uid'] ?? 0));
$__noDisc = false;
if (empty($__fl['edit_disc'])) { $in['hdisc'] = 0; $__noDisc = true; }
if (empty($__fl['edit_date'])) $in['date'] = null; // يُحفظ بتاريخ اليوم فقط
try {
  // حذف: مسموح لفرع 99 فقط + يحتاج صلاحية تعديل الفواتير
  if (!empty($in['del'])) {
    $ptd = ((int)($in['pt'] ?? 1)) === 2 ? 2 : 1;
    if (empty($__fl['edit_inv'])) { echo json_encode(['ok' => false, 'error' => 'لا صلاحية حذف فاتورة'], JSON_UNESCAPED_UNICODE); exit; }
    $delProc = (int)$db->query("SELECT proc_id FROM inv WHERE id=" . (int)$in['del'] . " AND branch=99 AND IS_Buy=0 AND proc_type=$ptd")->fetchColumn();
    $delItems = $delProc > 0 ? $db->query("SELECT DISTINCT currency_from FROM inv_sub WHERE proc_id=$delProc")->fetchAll(PDO::FETCH_COLUMN) : [];
    $st = $db->prepare("UPDATE inv SET IS_Deleted=1, user_IS_Deleted=? WHERE id=? AND branch=99 AND IS_Buy=0 AND proc_type=$ptd");
    $st->execute([(int)($_SESSION['uid'] ?? 0), (int)$in['del']]);
    if ($st->rowCount() > 0) {
      if ($delItems) stock_recalc_items($db, $delItems);
      audit_log('delete', 'sale', (int)$in['del'], "pt=$ptd");
    }
    echo json_encode(['ok' => $st->rowCount() > 0], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $lines = $in['lines'] ?? [];
  if (!is_array($lines) || !$lines) { echo json_encode(['ok' => false, 'error' => 'فارغة']); exit; }
  $hdisc = max(0, (float)($in['hdisc'] ?? 0));
  $paid = max(0, (float)($in['paid'] ?? 0));
  $cust = (int)($in['cust'] ?? 0);
  $sman = (int)($in['sman'] ?? 0);
  $payOk = [];
  foreach (cfg_pay_methods() as $pm) $payOk[] = (int)$pm['id'];
  $pay = (int)($in['pay'] ?? 1);
  if ($pay < 1 || !in_array($pay, $payOk, true)) $pay = 1;
  $acc = (int)($in['acc'] ?? 0);
  if ($acc > 0) { $ck = $db->prepare("SELECT 1 FROM banks WHERE id=?"); $ck->execute([$acc]); if (!$ck->fetch()) $acc = 0; }
  if ($pay === 1) $acc = 0; // الكاش للخزنة — بلا حساب بنكي
  $cobId = (int)($in['coupon'] ?? 0); $cob = 0;
  if ($cobId > 0) {
    $vc = $db->prepare("SELECT id FROM coboun WHERE id=? AND (Is_deleted=0 OR Is_deleted IS NULL) AND active=1 AND (date_start IS NULL OR date_start<=CURDATE()) AND (date_end IS NULL OR date_end>=CURDATE())");
    $vc->execute([$cobId]);
    if ($vc->fetch()) $cob = 1; else $cobId = 0;
  }
  $dateIn = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($in['date'] ?? '')) ? $in['date'] : null;
  // —— توصيل / حجز ——
  $dlv = !empty($in['delivery']) ? 1 : 0;
  $dlvName = mb_substr(trim((string)($in['dlvName'] ?? '')), 0, 120);
  $dlvTel = mb_substr(trim((string)($in['dlvTel'] ?? '')), 0, 40);
  $dlvTel2 = mb_substr(trim((string)($in['dlvTel2'] ?? '')), 0, 40);
  $dlvAdd = mb_substr(trim((string)($in['dlvAdd'] ?? '')), 0, 255);
  $dlvDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($in['dlvDate'] ?? '')) ? $in['dlvDate'] : null;
  $dlvGoes = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($in['dlvGoes'] ?? '')) ? $in['dlvGoes'] : null;
  $dlvCome = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($in['dlvCome'] ?? '')) ? $in['dlvCome'] : null;
  $dlvNotes = mb_substr(trim((string)($in['dlvNotes'] ?? '')), 0, 255);
  if (!$dlv) { $dlvName = $dlvTel = $dlvTel2 = $dlvAdd = $dlvNotes = ''; $dlvDate = $dlvGoes = $dlvCome = null; }
  $editId = (int)($in['id'] ?? 0);
  $temp = !empty($in['temp']) ? 1 : 0;
  $pt = ((int)($in['pt'] ?? 1)) === 2 ? 2 : 1;
  if ($editId > 0 && empty($__fl['edit_inv'])) { echo json_encode(['ok' => false, 'error' => 'لا صلاحية تعديل فاتورة'], JSON_UNESCAPED_UNICODE); exit; }
  seq_init(); // بره الترانزاكشن (DDL بيعمل commit ضمني)
  $db->beginTransaction();
  if ($editId > 0) {
    $st = $db->prepare("SELECT proc_id, DATE(date) d FROM inv WHERE id=? AND branch=99 AND IS_Buy=0 AND proc_type=$pt AND IS_Deleted=0");
    $st->execute([$editId]);
    $row = $st->fetch();
    if (!$row) throw new RuntimeException('not-ours');
    $proc = (int)$row['proc_id'];
    $invId = $editId;
    // تعديل: من غير تاريخ صريح نحتفظ بتاريخ الفاتورة الأصلي (ممنوع إعادة التأريخ لليوم)
    $date = $dateIn ?? $row['d'] ?? date('Y-m-d');
    $prevDate = (string)($row['d'] ?? '');
    $oldItems = $db->query("SELECT DISTINCT currency_from FROM inv_sub WHERE proc_id=$proc")->fetchAll(PDO::FETCH_COLUMN);
    $db->prepare("DELETE FROM inv_sub WHERE proc_id=?")->execute([$proc]);
  } else {
    $proc = seq_next('seq_proc'); // مسلسل آمن ضد التزامن (يبدأ من مليون لفرع 99)
    $invId = seq_next('seq_inv');
    $date = $dateIn ?? date('Y-m-d');
    $prevDate = '';
  }
  $tot = 0; $subs = [];
  foreach ($lines as $ln) {
    $code = (int)($ln['id'] ?? 0);
    $qty = (float)($ln['qty'] ?? 0);
    $disc = $__noDisc ? 0 : max(0, (float)($ln['disc'] ?? 0));
    if ($code <= 0 || $qty <= 0) continue;
    $st = $db->prepare("SELECT sale_price FROM currencies WHERE id=?");
    $st->execute([$code]);
    $dbp = $st->fetchColumn();
    if ($dbp === false) continue;
    $price = empty($__fl['edit_price']) ? (float)$dbp : (float)($ln['price'] ?? 0); // بلا صلاحية سعر = سعر البيع المسجل في الصنف
    if ($price < 0) $price = 0;
    $lineTotals = invoice_line_totals($qty, $price, $disc);
    $tot += $lineTotals['net'];
    $disc = $lineTotals['discount'];
    $sid = seq_next('seq_sub');
    $subs[] = [$sid, $proc, 2, $code, $qty, $qty, $price, $price, $disc];
  }
  if (!$subs) throw new RuntimeException('لا بنود صالحة');
  $hdisc = invoice_clamp_header_discount($tot, $hdisc);
  $totNet = $tot - $hdisc;
  if ($editId > 0) {
    $st = $db->prepare("UPDATE inv SET date=?, cust_id=?, salesman=?, tot_sale=?, tot_net=?, paid=?, IS_Temp=?, pay_type=?, bank=?, coboun=?, coboun_id=?, coboun_Dis=?, user_IS_updated=?, delivery=?, reserv_custname=?, reserv_custtel=?, reserv_custtel2=?, reserv_custadd=?, reserv_date=?, reserv_notes=?, date_delevGoes=?, date_delevCome=? WHERE id=? AND branch=99 AND proc_type=$pt");
    $st->execute([$date, $cust, $sman, $tot, $totNet, min($paid, $totNet), $temp, $pay, $acc, $cob, $cobId, $cob ? min($hdisc, $tot) : 0, (int)($_SESSION['uid'] ?? 0), $dlv, $dlvName, $dlvTel, $dlvTel2, $dlvAdd, $dlvDate, $dlvNotes, $dlvGoes, $dlvCome, $invId]);
  } else {
    $st = $db->prepare("INSERT INTO inv (proc_id, id, date, inv_type, safe, stock, branch, IS_Buy, proc_type, IS_Deleted, IS_Temp, cust_id, salesman, tot_sale, tot_net, paid, pay_type, bank, coboun, coboun_id, coboun_Dis, delivery, reserv_custname, reserv_custtel, reserv_custtel2, reserv_custadd, reserv_date, reserv_notes, date_delevGoes, date_delevCome) VALUES (?,?,?,2,1,1,99,0,$pt,0,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$proc, $invId, $date, $temp, $cust, $sman, $tot, $totNet, min($paid, $totNet), $pay, $acc, $cob, $cobId, $cob ? min($hdisc, $tot) : 0, $dlv, $dlvName, $dlvTel, $dlvTel2, $dlvAdd, $dlvDate, $dlvNotes, $dlvGoes, $dlvCome]);
  }
  $st = $db->prepare("INSERT INTO inv_sub (id, proc_id, proc_type, currency_from, val, val1, sale_price, exchange_price, discount) VALUES (?,?,?,?,?,?,?,?,?)");
  foreach ($subs as $s) $st->execute($s);
  if (!$temp) {
    $affectedItems = array_unique(array_merge($oldItems ?? [], array_column($subs, 3)));
    stock_recalc_items($db, $affectedItems);
  }
  // نقاط الولاء: خصم المستبدل + كسب الجديد
  $loyUse = max(0, (int)($in['loyaltyUse'] ?? 0));
  $loyEarn = 0;
  if ($cust > 0 && $pt === 1 && !$temp) {
    if ($loyUse > 0) {
      if (!loyalty_redeem($cust, $loyUse, $invId, 'استبدال من فاتورة')) $loyUse = 0;
    }
    $loyEarn = (int)floor($totNet * (float)(cfg_get('loy_earn', '1') ?: '1'));
    if ($loyEarn > 0) loyalty_earn($cust, $loyEarn, $invId, 'كسب من فاتورة');
  }
  $db->commit();
  audit_log($editId > 0 ? 'edit' : 'create', 'sale', $invId, "tot=$totNet date=$date lines=" . count($subs) . ($editId > 0 ? " prevDate=$prevDate" : ''));
  echo json_encode(['ok' => true, 'id' => $invId, 'total' => round($totNet, 2), 'loyaltyEarn' => $loyEarn], JSON_UNESCAPED_UNICODE);
} catch (Throwable $t) {
  if ($db->inTransaction()) $db->rollBack();
  echo json_encode(['ok' => false, 'error' => $t->getMessage() === 'not-ours' ? 'ليست من فواتير النسخة' : 'db'], JSON_UNESCAPED_UNICODE);
}
