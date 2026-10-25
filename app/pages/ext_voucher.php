<?php
// pages/ext_voucher.php — سند قبض/صرف عمليات خارجية (mode=in|out) + شيك
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$mode = ($_GET['mode'] ?? 'in') === 'out' ? 'out' : 'in';
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $amt = max(0, (float)$_POST['amt']);
  $acc = (int)$_POST['acc'];
  $to = (int)$_POST['to'];
  if ($amt > 0 && $acc > 0 && $to > 0) {
    $mx = seq_next_table('restrictions', 'id');
    $doc = seq_next_table('restrictions', 'doc_no');
    $db->beginTransaction();
    $nt = mb_substr(trim($_POST['notes'] ?? ''), 0, 200) . ' | من: ' . mb_substr(trim($_POST['from'] ?? ''), 0, 64);
    if (!empty($_POST['chkn'])) $nt .= ' | شيك ' . mb_substr(trim($_POST['chkn']), 0, 32) . ' استحقاق ' . ($_POST['chkd'] ?? '') . ' بنك ' . mb_substr(trim($_POST['chkb'] ?? ''), 0, 64);
    $type = $mode === 'in' ? 14 : 15;
    $dt = preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?$/', (string)($_POST['dt'] ?? '')) ? $_POST['dt'] : date('Y-m-d');
    $db->prepare("INSERT INTO restrictions (id, date, doc_no, type, state, notes, branch, IS_Deleted) VALUES (?,?,?,? ,1,?,99,0)")->execute([$mx, $dt, $doc, $type, $nt]);
    $ins = $db->prepare("INSERT INTO restrictions_sub (res_id, date, dept, credit, acc_no, notes, branch) VALUES (?,?,?,?,?,?,99)");
    if ($mode === 'in') { $ins->execute([$mx, $dt, $amt, 0, $to, $nt]); $ins->execute([$mx, $dt, 0, $amt, $acc, $nt]); }
    else { $ins->execute([$mx, $dt, 0, $amt, $to, $nt]); $ins->execute([$mx, $dt, $amt, 0, $acc, $nt]); }
    $db->commit();
    $msg = '✅ سند ' . $doc;
  } else $msg = '❌ أكمل الحسابات والمبلغ';
}
$accs = $db->query("SELECT Code, AName FROM accounts_index ORDER BY Code LIMIT 500")->fetchAll();
$opt = function ($sel) use ($accs) {
  $h = '<option value="0">—</option>';
  foreach ($accs as $a) $h .= '<option value="' . $a['Code'] . '"' . ((int)$a['Code'] === (int)$sel ? ' selected' : '') . '>' . $a['Code'] . ' - ' . e(mb_substr($a['AName'] ?? '', 0, 40)) . '</option>';
  return $h;
};
$doc = (int)$db->query("SELECT IFNULL(MAX(doc_no),0)+1 n FROM restrictions")->fetch()['n'];
layout_open($mode === 'in' ? 'سند قبض عمليات خارجية' : 'سند صرف عمليات خارجية', '');
echo '<div class="card"><form method="post"><div class="row">'
  . '<div><label>رقم السند</label><br><input value="' . $doc . '" readonly style="width:80px"></div>'
  . '<div><label>التاريخ</label><br><input name="dt" type="date" value="' . date('Y-m-d') . '"></div>'
  . '<div><label>قيمة السند</label><br><input name="amt" type="number" step="0.01" min="0" style="width:130px"></div>'
  . '<div><label>' . ($mode === 'in' ? 'استلمت من السيد' : 'استلمت أنا السيد') . '</label><br><input name="from" style="width:180px"></div>'
  . '<div><label>' . ($mode === 'in' ? 'دفعة لحساب' : 'دفعة لحساب') . '</label><br><select name="acc">' . $opt(0) . '</select></div>'
  . '<div><label>' . ($mode === 'in' ? 'يودع في حساب' : 'يصرف من حساب') . '</label><br><select name="to">' . $opt(0) . '</select></div>'
  . '<div><label>نقدي <input type="radio" name="pay" value="cash" checked></label> <label>شيك <input type="radio" name="pay" value="check"></label></div>'
  . '<div><label>رقم الشيك</label><br><input name="chkn" style="width:110px"></div>'
  . '<div><label>تاريخ استحقاق (الشيك)</label><br><input name="chkd" type="date"></div>'
  . '<div><label>الشيك مسحوب على بنك</label><br><input name="chkb" style="width:150px"></div>'
  . '<div><label>البيان</label><br><input name="notes" style="width:220px"></div>'
  . '<div><br><button name="save" value="1">💾 حفظ السند</button> <span>' . $msg . '</span></div></div></form></div>';
layout_close();
