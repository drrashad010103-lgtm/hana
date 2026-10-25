<?php
// lib/cfg.php — لوحة تحكم المحل: إعدادات + سجل الموديولات + أعلام المستخدم الحساسة
// المصدر: app_settings (k,v) للإعدادات + companies للهوية + users لأعلام الحقول
if (!function_exists('db')) require_once __DIR__ . '/db.php';

if (!function_exists('cfg_is_admin')) {
  if (!defined('CFG_DEF_NAME')) define('CFG_DEF_NAME', 'النسخة الحرة');

function cfg_is_admin(): bool {
  if (session_status() === PHP_SESSION_NONE) session_start();
  return (($_SESSION['u'] ?? '') === 'admin');
}
// —— إعدادات عامة (app_settings) — مفتاح مفقود = القيمة الافتراضية ——
function cfg_get(string $k, string $d = ''): string {
  static $mem = null;
  if ($mem === null) {
    $mem = [];
    try { foreach (db()->query("SELECT k, v FROM app_settings") as $r) $mem[(string)$r['k']] = (string)$r['v']; } catch (Throwable $t) { $mem = null; }
    if ($mem === null) { // كتلة أول ثلث: الجدول بسيط فين — ينشأ مرة واحدة ثم تُعاد القراءة
      try { db()->exec("CREATE TABLE IF NOT EXISTS app_settings (k VARCHAR(64) PRIMARY KEY, v TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch (Throwable $t2) {}
      $mem = [];
    }
  }
  return array_key_exists($k, $mem) ? $mem[$k] : $d;
}
function cfg_set(string $k, string $v): void {
  static $st = null;
  if ($st === null) $st = db()->prepare("INSERT INTO app_settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)");
  $st->execute([$k, $v]);
}
// —— خريطة الصلاحيات (مصدر واحد — تستخدمها guard.php والقوائم) ——
function cfg_perm_map(): array {
  return [
    'sale_rs.php' => 'm_sale', 'sale.php' => 'm_sale', 'api_sale_find.php' => 'm_sale', 'api_sale_load.php' => 'm_sale', 'api_sale_nav.php' => 'm_sale', 'api_sale_save.php' => 'm_sale', 'print_inv.php' => 'm_sale', 'msale.php' => 'm_sale',
    'buy_rs.php' => 'm_buy', 'buy.php' => 'm_buy', 'api_buy_find.php' => 'm_buy', 'api_buy_load.php' => 'm_buy', 'api_buy_nav.php' => 'm_buy', 'api_buy_save.php' => 'm_buy',
    'api_item.php' => 'm_item', 'api_item_full.php' => 'm_item', 'api_last_sale.php' => 'm_item', 'api_last_buy.php' => 'm_item', 'api_buy_item.php' => 'm_item', 'api_item_add.php' => 'm_item',
    'stock.php' => 'm_stock', 'items.php' => 'm_stock', 'item_edit.php' => 'm_stock', 'barcode.php' => 'm_stock', 'gardscan.php' => 'm_stock', 'refresh_bal.php' => 'm_stock', 'predict.php' => 'm_stock', 'order.php' => 'm_stock',
    'reports.php' => 'm_reports', 'board.php' => 'm_reports', 'home.php' => 'm_reports', 'dash.php' => 'm_reports',
    'money.php' => 'm_money', 'transfers.php' => 'm_money', 'ext_voucher.php' => 'm_money', 'vouchers.php' => 'm_money', 'bank_rep.php' => 'm_money', 'day_rep.php' => 'm_money', 'shift_rep.php' => 'm_money', 'shifts.php' => 'm_money', 'acc.php' => 'm_money', 'journal.php' => 'm_money', 'trial.php' => 'm_money',
    'customers.php' => 'm_cust', 'cust_bal.php' => 'm_cust', 'cust_groups.php' => 'm_cust', 'lastpay.php' => 'm_cust', 'coupons.php' => 'm_cust', 'vouchershop.php' => 'm_cust', 'whatsapp.php' => 'm_cust', 'loyalty.php' => 'm_cust', 'api_loyalty.php' => 'm_cust', 'api_cust_quick.php' => 'm_cust',
    'admin.php' => 'm_admin', 'users.php' => 'm_admin', 'user_mgmt.php' => 'm_admin', 'company.php' => 'm_admin', 'offers.php' => 'm_admin', 'api_coupon.php' => 'm_sale', 'defs.php' => 'm_admin', 'schema.php' => 'm_admin', 'snapshot.php' => 'm_admin', 'restore.php' => 'm_admin', 'backup.php' => 'm_admin', 'audit.php' => 'm_admin', 'close_year.php' => 'm_admin', 'easy.php' => 'm_admin', 'api_easy_sync.php' => 'm_admin', 'sync.php' => 'm_admin', 'bridge.php' => 'm_admin', 'theme.php' => 'm_admin', 'dups.php' => 'm_admin', 'strings.php' => 'm_admin',
    'branches.php' => 'm_admin', 'safes.php' => 'm_admin', 'stocks.php' => 'm_admin', 'banks.php' => 'm_admin', 'expenses.php' => 'm_admin', 'maingroups.php' => 'm_admin', 'units.php' => 'm_admin', 'areas.php' => 'm_admin', 'hr_depts.php' => 'm_admin', 'hr_emp.php' => 'm_admin', 'hr_sales.php' => 'm_admin', 'hr_comm.php' => 'm_admin', 'salary.php' => 'm_admin',
    'settings.php' => 'm_admin', 'control.php' => 'm_admin', 'api_control.php' => 'm_admin',
  ];
}
function cfg_perm_of(string $file): ?string {
  $m = cfg_perm_map();
  return $m[$file] ?? null;
}
// —— سجل الموديولات: شاشة لوحة + قوائم main.php المشتقة من هنا ——
function cfg_tabs(): array {
  return [
    'basic' => 'البيانات الأساسية', 'cust' => 'العملاء', 'emp' => 'شئون الموظفين', 'inv' => 'مبيعات ومشتريات',
    'rep' => 'تقارير بيع وشراء', 'store' => 'المخازن', 'money' => 'أموال', 'promo' => 'العروض والخصومات',
    'sys' => 'إعدادات النظام', 'about' => 'عن البرنامج',
  ];
}
function cfg_tools(): array {
  return [
    'basic' => [['🏢','بيانات الشركة','company.php'],['🌿','تعريف الفروع','branches.php'],['📒','قيود افتتاحية','vouchers.php'],['🗄️','تعريف الخزن','safes.php'],['📦','تعريف المخازن','stocks.php'],['🏦','تعريف البنوك','banks.php'],['💸','تعريف مصروفات','expenses.php'],['📂','مجموعات رئيسية','maingroups.php'],['🏷️','تعريف الأصناف','item_edit.php'],['📏','الوحدات','units.php'],['🗺️','بيانات المناطق','areas.php']],
    'cust' => [['💬','واتساب','whatsapp.php'],['👥','بيانات العملاء','customers.php'],['⭐','نقاطي','loyalty.php'],['💵','أرصدة حسابات العملاء','cust_bal.php'],['💰','حركة آخر سداد للعملاء','lastpay.php'],['🗂️','مجموعات العملاء','cust_groups.php'],['🎟️','كوبون خصم','coupons.php'],['🎫','قسيمة مشتريات','vouchershop.php']],
    'emp' => [['💰','صرف الرواتب','salary.php'],['🏢','تعريف الإدارات','hr_depts.php?mode=dept'],['🏬','تعريف الأقسام','hr_depts.php?mode=sec'],['👤','تعريف الموظف','hr_emp.php'],['🧑‍💼','تعريف بائعين','hr_sales.php?mode=seller'],['📊','تقرير عمولة بائعين','hr_comm.php?type=seller'],['🛵','تعريف دليفري','hr_sales.php?mode=delivery'],['📋','تقرير عمولة دليفري','hr_comm.php?type=delivery']],
    'inv' => [['📱','فاتورة موبايل','msale.php'],['🛒','فاتورة مشتريات','buy_rs.php'],['↩️','مرتجع مشتريات','buy_rs.php?mode=ret'],['🧾','فاتورة مبيعات','sale_rs.php'],['↩️','مرتجع مبيعات','sale_rs.php?mode=ret'],['📋','عرض سعر خدمات','sale_rs.php?mode=quot'],['🖨️','طباعة باركود','barcode.php'],['🟢','الورديات','shifts.php']],
    'rep' => [['📊','الداشبورد الحية','board.php'],['📊','داشبورد المحل','dash.php'],['📅','تقارير بالتواريخ','reports.php?r=dates'],['🧾','فواتير بالعملاء','reports.php?r=custinv'],['📝','مبيعات ومشتريات تفصيلية','reports.php?r=detail'],['💰','أرباح مبيعات','reports.php?r=profit'],['📆','حركة يومية','reports.php?r=daily'],['🔍','تحليل الحركات','reports.php?r=anal'],['📊','إجمالي المبيعات والمشتريات','reports.php?r=totals'],['💳','مبيعات طرق الدفع','reports.php?r=paym'],['⚠️','أصناف وصلت حد الطلب','reports.php?r=reorder'],['📦','الكميات والإيرادات','reports.php?r=qtyrev'],['🏦','أرباح حسابات رئيسية','reports.php?r=accmain'],['📉','أرباح وخسائر حسابات عامة','reports.php?r=acpnl'],['📑','أرباح حسابات تحليلية','reports.php?r=accanal']],
    'store' => [['🔮','تنبؤ المخزون الذكي','predict.php'],['🧠','غرفة الطلبية','order.php'],['📦','مخزون أول المدة','stock.php?r=opening'],['🔄','حركات المخازن','stock.php?r=moves'],['📑','تقرير تحويلات المخازن','stock.php?r=transfers'],['📋','كشف تجهيز الجرد','stock.php?r=gardprep'],['📦','كشف جرد المخازن','stock.php?r=gard'],['🧮','جرد سكانر','gardscan.php'],['📊','أرصدة المخزن حتى تاريخ','stock.php?r=balances'],['🔍','حركة صنف محددة','stock.php?r=item'],['📈','صنف بإجمالي الحركات','stock.php?r=itemtot'],['⏰','أصناف سنتهتي تاريخها','stock.php?r=expiry'],['🏬','جرد مخزن سنتر','stock.php?r=center']],
    'sys' => [['🎛️','لوحة تحكم المحل','settings.php'],['🔗','مزامنة EasyOrders','easy.php'],['🛠️','لوحة التحكم (باك إند)','admin.php'],['🔄','كوبري المزامنة','sync.php'],['➕','إضافة مستخدم','user_mgmt.php'],['⚙️','خصائص المستخدم','users.php'],['🔢','أكواد مسلسلة','schema.php?t=inv'],['🎨','إعدادات الشاشات','theme.php'],['💾','نسخة احتياطية','snapshot.php'],["\u{21C4}","\u{0627}\u{0644}\u{0643}\u{0648}\u{0628}\u{0631}\u{064A}","bridge.php"]],
    'about' => [['🌿','عن البرنامج','about.php'],['🚪','الخروج من البرنامج','about.php?exit=1']],
    'promo' => [['🏷️','عروض الأصناف','offers.php']],
    'money' => [['💸','تحويل بين خزائن','transfers.php'],['🧾','سندات قبض للخزينة','money.php?r=in'],['🧾','سندات صرف من الخزينة','money.php?r=out'],['📑','تقرير تحويلات الخزائن','transfers.php?tab=rep'],['💰','أرصدة حسابات الخزن','transfers.php?tab=bal'],['📒','حركة خزينة','transfers.php?tab=moves'],['📊','تقرير مصروفات','money.php?r=moves'],['🏦','تقرير البنوك','bank_rep.php'],['📥','سندات قبض خارجية','ext_voucher.php?mode=in'],['📤','سندات صرف خارجية','ext_voucher.php?mode=out'],['📋','تقرير الورديات','shift_rep.php'],['📋','تقرير اليوميات','day_rep.php'],['📒','شجرة الحسابات','acc.php'],['📕','دفتر اليومية (قيد مزدوج)','journal.php'],['⚖️','ميزان المراجعة','trial.php?r=trial'],['🔍','كشف حساب تفصيلي','trial.php?r=ledger']],
  ];
}
// شاشات الموديولات (بالمفتاح = اسم الملف) — القائمة في settings.php
function cfg_screens(): array {
  return [
    'company.php' => ['🏢','بيانات الشركة'], 'branches.php' => ['🌿','تعريف الفروع'], 'vouchers.php' => ['📒','قيود افتتاحية'],
    'safes.php' => ['🗄️','تعريف الخزن'], 'stocks.php' => ['📦','تعريف المخازن'], 'banks.php' => ['🏦','تعريف البنوك'],
    'expenses.php' => ['💸','تعريف مصروفات'], 'maingroups.php' => ['📂','المجموعات الرئيسية'], 'item_edit.php' => ['🏷️','تعريف الأصناف'],
    'units.php' => ['📏','الوحدات'], 'areas.php' => ['🗺️','بيانات المناطق'],
    'whatsapp.php' => ['💬','واتساب'], 'customers.php' => ['👥','بيانات العملاء'], 'loyalty.php' => ['⭐','نقاط الولاء'],
    'cust_bal.php' => ['💵','أرصدة العملاء'], 'lastpay.php' => ['💰','آخر سداد'], 'cust_groups.php' => ['🗂️','مجموعات العملاء'],
    'coupons.php' => ['🎟️','كوبونات الخصم'], 'vouchershop.php' => ['🎫','قسيمة مشتريات'],
    'salary.php' => ['💰','صرف الرواتب'], 'hr_depts.php' => ['🏢','تعريف الإدارات'], 'hr_emp.php' => ['👤','تعريف الموظف'],
    'hr_sales.php' => ['🧑‍💼','تعريف بائعين'], 'hr_comm.php' => ['📋','عمولة بائعين/دليفري'],
    'msale.php' => ['📱','فاتورة موبايل'], 'buy_rs.php' => ['🛒','فاتورة مشتريات'], 'sale_rs.php' => ['🧾','فاتورة مبيعات'],
    'barcode.php' => ['🖨️','طباعة باركود'], 'shifts.php' => ['🟢','الورديات'],
    'board.php' => ['📊','الداشبورد الحية'], 'dash.php' => ['📊','داشبورد المحل'], 'reports.php' => ['📅','تقارير التواريخ'],
    'dups.php' => ['🧾','تقرير تجميعي'], 'strings.php' => ['📝','نصوص البرنامج'],
    'predict.php' => ['🔮','تنبؤ المخزون'], 'order.php' => ['🧠','غرفة الطلبية'],     'stock.php' => ['📦','تقارير المخازن'], 'gardscan.php' => ['🧮','جرد سكانر'],
    'transfers.php' => ['💸','تحويل بين خزائن'], 'money.php' => ['🧾','سندات الخزينة'], 'bank_rep.php' => ['🏦','تقرير البنوك'],
    'ext_voucher.php' => ['📥','سندات خارجية'], 'shift_rep.php' => ['📋','تقرير الورديات'], 'day_rep.php' => ['📋','تقرير اليوميات'],
    'acc.php' => ['📒','شجرة الحسابات'], 'journal.php' => ['📕','دفتر اليومية'], 'trial.php' => ['⚖️','ميزان وكشوف'],
    'offers.php' => ['🏷️','عروض الأصناف'],
    'easy.php' => ['🔗','مزامنة EasyOrders'], 'admin.php' => ['🛠️','لوحة الباك إند'], 'sync.php' => ['🔄','كوبري المزامنة'],
    'user_mgmt.php' => ['➕','إضافة مستخدم'], 'users.php' => ['⚙️','خصائص المستخدم'], 'schema.php' => ['🔢','أكواد مسلسلة'],
    'snapshot.php' => ['💾','نسخ احتياطية'], 'settings.php' => ['🎛️','لوحة تحكم المحل'], 'control.php' => ['🛠️','لوحة التحكم الشاملة'], 'theme.php' => ['🎨','إعدادات الشاشات'],
    'bridge.php' => ["\u{21C4}", "\u{0627}\u{0644}\u{0643}\u{0648}\u{0628}\u{0631}\u{064A}"],
    'about.php' => ['🌿','عن البرنامج'],
  ];
}
// غياب المفتاح يترك الموديول مفتوحاً، لكن تعذر قراءة الإعداد يمنع الوصول.
function cfg_mod_allow(string $screen): bool {
  if (cfg_is_admin()) return true;
  try {
    $db = db();
    try {
      $st = $db->prepare('SELECT v FROM app_settings WHERE k=? LIMIT 1');
      $st->execute(['mod_' . $screen]);
    } catch (PDOException $e) {
      if (($e->errorInfo[1] ?? null) !== 1146 && $e->getCode() !== '42S02') throw $e;
      $db->exec("CREATE TABLE IF NOT EXISTS app_settings (k VARCHAR(64) PRIMARY KEY, v TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      $st = $db->prepare('SELECT v FROM app_settings WHERE k=? LIMIT 1');
      $st->execute(['mod_' . $screen]);
    }
    $value = $st->fetchColumn();
  } catch (Throwable $e) {
    error_log('cfg_mod_allow failed: ' . $e->getMessage());
    return false;
  }
  return $value === false || (string)$value !== '0';
}
// —— هوية المحل (companies) ——
function cfg_shop(): array {
  static $c = null;
  if ($c === null) {
    $row = null;
    try { $row = db()->query("SELECT name, name_en, Tel, Mobile, Address, tax_no, activity FROM companies ORDER BY id LIMIT 1")->fetch(); } catch (Throwable $t) {}
    $c = [
      'name' => trim((string)($row['name'] ?? '')) ?: CFG_DEF_NAME,
      'name_en' => (string)($row['name_en'] ?? ''),
      'tel' => (string)($row['Tel'] ?? ''), 'mobile' => (string)($row['Mobile'] ?? ''),
      'address' => (string)($row['Address'] ?? ''), 'tax_no' => (string)($row['tax_no'] ?? ''),
      'activity' => (string)($row['activity'] ?? ''),
      'logo' => cfg_get('shop_logo', 'assets/logo.png'),
    ];
  }
  return $c;
}
function cfg_shop_name(): string { return cfg_shop()['name']; }
// مسار اللوجو — الصفحات داخل مجلد pages تسبقه بـ ../ (الافتراضي assets/logo.png)
function cfg_logo_src(string $prefix = '../'): string {
  $p = cfg_shop()['logo'];
  return $prefix . (preg_match('~^assets/~', $p) ? $p : 'assets/' . $p);
}
// —— طرق الدفع (جدول pay_methods — يُنشأ ويتزرع أول مرة) ——
function cfg_pay_methods(bool $all = false): array {
  static $rows = null, $byAllRef = null;
  if ($rows === null) {
    $rows = [];
    try {
      if (!db()->query("SHOW TABLES LIKE 'pay_methods'")->fetch()) {
        db()->exec("CREATE TABLE pay_methods (id INT NOT NULL PRIMARY KEY, name VARCHAR(64) NOT NULL, active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("INSERT INTO pay_methods (id, name, active) VALUES (1,'كاش',1),(2,'فيزا',1),(3,'محفظة',1),(4,'انستاباى',1)");
      }
      $rows = db()->query("SELECT id, name, active FROM pay_methods ORDER BY id")->fetchAll();
    } catch (Throwable $t) { error_log('cfg_pay_methods: ' . $t->getMessage()); }
  }
  if ($all) return $rows;
  return array_values(array_filter($rows, function ($r) { return (int)$r['active'] === 1; }));
}
function cfg_pay_name(?int $id): string {
  foreach (cfg_pay_methods(true) as $r) if ((int)$r['id'] === (int)$id) return (string)$r['name'];
  return [1 => 'كاش', 2 => 'فيزا', 3 => 'محفظة', 4 => 'انستاباى'][(int)$id] ?? 'طريقة ' . (int)$id;
}
// أعلام المستخدم الحساسة (خصم/سعر/تاريخ/تعديل فاتورة) — Fail-Closed: الغياب أو NULL = منع (0)
function cfg_invoice_flags_from_row(?array $row, bool $readOk): array {
  $flags = ['edit_inv' => 0, 'edit_disc' => 0, 'edit_price' => 0, 'edit_date' => 0];
  if (!$readOk || $row === null) return $flags;
  foreach ($flags as $key => $default) {
    $flags[$key] = empty($row[$key]) ? 0 : 1;
  }
  return $flags;
}
function cfg_user_flags(int $uid): array {
  static $cache = [];
  if (isset($cache[$uid])) return $cache[$uid];
  $row = null;
  $readOk = false;
  if ($uid > 0) {
    try {
      $st = db()->prepare("SELECT edit_inv, edit_disc, edit_price, edit_date FROM users WHERE id=? LIMIT 1");
      $st->execute([$uid]);
      $row = $st->fetch() ?: null;
      $readOk = true;
      if ($row === null) {
        error_log('cfg_user_flags: no user found for uid=' . $uid);
      }
    } catch (Throwable $t) {
      error_log('cfg_user_flags failed for uid=' . $uid . ': ' . $t->getMessage());
    }
  } else {
    error_log('cfg_user_flags: missing uid; sensitive invoice actions denied');
  }
  $f = cfg_invoice_flags_from_row($row, $readOk);
  if (cfg_is_admin()) { foreach ($f as &$v) $v = 1; }
  return $cache[$uid] = $f;
}
}
