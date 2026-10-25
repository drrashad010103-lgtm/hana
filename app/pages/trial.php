<?php
// pages/trial.php — ميزان المراجعة لكل الحسابات + كشف حساب تفصيلي برصيد متحرك (فرع 99)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$r = $_GET['r'] ?? 'trial';
$f = dt($_GET['from'] ?? '', date('Y-m-01'));
$t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
$accs = $db->query("SELECT Code, AName FROM accounts_index ORDER BY Code")->fetchAll();
$opt = '';
foreach ($accs as $a) $opt .= '<option value="' . $a['Code'] . '">' . $a['Code'] . ' - ' . e(mb_substr($a['AName'] ?? '', 0, 40)) . '</option>';
layout_open($r === 'ledger' ? 'كشف حساب تفصيلي' : 'ميزان المراجعة', '');
echo '<div class="card"><form method="get" class="row"><input type="hidden" name="r" value="' . e($r) . '">'
  . ($r === 'ledger' ? '<div><label>الحساب</label><br><select name="acc">' . $opt . '</select></div>' : '')
  . '<div><label>من</label><br><input name="from" type="date" value="' . $f . '"></div>'
  . '<div><label>إلى</label><br><input name="to" type="date" value="' . $t2 . '"></div>'
  . '<div><br><button>عرض</button></div></form></div>';
if ($r === 'ledger') {
  $acc = (int)($_GET['acc'] ?? 0);
  $nm = trim((string)($db->query("SELECT AName FROM accounts_index WHERE Code=$acc")->fetch()['AName'] ?? ''));
  if ($acc > 0 && $nm !== '') {
    $nat = (int)($db->query("SELECT Nature FROM accounts_index WHERE Code=$acc")->fetch()['Nature'] ?? 0);
    $op = (float)($db->query("SELECT SUM(s.dept)-SUM(s.credit) b FROM restrictions_sub s JOIN restrictions r ON r.id=s.res_id WHERE s.acc_no=$acc AND r.IS_Deleted=0 AND r.branch=99 AND s.date<'$f 00:00:00'")->fetch()['b'] ?? 0);
    $st = $db->prepare("SELECT s.date, r.doc_no, r.notes rn, s.dept, s.credit, s.notes, SUM(s.dept-s.credit) OVER (ORDER BY s.date, s.res_id, s.dept ROWS UNBOUNDED PRECEDING) run FROM restrictions_sub s JOIN restrictions r ON r.id=s.res_id WHERE s.acc_no=? AND r.IS_Deleted=0 AND r.branch=99 AND s.date>=? AND s.date<=? ORDER BY s.date, s.res_id LIMIT 500");
    $st->execute([$acc, $f . ' 00:00:00', $t2 . ' 23:59:59']);
    $rows = $st->fetchAll();
    echo '<div class="card"><h3>حساب ' . $acc . ' — ' . e($nm) . ' <small>(' . ($nat === 1 ? 'طبيعته دائن' : 'طبيعته مدين') . ')</small></h3>'
      . '<p>رصيد أول المدة: <b style="color:#065f46">' . number_format($op, 2) . '</b></p>'
      . '<table><tr><th>التاريخ</th><th>السند</th><th style="text-align:right">البيان</th><th>مدين</th><th>دائن</th><th>الرصيد</th></tr>';
    $sumD = 0; $sumC = 0; $run = 0;
    foreach ($rows as $x) {
      $d = (float)$x['dept']; $c = (float)$x['credit'];
      $sumD += $d; $sumC += $c; $run = round((float)$x['run'], 2);
      echo '<tr><td>' . substr((string)$x['date'], 0, 10) . '</td><td>' . $x['doc_no'] . '</td>'
        . '<td style="text-align:right">' . e(mb_substr(($x['notes'] ?? '') ?: ($x['rn'] ?? ''), 0, 70)) . '</td>'
        . '<td>' . ($d ? number_format($d, 2) : '') . '</td><td>' . ($c ? number_format($c, 2) : '') . '</td>'
        . '<td><b>' . number_format(round($op + $run, 2), 2) . '</b></td></tr>';
    }
    $cl = round($op + $sumD - $sumC, 2);
    echo '<tr style="background:#ede9fe;font-weight:800"><td colspan="3">الرصيد النهائي</td><td>' . number_format($sumD, 2) . '</td><td>' . number_format($sumC, 2) . '</td><td>' . number_format($cl, 2) . '</td></tr>';
    if (!$rows) echo '<tr><td colspan="6">لا حركة في الفترة</td></tr>';
    echo '</table></div>';
  } else echo '<div class="card">اختار حساب ورقة صح من القائمة فوق.</div>';
} else {
  $all = (($_GET['all'] ?? '') === '1');
  $st = $db->prepare("SELECT s.acc_no, a.AName, SUM(IF(s.date<? , s.dept-s.credit, 0)) ob, SUM(IF(s.date>=?, s.dept, 0)) pd, SUM(IF(s.date>=?, s.credit, 0)) pc FROM restrictions_sub s JOIN restrictions r2 ON r2.id=s.res_id LEFT JOIN accounts_index a ON a.Code=s.acc_no WHERE r2.IS_Deleted=0 AND r2.branch=99 AND s.date<? GROUP BY s.acc_no ORDER BY s.acc_no");
  $st->execute([$f . ' 00:00:00', $f . ' 00:00:00', $f . ' 00:00:00', $t2 . ' 23:59:59']);
  $rows = [];
  foreach ($st->fetchAll() as $x) {
    $ob = (float)$x['ob']; $pd = (float)$x['pd']; $pc = (float)$x['pc'];
    if (!$all && $ob === 0.0 && $pd === 0.0 && $pc === 0.0) continue;
    $rows[] = ['a' => (int)$x['acc_no'], 'n' => (string)$x['AName'], 'ob' => $ob, 'pd' => $pd, 'pc' => $pc, 'cl' => round($ob + $pd - $pc, 2)];
  }
  echo '<div class="card"><form method="get" style="float:left"><input type="hidden" name="r" value="trial"><input type="hidden" name="from" value="' . $f . '"><input type="hidden" name="to" value="' . $t2 . '"><label><input type="checkbox" name="all" value="1"' . ($all ? ' checked' : '') . '> إظهار كل الحسابات (حتى الصفر)</label> <button>تحديث</button></form>'
    . '<table><tr><th>الحساب</th><th style="text-align:right">الاسم</th><th>رصيد أول</th><th>مدين الفترة</th><th>دائن الفترة</th><th>الرصيد النهائي</th></tr>';
  $c = ['ob' => 0, 'pd' => 0, 'pc' => 0, 'cl' => 0];
  foreach ($rows as $x) {
    $c['ob'] += $x['ob']; $c['pd'] += $x['pd']; $c['pc'] += $x['pc']; $c['cl'] += $x['cl'];
    echo '<tr><td><a href="?r=ledger&acc=' . $x['a'] . '&amp;from=' . $f . '&amp;to=' . $t2 . '"><b>' . $x['a'] . '</b></a></td><td style="text-align:right">' . e(mb_substr($x['n'], 0, 40)) . '</td>'
      . '<td>' . number_format($x['ob'], 2) . '</td><td>' . number_format($x['pd'], 2) . '</td><td>' . number_format($x['pc'], 2) . '</td>'
      . '<td style="color:' . ($x['cl'] < 0 ? '#dc2626' : '#065f46') . '"><b>' . number_format($x['cl'], 2) . '</b></td></tr>';
  }
  echo '<tr style="background:#ede9fe;font-weight:800"><td colspan="2">الإجمالي</td><td>' . number_format($c['ob'], 2) . '</td><td>' . number_format($c['pd'], 2) . '</td><td>' . number_format($c['pc'], 2) . '</td><td>' . number_format($c['cl'], 2) . '</td></tr>';
  $chk = round($c['pd'] - $c['pc'], 2);
  echo '</table>' . ($chk === 0.0 ? '<p style="color:#16a34a;font-weight:800">✅ الفترة متزنة (مدين = دائن)</p>' : '<p style="color:#dc2626;font-weight:800">⚠️ فرق ' . number_format($chk, 2) . ' — راجع قيود الفترة</p>') . '</div>';
}
layout_close();
