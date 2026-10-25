<?php
// pages/company.php — بيانات الشركة (تبويبان: أساسية + فواتير) + إعدادات النظام
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/company.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
$set = function ($k, $def = '') use ($db) {
  $st = $db->prepare("SELECT v FROM app_settings WHERE k=?");
  $st->execute([$k]);
  $r = $st->fetch();
  return $r ? $r['v'] : $def;
};
if (($_POST['save'] ?? '') !== '') {
  $r = company_save([
    'name' => $_POST['nm'] ?? '', 'name_en' => $_POST['nmen'] ?? '', 'activity' => $_POST['act'] ?? '',
    'license_no' => $_POST['lic'] ?? '', 'comm_reg' => $_POST['cr'] ?? '',
    'Tel' => $_POST['tel'] ?? '', 'Mobile' => $_POST['mob'] ?? '', 'Fax' => $_POST['fax'] ?? '',
    'Email' => $_POST['mail'] ?? '', 'website' => $_POST['web'] ?? '',
    'tax_no' => $_POST['taxno'] ?? '', 'tax_pct' => $_POST['taxp'] ?? 0, 'Address' => $_POST['addr'] ?? '',
  ], (int)($_POST['id'] ?? 0));
  if (empty($r['ok'])) { $msg = '❌ ' . ($r['error'] ?? 'خطأ'); } else {
  foreach (['inv_header', 'inv_footer', 'inv_a5', 'screen1', 'screen2', 'allow_neg', 'fiscal_from', 'fiscal_to', 'currency', 'currency_part'] as $k) {
    $v = isset($_POST['s_' . $k]) ? (string)$_POST['s_' . $k] : '';
    $db->prepare("INSERT INTO app_settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$k, mb_substr($v, 0, 64)]);
  }
  $msg = '✅ اتحفظ';
  }
}
$c = $db->query("SELECT * FROM companies ORDER BY id LIMIT 1")->fetch();
$tab = $_GET['tab'] ?? 'main';
layout_open('بيانات الشركة', '');
echo '<div class="card"><a href="?tab=main">بيانات أساسية</a> | <a href="?tab=inv">إعدادات فواتير</a> | <a href="?tab=perm">صلاحيات المستخدمين</a> <span>' . $msg . '</span></div>';
$PERMS = [
  'شاشة تعريف الأصناف' => ['item_add' => 'جداول إضافة الأصناف', 'item_edit' => 'جداول تعديل على الأصناف', 'item_price' => 'جداول تسعير الأصناف', 'print_list_cashier' => 'طباعة قائمة الأصناف ورق كاشير', 'print_list_a4' => 'طباعة قائمة الأصناف A4'],
  'نشاطات الشراء والبيع' => ['auto_market' => 'فاتورة أوتوماتيك ماركت', 'change_inv_type' => 'تغيير نوع الفاتورة', 'edit_inv_date' => 'تعديل تاريخ الفواتير', 'show_cust_bal' => 'إظهار رصيد العميل', 'show_safe_bal' => 'إظهار رصيد الخزينة', 'hold_cashier' => 'تعليق الفواتير بالكاشير', 'cashier_collect' => 'موظف كاشير وتحصيل نقدية', 'show_expiry' => 'ظهور تاريخ الصلاحية', 'edit_buy_price' => 'تعديل سعر الشراء', 'edit_sale_price' => 'تعديل سعر البيع', 'inv_disc' => 'خصم على الفاتورة حتى %', 'tax_allow' => 'السماح بالضريبة حتى %', 'search_closed' => 'السماح ببحث فواتير ورديات مغلقة', 'free_return' => 'السماح بالمرتجع الحر', 'temp_save' => 'حفظ نسخة مؤقتة للفاتورة'],
  'رسائل دخول البرنامج' => ['low_stock_notify' => 'متابعة أصناف وصلت حد الطلب'],
];
if ($tab === 'perm') {
  $users = $db->query("SELECT id, username FROM users WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY id")->fetchAll();
  $uid = (int)($_GET['uid'] ?? ($users[0]['id'] ?? 0));
  if (($_POST['saveperm'] ?? '') !== '') {
    $uid = (int)$_POST['uid'];
    $db->prepare("DELETE FROM user_perms WHERE user_id=?")->execute([$uid]);
    $ins = $db->prepare("INSERT INTO user_perms (user_id, perm, val) VALUES (?,?,?)");
    foreach ($PERMS as $grp => $pp) foreach ($pp as $k => $t) $ins->execute([$uid, $k, !empty($_POST['p_' . $k]) ? 1 : 0]);
    $db->prepare("INSERT INTO user_perms (user_id, perm, val) VALUES (?,?,?) ON DUPLICATE KEY UPDATE val=VALUES(val)")->execute([$uid, 'inv_disc_pct', (float)($_POST['inv_disc_pct'] ?? 60)]);
    $db->prepare("INSERT INTO user_perms (user_id, perm, val) VALUES (?,?,?) ON DUPLICATE KEY UPDATE val=VALUES(val)")->execute([$uid, 'tax_pct', (float)($_POST['tax_pct'] ?? 60)]);
    $msg = '✅ اتحفظت الصلاحيات';
  }
  $pv = [];
  $st = $db->prepare("SELECT perm, val FROM user_perms WHERE user_id=?");
  $st->execute([$uid]);
  foreach ($st->fetchAll() as $r) $pv[$r['perm']] = $r['val'];
  $g = fn($k, $d = 0) => e($pv[$k] ?? $d);
  echo '<div class="card"><form method="get"><input type="hidden" name="tab" value="perm"><label>المستخدم</label> <select name="uid" onchange="this.form.submit()">';
  foreach ($users as $u) echo '<option value="' . $u['id'] . '"' . ($u['id'] == $uid ? ' selected' : '') . '>' . e($u['username']) . '</option>';
  echo '</select></form></div>';
  echo '<div class="card"><form method="post"><input type="hidden" name="uid" value="' . $uid . '"><div class="row">';
  foreach ($PERMS as $grp => $pp) {
    echo '<div style="min-width:260px"><h4>' . $grp . '</h4>';
    foreach ($pp as $k => $t) {
      if ($k === 'inv_disc') echo '<div><label>خصم على الفاتورة حتى % <input name="inv_disc_pct" type="number" value="' . $g('inv_disc_pct', 60) . '" style="width:60px"></label> <input type="checkbox" name="p_inv_disc" value="1" ' . (!empty($pv['inv_disc']) ? 'checked' : '') . '></div>';
      elseif ($k === 'tax_allow') echo '<div><label>السماح بالضريبة حتى % <input name="tax_pct" type="number" value="' . $g('tax_pct', 60) . '" style="width:60px"></label> <input type="checkbox" name="p_tax_allow" value="1" ' . (!empty($pv['tax_allow']) ? 'checked' : '') . '></div>';
      else echo '<div><label><input type="checkbox" name="p_' . $k . '" value="1" ' . (!empty($pv[$k]) ? 'checked' : '') . '> ' . $t . '</label></div>';
    }
    echo '</div>';
  }
  echo '</div><button name="saveperm" value="1">💾 حفظ الصلاحيات</button> <span>' . $msg . '</span></form></div>';
}
if ($tab === 'inv') {
  $g = fn($k, $d = '') => e($set($k, $d));
  echo '<div class="card"><form method="post"><h3>إعدادات فواتير</h3><div class="row">'
    . '<div><label><input type="checkbox" name="s_inv_header" value="1" ' . ($set('inv_header', '1') ? 'checked' : '') . '> طباعة هيدر الفاتورة</label></div>'
    . '<div><label><input type="checkbox" name="s_inv_footer" value="1" ' . ($set('inv_footer', '1') ? 'checked' : '') . '> طباعة فوتر الفاتورة</label></div>'
    . '<div><label><input type="checkbox" name="s_inv_a5" value="1" ' . ($set('inv_a5') ? 'checked' : '') . '> طباعة A5</label></div>'
    . '<div><label>شاشة الفواتير</label><br><label><input type="radio" name="s_screen1" value="1" checked> تفعيل شاشة الفواتير 1</label> <label><input type="radio" name="s_screen2" value="1"> تفعيل شاشة الفواتير 2</label></div>'
    . '<div><label>السنة المالية من</label><br><input name="s_fiscal_from" type="date" value="' . $g('fiscal_from', date('Y') . '-01-01') . '"></div>'
    . '<div><label>إلى</label><br><input name="s_fiscal_to" type="date" value="' . $g('fiscal_to', date('Y') . '-12-31') . '"></div>'
    . '<div><label>العملة المحلية</label><br><input name="s_currency" value="' . $g('currency', 'جنيه') . '" style="width:90px"></div>'
    . '<div><label>جزء العملة</label><br><input name="s_currency_part" value="' . $g('currency_part', 'قرش') . '" style="width:90px"></div>'
    . '<div><br><button name="save" value="1">💾 حفظ</button></div></div></form></div>';
} else {
  echo '<div class="card"><h3>ادخل بيانات المؤسسة</h3><form method="post"><input type="hidden" name="id" value="' . $c['id'] . '"><div class="row">'
    . '<div><label>الاسم AR</label><br><input name="nm" style="width:220px" value="' . e($c['name'] ?? '') . '"></div>'
    . '<div><label>EN</label><br><input name="nmen" style="width:220px" value="' . e($c['name_en'] ?? '') . '" dir="ltr"></div>'
    . '<div><label>النشاط التجاري</label><br><input name="act" style="width:220px" value="' . e($c['activity'] ?? '') . '"></div>'
    . '<div><label>رقم الرخصة</label><br><input name="lic" value="' . e($c['license_no'] ?? '') . '"></div>'
    . '<div><label>رقم سجل تجاري</label><br><input name="cr" value="' . e($c['comm_reg'] ?? '') . '"></div>'
    . '<div><label>رقم الهاتف</label><br><input name="tel" value="' . e($c['Tel'] ?? '') . '"></div>'
    . '<div><label>رقم الفاكس</label><br><input name="fax" value="' . e($c['Fax'] ?? '') . '"></div>'
    . '<div><label>رقم محمول</label><br><input name="mob" value="' . e($c['Mobile'] ?? '') . '"></div>'
    . '<div><label>الموقع الرسمي</label><br><input name="web" value="' . e($c['website'] ?? '') . '" dir="ltr"></div>'
    . '<div><label>البريد الإلكتروني</label><br><input name="mail" value="' . e($c['Email'] ?? '') . '" dir="ltr"></div>'
    . '<div><label>الضريبة Tax %</label><br><input name="taxp" type="number" step="0.01" value="' . e($c['tax_pct'] ?? 0) . '" style="width:80px"></div>'
    . '<div><label>رقم الضريبية</label><br><input name="taxno" value="' . e($c['tax_no'] ?? '') . '"></div>'
    . '<div><label>العنوان</label><br><input name="addr" style="width:300px" value="' . e($c['Address'] ?? '') . '"></div>'
    . '<div><label><input type="checkbox" name="s_allow_neg" value="1" ' . ($set('allow_neg', '1') ? 'checked' : '') . '> السماح بالبيع بدون كمية بالمخزن</label></div>'
    . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
}
layout_close();
