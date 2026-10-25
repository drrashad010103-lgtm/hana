<?php
// pages/money.php — أموال: سندات قبض/صرف وتحويل + كشوف (فرع 99)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/audit.php';
$db = db();
$r = $_GET['r'] ?? 'balances';
$titles = ['in' => 'سند قبض', 'out' => 'سند صرف', 'transfer' => 'تحويل بين خزائن', 'moves' => 'حركة خزينة', 'balances' => 'أرصدة الخزائن', 'banks' => 'تقرير البنوك', 'exp' => 'المصروفات', 'shifts' => 'تقرير الورديات'];
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $kind = $_POST['kind'] ?? 'in';
  $acc1 = (int)$_POST['acc1'];
  $acc2 = (int)($_POST['acc2'] ?? 0);
  $amt = max(0, (float)$_POST['amt']);
  $notes = mb_substr(trim($_POST['notes'] ?? ''), 0, 255);
  if ($acc1 > 0 && $amt > 0 && ($kind !== 'transfer' || $acc2 > 0)) {
    $mx = seq_next_table('restrictions', 'id');
    $doc = seq_next_table('restrictions', 'doc_no');
    $db->beginTransaction();
    $type = $kind === 'in' ? 11 : ($kind === 'out' ? 12 : 13);
    $db->prepare("INSERT INTO restrictions (id, date, doc_no, type, state, notes, branch, IS_Deleted) VALUES (?,?,?,? ,1,?,99,0)")->execute([$mx, date('Y-m-d H:i:s'), $doc, $type, $notes]);
    $ins = $db->prepare("INSERT INTO restrictions_sub (res_id, date, dept, credit, acc_no, notes, branch) VALUES (?,?,?,?,?,?,99)");
    if ($kind === 'in') { $ins->execute([$mx, date('Y-m-d H:i:s'), $amt, 0, $acc1, $notes]); $ins->execute([$mx, date('Y-m-d H:i:s'), 0, $amt, $acc2, $notes]); }
    elseif ($kind === 'out') { $ins->execute([$mx, date('Y-m-d H:i:s'), 0, $amt, $acc1, $notes]); $ins->execute([$mx, date('Y-m-d H:i:s'), $amt, 0, $acc2, $notes]); }
    else { $ins->execute([$mx, date('Y-m-d H:i:s'), $amt, 0, $acc1, $notes]); $ins->execute([$mx, date('Y-m-d H:i:s'), 0, $amt, $acc2, $notes]); }
    $db->commit();
    audit_log('create', 'voucher', $mx, "kind=$kind doc=$doc amt=$amt acc1=$acc1 acc2=$acc2");
    $msg = '✅ سند ' . $doc;
  } else $msg = '❌ أكمل الحسابات والمبلغ';
}
$accs = $db->query("SELECT Code, AName FROM accounts_index ORDER BY Code LIMIT 500")->fetchAll();
$opt = function ($sel) use ($accs) {
  $h = '<option value="0">—</option>';
  foreach ($accs as $a) $h .= '<option value="' . $a['Code'] . '"' . ((int)$a['Code'] === (int)$sel ? ' selected' : '') . '>' . $a['Code'] . ' - ' . mb_substr($a['AName'] ?? '', 0, 40) . '</option>';
  return $h;
};
layout_open($titles[$r] ?? 'أموال', '');
if (in_array($r, ['in', 'out', 'transfer'], true)) {
  $isT = $r === 'transfer';
  echo '<div class="card"><h3>' . $titles[$r] . '</h3><form method="post" class="row"><input type="hidden" name="kind" value="' . $r . '">'
    . '<div><label>' . ($r === 'out' ? 'من حساب' : 'إلى حساب') . '</label><br><select name="acc1">' . $opt(0) . '</select></div>'
    . '<div><label>' . ($isT ? 'إلى حساب' : ($r === 'out' ? 'إلى حساب' : 'من حساب')) . '</label><br><select name="acc2">' . $opt(0) . '</select></div>'
    . '<div><label>المبلغ</label><br><input name="amt" type="number" step="0.01" min="0"></div>'
    . '<div><label>ملاحظات</label><br><input name="notes" style="width:220px"></div>'
    . '<div><br><button name="save" value="1">💾 حفظ السند</button> <span>' . $msg . '</span></div></form></div>';
}
$f = dt($_GET['from'] ?? '', date('Y-m-01'));
$t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
if ($r === 'moves') {
  $rows = $db->query("SELECT DATE(s.date) d, r.doc_no, a.AName acc, s.dept, s.credit, s.notes FROM restrictions_sub s JOIN restrictions r ON r.id=s.res_id LEFT JOIN accounts_index a ON a.Code=s.acc_no WHERE r.IS_Deleted=0 AND s.date>='$f 00:00:00' AND s.date<='$t2 23:59:59' ORDER BY s.date DESC LIMIT 300")->fetchAll();
  echo '<div class="card"><table><tr><th>التاريخ</th><th>سند</th><th>الحساب</th><th>مدين</th><th>دائن</th><th>ملاحظات</th></tr>';
  foreach ($rows as $x) echo '<tr><td>' . $x['d'] . '</td><td>' . $x['doc_no'] . '</td><td>' . e($x['acc'] ?? $x['acc_no'] ?? '') . '</td><td>' . $x['dept'] . '</td><td>' . $x['credit'] . '</td><td>' . e(mb_substr($x['notes'] ?? '', 0, 50)) . '</td></tr>';
  echo '</table></div>';
} else {
  $rows = $db->query("SELECT s.acc_no, a.AName acc, SUM(IFNULL(s.dept,0))-SUM(IFNULL(s.credit,0)) b FROM restrictions_sub s JOIN restrictions r ON r.id=s.res_id LEFT JOIN accounts_index a ON a.Code=s.acc_no WHERE r.IS_Deleted=0 GROUP BY s.acc_no, a.AName HAVING b<>0 ORDER BY b DESC LIMIT 200")->fetchAll();
  echo '<div class="card"><table><tr><th>الحساب</th><th>الرصيد</th></tr>';
  foreach ($rows as $x) echo '<tr><td>' . $x['acc_no'] . ' - ' . e($x['acc'] ?? '') . '</td><td class="' . ($x['b'] < 0 ? 'red' : 'green') . '"><b>' . number_format($x['b']) . '</b></td></tr>';
  echo '</table></div>';
}
layout_close();
