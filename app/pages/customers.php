<?php
// pages/customers.php — تعريف العملاء/الموردين (محرر كامل + بحث + كشف)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/customers.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $id = (int)($_POST['id'] ?? 0);
  $in = [
    'name' => $_POST['nm'] ?? '', 'act' => $_POST['tp'] ?? 1,
    'tax_no' => $_POST['tax'] ?? '', 'national_id' => $_POST['nid'] ?? '',
    'country' => $_POST['co'] ?? 0, 'city' => $_POST['ci'] ?? 0, 'area' => $_POST['ar'] ?? 0,
    'email' => $_POST['mail'] ?? '', 'mobile' => $_POST['mob'] ?? '',
    'mobile2' => $_POST['wa'] ?? '', 'fax' => $_POST['fax'] ?? '',
    'notes' => $_POST['notes'] ?? '', 'discount_balance' => $_POST['open'] ?? 0,
    'maxdepit' => $_POST['maxd'] ?? 0, 'accountNo' => $_POST['acc'] ?? '',
  ];
  $r = customer_save($in, $id);
  if (empty($r['ok'])) {
    $msg = '❌ ' . ($r['error'] ?? 'خطأ');
  } elseif (!empty($r['edited'])) {
    $msg = '✅ اتعدل';
  } else {
    $msg = '✅ اتضاف (' . $r['id'] . ')';
    $_GET['id'] = $r['id'];
  }
}
$id = (int)($_GET['id'] ?? 0);
$cos = $db->query("SELECT id, name FROM countries ORDER BY id")->fetchAll();
$cis = $db->query("SELECT id, name FROM cities ORDER BY id")->fetchAll();
$ars = $db->query("SELECT id, name FROM areas ORDER BY id LIMIT 200")->fetchAll();
$cur = $id > 0 ? $db->query("SELECT * FROM customers WHERE id=$id")->fetch() : null;
$q = trim($_GET['q'] ?? '');
$tab = $_GET['tab'] ?? 'data';
layout_open('تعريف العملاء / الموردين', '');
echo '<div class="card"><a href="?tab=data' . ($id ? '&id=' . $id : '') . '">بيانات</a> | <a href="?tab=search">البحث</a></div>';
if ($tab === 'search') {
  $rows = [];
  if ($q !== '') {
    $like = '%' . str_replace(['%', '_'], '', mb_substr($q, 0, 64)) . '%';
    $st = $db->prepare("SELECT id, name, act, mobile, tel, accountNo FROM customers WHERE IS_Deleted=0 AND (CAST(id AS CHAR)=? OR CAST(accountNo AS CHAR)=? OR name LIKE ? OR mobile LIKE ? OR tel LIKE ? OR mobile2 LIKE ?) ORDER BY id LIMIT 100");
    $st->execute([$q, $q, $like, "$q%", "$q%", "$q%"]);
    $rows = $st->fetchAll();
  }
  echo '<div class="card"><form class="row"><input type="hidden" name="tab" value="search"><div><label>🔍 بحث (كود / اسم / موبايل)</label><br><input name="q" style="width:280px" value="' . e($q) . '" placeholder="مثال: 3 أو 0102 أو سعد"></div><div><br><button>بحث</button></div></form>';
  if ($q !== '') echo '<div style="margin:6px 0;color:#666">النتائج: <b>' . count($rows) . '</b></div>';
  if ($rows) {
    echo '<table><tr><th>الكود</th><th>رقم الحساب</th><th>الاسم</th><th>الموبايل</th><th>التليفون</th><th>النوع</th></tr>';
    foreach ($rows as $r) echo '<tr><td><b>' . $r['id'] . '</b></td><td>' . e($r['accountNo'] ?? '') . '</td><td><a href="?id=' . $r['id'] . '">' . e($r['name']) . '</a></td><td>' . e($r['mobile'] ?? '') . '</td><td>' . e($r['tel'] ?? '') . '</td><td>' . (($r['act'] == 2) ? 'مورد' : 'عميل') . '</td></tr>';
    echo '</table>';
  } elseif ($q !== '') echo '<div class="note">لا نتائج للبحث: <b>' . e($q) . '</b></div>';
  echo '</div>';
} else {
  echo '<div class="card"><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">'
    . '<div><label>عميل <input type="radio" name="tp" value="1" ' . ((($cur['act'] ?? 1) == 1) ? 'checked' : '') . '></label> <label>عميل / مورد <input type="radio" name="tp" value="2" ' . ((($cur['act'] ?? 1) == 2) ? 'checked' : '') . '></label></div>'
    . '<div><label>الاسم</label><br><input name="nm" style="width:240px" value="' . e($cur['name'] ?? '') . '"></div>'
    . '<div><label>رقم الحساب</label><br><input name="acc" value="' . e($cur['accountNo'] ?? '') . '" dir="ltr"></div>'
    . '<div><label>الرقم الضريبي</label><br><input name="tax" value="' . e($cur['tax_no'] ?? '') . '"></div>'
    . '<div><label>الرقم القومي</label><br><input name="nid" value="' . e($cur['national_id'] ?? '') . '"></div>'
    . '<div><label>الحد الأعلى للأجل</label><br><input name="maxd" type="number" value="' . e($cur['maxdepit'] ?? 0) . '" style="width:90px"></div>'
    . '<div><label>الدولة</label><br><select name="co"><option value="0">—</option>';
  foreach ($cos as $c) echo '<option value="' . $c['id'] . '"' . (($cur && $cur['country'] == $c['id']) ? ' selected' : '') . '>' . e($c['name']) . '</option>';
  echo '</select></div><div><label>المدينة</label><br><select name="ci"><option value="0">—</option>';
  foreach ($cis as $c) echo '<option value="' . $c['id'] . '"' . (($cur && $cur['city'] == $c['id']) ? ' selected' : '') . '>' . e($c['name']) . '</option>';
  echo '</select></div><div><label>المنطقة</label><br><select name="ar"><option value="0">—</option>';
  foreach ($ars as $c) echo '<option value="' . $c['id'] . '"' . (($cur && $cur['area'] == $c['id']) ? ' selected' : '') . '>' . e($c['name']) . '</option>';
  echo '</select></div>'
    . '<div><label>البريد الإلكتروني</label><br><input name="mail" value="' . e($cur['email'] ?? '') . '" dir="ltr"></div>'
    . '<div><label>رقم المحمول</label><br><input name="mob" value="' . e($cur['mobile'] ?? '') . '"></div>'
    . '<div><label>رقم واتساب</label><br><input name="wa" value="' . e($cur['mobile2'] ?? '') . '"></div>'
    . '<div><label>فاكس</label><br><input name="fax" value="' . e($cur['fax'] ?? '') . '"></div>'
    . '<div><label>الرصيد الافتتاحي</label><br><input name="open" type="number" step="0.01" value="' . e($cur['discount_balance'] ?? 0) . '" style="width:110px"></div>'
    . '<div><label>تفاصيل</label><br><input name="notes" style="width:260px" value="' . e($cur['notes'] ?? '') . '"></div>'
    . '<div><br><button name="save" value="1">💾 حفظ</button> <a href="cust_bal.php?id=' . ($cur['id'] ?? 0) . '">📄 كشف حساب</a> <span>' . $msg . '</span></div></div></form>';
  if ($cur) {
    $t = $db->query("SELECT IFNULL(SUM(tot_net-paid),0) t FROM inv WHERE cust_id=" . $cur['id'] . " AND IS_Deleted=0")->fetch()['t'];
    echo '<div class="note">رصيد الفواتير الحالي: <b>' . number_format($t) . '</b></div>';
  }
  echo '</div>';
}
layout_close();
