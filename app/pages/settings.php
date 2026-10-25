<?php
// pages/settings.php — لوحة تحكم المحل: هوية المحل + إظهار/إخفاء الموديولات + نموذج الشغل
// (مالك admin فقط — من خريطة الصلاحيات) — يقف على: companies (هوية) + app_settings (إعدادات)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/company.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/i18n.php';
$db = db();
$msg = '';

// —— حفظ هوية المحل (صف companies الأول) ——
if (($_POST['save_shop'] ?? '') !== '') {
  $f = [
    'name' => mb_substr(trim($_POST['name'] ?? ''), 0, 190),
    'name_en' => mb_substr(trim($_POST['name_en'] ?? ''), 0, 128),
    'Tel' => mb_substr(trim($_POST['tel'] ?? ''), 0, 60),
    'Mobile' => mb_substr(trim($_POST['mobile'] ?? ''), 0, 60),
    'Address' => mb_substr(trim($_POST['address'] ?? ''), 0, 255),
    'tax_no' => mb_substr(trim($_POST['tax_no'] ?? ''), 0, 64),
    'activity' => mb_substr(trim($_POST['activity'] ?? ''), 0, 128),
  ];
  if ($f['name'] === '') {
    $msg = '❌ اسم المحل مطلوب';
  } else {
    $r = company_save([
      'name' => $f['name'], 'name_en' => $f['name_en'], 'Tel' => $f['Tel'],
      'Mobile' => $f['Mobile'], 'Address' => $f['Address'], 'tax_no' => $f['tax_no'], 'activity' => $f['activity'],
    ], 0);
    if (empty($r['ok'])) $msg = '❌ ' . ($r['error'] ?? 'خطأ');
    // رفع اللوجو (اختياري)
    if (!empty($_FILES['logo']['tmp_name']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($_FILES['logo']['name'] ?? '', PATHINFO_EXTENSION));
      if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
        $dir = __DIR__ . '/../assets';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $fn = 'shop_logo.' . $ext;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $dir . '/' . $fn)) {
          cfg_set('shop_logo', 'assets/' . $fn);
        } else $msg = '⚠️ الاسم اتحفظ لكن الرفع للوجو فشل (مجلد assets غير قابل للكتابة)';
      } else $msg = '⚠️ اللوجو: الامتداد غير مدعوم (png/jpg/webp)';
    }
    if (($_POST['clear_logo'] ?? '') !== '') {
      $cur = cfg_get('shop_logo', '');
      if ($cur !== '') { @unlink(__DIR__ . '/../' . $cur); cfg_set('shop_logo', 'assets/logo.png'); }
    }
    if ($msg === '') $msg = '✅ اتحفظت بيانات المحل — حدّث الصفحات لتطبيق الاسم الجديد';
  }
}

// —— إظهار / إخفاء الموديولات ——
if (($_POST['save_mods'] ?? '') !== '') {
  foreach (array_keys(cfg_screens()) as $sc) {
    cfg_set('mod_' . $sc, !empty($_POST['m'][$sc]) ? '1' : '0');
  }
  $msg = '✅ اتحفظت حالات الموديولات — القوائم اتعدلت فوراً';
}

// —— نموذج شغل جاهز (تجزئة) ——
if (($_POST['preset'] ?? '') !== '') {
  $P = $_POST['preset'] === 'retail' ? 'retail' : '';
  if ($P === 'retail') {
    // التجزئة: العمليات اليومية كلها مفتوحة — تعريفات الشركات المتعددة والمحاسبة المتقدمة مقفولة
    $off = ['branches.php', 'vouchers.php', 'safes.php', 'stocks.php', 'banks.php', 'areas.php',
            'vouchershop.php', 'ext_voucher.php', 'transfers.php', 'bank_rep.php', 'day_rep.php',
            'hr_depts.php', 'hr_emp.php', 'hr_sales.php', 'hr_comm.php'];
    foreach (array_keys(cfg_screens()) as $sc) cfg_set('mod_' . $sc, in_array($sc, $off, true) ? '0' : '1');
    $msg = '✅ اتطبق نموذج التجزئة — راجع الموديولات تحت وعدّل براحتك';
  }
}

$S = cfg_shop();
$sc = cfg_screens();
$byTab = [];
foreach ($sc as $base => $meta) {
  $grp = cfg_perm_of($base) ?: 'm_admin';
  $tab = match ($grp) {
    'm_sale', 'm_buy', 'm_item' => 'inv',
    'm_stock' => 'store',
    'm_reports' => 'rep',
    'm_money' => 'money',
    'm_cust' => 'cust',
    default => 'admin',
  };
  if (in_array($base, ['company.php', 'branches.php', 'vouchers.php', 'safes.php', 'stocks.php', 'banks.php', 'expenses.php', 'maingroups.php', 'item_edit.php', 'units.php', 'areas.php'], true)) $tab = 'basic';
  $byTab[$tab][] = $base;
}
$tabNames = ['basic' => 'البيانات الأساسية', 'cust' => 'العملاء', 'inv' => 'الفواتير', 'store' => 'المخازن', 'rep' => 'التقارير', 'money' => 'المال', 'admin' => 'النظام والإدارة'];

layout_open('لوحة تحكم المحل', '');
echo '<div class="note">🎛️ كل المحلات اللي تركّب النسخة بتضبط نفسهن من هنا — من غير لمس أي كود. اللي تتقفل من "الموديولات" يختفي من القوائم ويترفض الدخول ليه لغير المالك.</div>';

// ١) هوية المحل
echo '<div class="card"><h3>🏪 هوية المحل (تظهر في الشاشات وفوق الفاتورة المطبوعة)</h3><form method="post" enctype="multipart/form-data"><div class="row">'
  . '<div><label>اسم المحل</label><br><input name="name" style="width:230px" value="' . e($S['name']) . '"></div>'
  . '<div><label>الاسم (EN)</label><br><input name="name_en" dir="ltr" style="width:160px" value="' . e($S['name_en']) . '"></div>'
  . '<div><label>النشاط</label><br><input name="activity" placeholder="مستحضرات تجميل" style="width:170px" value="' . e($S['activity']) . '"></div>'
  . '<div><label>تليفون</label><br><input name="tel" dir="ltr" style="width:120px" value="' . e($S['tel']) . '"></div>'
  . '<div><label>موبايل</label><br><input name="mobile" dir="ltr" style="width:120px" value="' . e($S['mobile']) . '"></div>'
  . '<div><label>العنوان</label><br><input name="address" style="width:220px" value="' . e($S['address']) . '"></div>'
  . '<div><label>رقم ضريبي</label><br><input name="tax_no" dir="ltr" style="width:130px" value="' . e($S['tax_no']) . '"></div>'
  . '<div><label>شعار (png/jpg)</label><br><input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp"></div>'
  . '<div style="text-align:center"><label>الشعار الحالي</label><br><img src="' . e(cfg_logo_src('../')) . '" alt="" style="height:52px;border-radius:8px;background:#fff;padding:4px;box-shadow:0 2px 8px #0003"></div>'
  . '<div><br><label><input type="checkbox" name="clear_logo" value="1"> رجوع للشعار الافتراضي</label></div>'
  . '<div><br><button name="save_shop" value="1">💾 حفظ بيانات المحل</button></div></div><div class="note">' . e($msg) . '</div></form></div>';

// ٢) الموديولات
echo '<div class="card"><h3>🧩 الموديولات (مظهر = ظاهر في القوائم، إخفاء = مقفل تماماً)</h3><form method="post"><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px">';
foreach ($tabNames as $tk => $tn) {
  if (empty($byTab[$tk])) continue;
  echo '<div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:10px 12px"><b style="color:#6b21a8">' . e($tn) . '</b>';
  foreach ($byTab[$tk] as $base) {
    $on = cfg_is_admin() ? (cfg_get('mod_' . $base, '1') !== '0') : true;
    echo '<div style="display:flex;gap:8px;align-items:center;padding:5px 0;border-bottom:1px solid #f3e8ff"><input type="checkbox" name="m[' . e($base) . ']" value="1"' . ($on ? ' checked' : '') . '><span>' . $sc[$base][0] . ' ' . e($sc[$base][1]) . '</span><small style="margin-right:auto;color:#94a3b8">' . e($base) . '</small></div>';
  }
  echo '</div>';
}
echo '</div><br><button name="save_mods" value="1">💾 حفظ الموديولات</button></form></div>';

// ٣) نموذج الشغل
echo '<div class="card"><h3>🧭 نموذج الشغل (زر واحد يضبط المحل حسب النشاط)</h3><div class="cards">';
echo '<div style="background:linear-gradient(160deg,#4ade80 0%,#16a34a 45%,#065f46 100%);color:#fff;border-radius:16px;padding:18px;min-width:230px"><div style="font-size:34px">🛍️</div><b style="font-size:17px">تجزئة</b><div style="font-size:12.5px;opacity:.9;margin:6px 0 10px">بيع كاشير + موبايل + واتساب + ولاء وعروض + ورديات + خزينة + رواتب. مقفول: فروع / خزائن متعددة / مناطق / سندات خارجية / محاسبة متقدمة</div>'
  . '<form method="post" style="display:inline"><button name="preset" value="retail" onclick="return confirm(\'تطبيق نموذج التجزئة؟ الموديولات الحالية هتتعدل\')">⚡ تطبيق النموذج</button></form></div>';
echo '<div style="background:#f1f5f9;color:#334155;border-radius:16px;padding:18px;min-width:180px;opacity:.55"><div style="font-size:34px">📦</div><b>جملة</b><div style="font-size:12.5px;margin:6px 0">فواتير آجل والتأمينات والتحصيل — قريباً</div><button disabled>قريباً</button></div>';
echo '<div style="background:#f1f5f9;color:#334155;border-radius:16px;padding:18px;min-width:180px;opacity:.55"><div style="font-size:34px">💄</div><b>صالون</b><div style="font-size:12.5px;margin:6px 0">خدمات بالحجز بدل الأصناف — قريباً</div><button disabled>قريباً</button></div>';
echo '</div></div>';

$langNow = i18n_current();
$langOpts = '';
foreach (i18n_langs() as $lc => $ln) {
  $langOpts .= '<option value="' . e($lc) . '"' . ($lc === $langNow ? ' selected' : '') . '>' . e($ln) . '</option>';
}
echo '<div class="card"><h3>لغة البرنامج</h3>'
  . '<div class="row"><div><label>لغة الواجهة لهذا المستخدم</label><br>'
  . '<select onchange="location.href=\'lang.php?set=\'+encodeURIComponent(this.value)+\'&back=settings.php\'">' . $langOpts . '</select></div>';
if (($_SESSION['u'] ?? '') === 'admin') {
  echo '<div><form method="get" action="lang.php" style="display:flex;gap:8px;align-items:end">'
    . '<input type="hidden" name="back" value="settings.php"><input type="hidden" name="global" value="1">'
    . '<div><label>اللغة الافتراضية للجميع</label><br><select name="set">' . $langOpts . '</select></div>'
    . '<div><button type="submit">حفظ اللغة الافتراضية</button></div></form></div>';
}
echo '<div><a href="lang.php?set=ar&back=settings.php">العربية</a> · <a href="lang.php?set=en&back=settings.php">English</a> · <a href="lang.php?set=ur&back=settings.php">اردو</a> · <a href="lang.php?set=hi&back=settings.php">हिन्दी</a> · <a href="lang.php?set=bn&back=settings.php">বাংলা</a></div></div></div>';
layout_close();
