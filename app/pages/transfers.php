<?php
// pages/transfers.php — تحويل بين خزائن + تقاريرها + أرصدة وحركة الخزائن
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$db->exec("CREATE TABLE IF NOT EXISTS rs_safebox (id INT PRIMARY KEY AUTO_INCREMENT, safe_id INT NOT NULL, dt DATE NOT NULL, kind VARCHAR(8) NOT NULL, amount DOUBLE NOT NULL DEFAULT 0, notes TEXT, ref_no INT DEFAULT 0)");
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $from = (int)$_POST['from'];
  $to = (int)$_POST['to'];
  $amt = max(0, (float)$_POST['amt']);
  if ($from > 0 && $to > 0 && $from !== $to && $amt > 0) {
    $doc = (int)$db->query("SELECT IFNULL(MAX(ref_no),1107)+1 n FROM rs_safebox")->fetch()['n'];
    $dt = $_POST['dt'] ?? date('Y-m-d');
    $nt = mb_substr(trim($_POST['notes'] ?? ''), 0, 255) . ' | المستلم: ' . mb_substr(trim($_POST['recv'] ?? ''), 0, 64) . ($_POST['cash'] ?? '' ? ' | نقدي' : '');
    $db->prepare("INSERT INTO rs_safebox (safe_id, dt, kind, amount, notes, ref_no) VALUES (?,?,?,?,?,?)")->execute([$from, $dt, 'out', $amt, $nt, $doc]);
    $db->prepare("INSERT INTO rs_safebox (safe_id, dt, kind, amount, notes, ref_no) VALUES (?,?,?,?,?,?)")->execute([$to, $dt, 'in', $amt, $nt, $doc]);
    $msg = '✅ سند تحويل ' . $doc;
  } else $msg = '❌ أكمل الخزنتين والمبلغ';
}
$safes = $db->query("SELECT id, name FROM safes ORDER BY id")->fetchAll();
$tab = $_GET['tab'] ?? 'new';
layout_open('تحويل بين خزائن', '');
echo '<div class="card"><a href="?tab=new">تحويلات بين الخزائن</a> | <a href="?tab=rep">تقرير تحويلات الخزن</a> | <a href="?tab=bal">أرصدة حساب الخزن</a> | <a href="?tab=moves">حركة الخزينة</a></div>';
if ($tab === 'new') {
  $doc = (int)$db->query("SELECT IFNULL(MAX(ref_no),1107)+1 n FROM rs_safebox")->fetch()['n'];
  $opt = function ($sel) use ($safes) {
    $h = '';
    foreach ($safes as $s) $h .= '<option value="' . $s['id'] . '"' . ($s['id'] == $sel ? ' selected' : '') . '>' . e($s['name']) . '</option>';
    return $h;
  };
  echo '<div class="card"><form method="post"><div class="row">'
    . '<div><label>سند تحويل خزينة رقم</label><br><input value="' . $doc . '" readonly style="width:90px"></div>'
    . '<div><label>التاريخ</label><br><input name="dt" type="date" value="' . date('Y-m-d') . '"></div>'
    . '<div><label>قيمة التحويل</label><br><input name="amt" type="number" step="0.01" min="0" style="width:130px"></div>'
    . '<div><label>المستلم</label><br><input name="recv" style="width:160px"></div>'
    . '<div><label><input type="checkbox" name="cash" value="1" checked> نقدي</label></div>'
    . '<div><label>إيداع من خزينة</label><br><select name="from">' . $opt(0) . '</select></div>'
    . '<div><label>إلى خزينة</label><br><select name="to">' . $opt(0) . '</select></div>'
    . '<div><label>الكاشير</label><br><input style="width:130px"></div>'
    . '<div><label>المدير</label><br><input style="width:130px"></div>'
    . '<div><label>ملاحظات</label><br><input name="notes" style="width:260px"></div>'
    . '<div><br><button name="save" value="1">💾 حفظ التحويل</button> <span>' . $msg . '</span></div></div></form></div>';
} elseif ($tab === 'rep') {
  $f = dt($_GET['from'] ?? '', '2026-10-15');
  $t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
  $rows = $db->query("SELECT o.ref_no, o.dt, f.name fn, t.name tn, o.amount FROM rs_safebox o JOIN rs_safebox t ON t.ref_no=o.ref_no AND t.kind='in' JOIN safes f ON f.id=o.safe_id JOIN safes t2 ON t2.id=t.safe_id WHERE o.kind='out' AND o.dt>='$f' AND o.dt<='$t2' GROUP BY o.ref_no, o.dt, f.name, t.name, o.amount ORDER BY o.dt DESC")->fetchAll();
  $tt = array_sum(array_column($rows, 'amount'));
  echo '<div class="card"><form class="row"><input type="hidden" name="tab" value="rep"><div><label>من تاريخ</label><br><input type="date" name="from" value="' . e($f) . '"></div><div><label>إلى تاريخ</label><br><input type="date" name="to" value="' . e($t2) . '"></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">طباعة</button></div></form>'
    . '<table><tr><th>قيمة التحويل</th><th>تاريخ التحويل</th><th>رقم التحويل</th><th>تحويل من</th><th>تحويل إلى</th></tr>';
  foreach ($rows as $r) echo '<tr><td>' . number_format($r['amount']) . '</td><td>' . $r['dt'] . '</td><td>' . $r['ref_no'] . '</td><td>' . e($r['fn']) . '</td><td>' . e($r['tn']) . '</td></tr>';
  echo '</table><div class="note">الإجمالي: <b>' . number_format($tt) . '</b></div></div>';
} elseif ($tab === 'bal') {
  $rows = $db->query("SELECT s.id, s.name, SUM(IF(b.kind='in',b.amount,0))-SUM(IF(b.kind='out',b.amount,0)) bal, SUM(IF(b.kind='in',b.amount,0)) vin, SUM(IF(b.kind='out',b.amount,0)) vout FROM safes s LEFT JOIN rs_safebox b ON b.safe_id=s.id GROUP BY s.id, s.name")->fetchAll();
  echo '<div class="card"><table><tr><th>اسم الخزينة</th><th>حركة مدين (وارد)</th><th>حركة دائن (صادر)</th><th>الرصيد</th><th>الحالة</th></tr>';
  foreach ($rows as $r) echo '<tr><td>' . e($r['name']) . '</td><td>' . number_format($r['vin']) . '</td><td>' . number_format($r['vout']) . '</td><td><b>' . number_format($r['bal']) . '</b></td><td>' . ($r['bal'] < 0 ? 'سالب' : 'سليم') . '</td></tr>';
  echo '</table></div>';
} else {
  $sid = (int)($_GET['sid'] ?? 0);
  $f = dt($_GET['from'] ?? '', '2026-10-15');
  $t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
  $fs = $sid > 0 ? "AND b.safe_id=$sid" : "";
  echo '<div class="card"><form class="row"><input type="hidden" name="tab" value="moves"><div><label>الخزينة</label><br><select name="sid"><option value="0">الكل</option>';
  foreach ($safes as $s) echo '<option value="' . $s['id'] . '"' . ($s['id'] == $sid ? ' selected' : '') . '>' . e($s['name']) . '</option>';
  echo '</select></div><div><label>من تاريخ</label><br><input type="date" name="from" value="' . e($f) . '"></div><div><label>إلى تاريخ</label><br><input type="date" name="to" value="' . e($t2) . '"></div><div><br><button>عرض</button></div></form></div>';
  $rows = $db->query("SELECT b.id, s.name, b.dt, b.kind, b.amount, b.notes FROM rs_safebox b JOIN safes s ON s.id=b.safe_id WHERE b.dt>='$f' AND b.dt<='$t2' $fs ORDER BY b.dt DESC, b.id DESC LIMIT 300")->fetchAll();
  $bal = 0;
  echo '<div class="card"><table><tr><th>م</th><th>الحركة</th><th>الخزينة</th><th>التاريخ</th><th>وارد</th><th>صادر</th><th>الرصيد</th><th>البيان</th></tr>';
  foreach (array_reverse($rows) as $r) {
    $bal += $r['kind'] === 'in' ? $r['amount'] : -$r['amount'];
    $rr[] = '<tr><td>' . $r['id'] . '</td><td>' . ($r['kind'] === 'in' ? 'وارد' : 'صادر') . '</td><td>' . e($r['name']) . '</td><td>' . $r['dt'] . '</td><td>' . ($r['kind'] === 'in' ? number_format($r['amount']) : '') . '</td><td>' . ($r['kind'] === 'out' ? number_format($r['amount']) : '') . '</td><td><b>' . number_format($bal) . '</b></td><td>' . e(mb_substr($r['notes'] ?? '', 0, 60)) . '</td></tr>';
  }
  echo implode('', array_reverse($rr ?? [])) . '</table></div>';
}
layout_close();
