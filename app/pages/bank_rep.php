<?php
// pages/bank_rep.php — تقرير البنوك (كشف حساب بنك)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$banks = $db->query("SELECT id, name, accountNo FROM banks ORDER BY id")->fetchAll();
$bid = (int)($_GET['bid'] ?? 0);
$f = dt($_GET['from'] ?? '', '2026-10-15');
$t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
$acc = 0;
foreach ($banks as $b) if ($b['id'] == $bid) $acc = (int)$b['accountNo'];
layout_open('تقرير البنوك', '');
echo '<div class="card"><form class="row"><div><label>البنك</label><br><select name="bid"><option value="0">الكل</option>';
foreach ($banks as $b) echo '<option value="' . $b['id'] . '"' . ($b['id'] == $bid ? ' selected' : '') . '>' . e($b['name']) . '</option>';
echo '</select></div><div><label>من تاريخ</label><br><input type="date" name="from" value="' . e($f) . '"></div><div><label>إلى تاريخ</label><br><input type="date" name="to" value="' . e($t2) . '"></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">طباعة</button></div></form></div>';
if ($acc > 0) {
  $prev = $db->query("SELECT SUM(IFNULL(s.dept,0))-SUM(IFNULL(s.credit,0)) b FROM restrictions_sub s JOIN restrictions r ON r.id=s.res_id WHERE s.acc_no=$acc AND r.IS_Deleted=0 AND s.date<'$f 00:00:00'")->fetch()['b'] ?? 0;
  $rows = $db->query("SELECT DATE(s.date) d, r.doc_no, s.notes, s.dept, s.credit FROM restrictions_sub s JOIN restrictions r ON r.id=s.res_id WHERE s.acc_no=$acc AND r.IS_Deleted=0 AND s.date>='$f 00:00:00' AND s.date<='$t2 23:59:59' ORDER BY s.date LIMIT 300")->fetchAll();
  $bal = $prev;
  echo '<div class="card"><div class="note">رصيد سابق: <b>' . number_format($prev) . '</b></div><table><tr><th>مدين</th><th>دائن</th><th>الرصيد</th><th>البيان</th><th>تاريخ القيد</th><th>رقم القيد</th></tr>';
  foreach ($rows as $x) {
    $bal += $x['dept'] - $x['credit'];
    echo '<tr><td>' . number_format($x['dept']) . '</td><td>' . number_format($x['credit']) . '</td><td><b>' . number_format($bal) . '</b></td><td>' . e(mb_substr($x['notes'] ?? '', 0, 60)) . '</td><td>' . $x['d'] . '</td><td>' . $x['doc_no'] . '</td></tr>';
  }
  $td = array_sum(array_column($rows, 'dept'));
  $tc = array_sum(array_column($rows, 'credit'));
  echo '</table><div class="note">إجمالي الفترة: مدين <b>' . number_format($td) . '</b> — دائن <b>' . number_format($tc) . '</b> — رصيد الفترة: <b>' . number_format($bal) . '</b></div></div>';
} else echo '<div class="card"><div class="note">اختر بنكاً (البنوك تحتاج رقم حساب في تعريف البنوك لعرض كشفها).</div></div>';
layout_close();
