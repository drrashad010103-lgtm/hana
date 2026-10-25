<?php
// pages/cust_groups.php + coupons.php + vouchershop.php + lastpay.php — شاشات العملاء الأربع
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/shop.php';
$db = db();
$which = basename($_SERVER['SCRIPT_NAME'], '.php');
$msg = '';
if ($which === 'cust_groups') {
  $db->exec("CREATE TABLE IF NOT EXISTS cust_groups (id INT PRIMARY KEY AUTO_INCREMENT, name TEXT)");
  if (($_POST['add'] ?? '') !== '') {
    $r = cust_group_add($_POST['nm'] ?? '');
    $msg = !empty($r['ok']) ? '✅ اتضافت' : '❌ ' . ($r['error'] ?? 'خطأ');
  }
  if (($_POST['del'] ?? '') !== '') { cust_group_delete((int)$_POST['id']); $msg = '🗑️'; }
  $rows = $db->query("SELECT id, name FROM cust_groups ORDER BY id")->fetchAll();
  layout_open('مجموعات العملاء والموردين', '');
  echo '<div class="card"><form method="post" class="row"><div><label>مجموعات العملاء والموردين</label><br><input name="nm" style="width:260px"></div><div><br><button name="add" value="1">➕ إضافة</button> <span>' . $msg . '</span></div></form></div>';
  echo '<div class="card"><table><tr><th>مجموعات العملاء والموردين</th><th></th></tr>';
  foreach ($rows as $r) echo '<tr><td>' . e($r['name']) . '</td><td><form method="post" onsubmit="return confirm(\'حذف؟\')"><input type="hidden" name="id" value="' . $r['id'] . '"><button name="del" value="1">🗑️</button></form></td></tr>';
  echo '</table></div>';
} elseif ($which === 'coupons') {
  if (($_POST['save'] ?? '') !== '') {
    $r = coupon_save([
      'code_name' => $_POST['code'] ?? '', 'discount' => $_POST['pct'] ?? 0,
      'date_start' => $_POST['ds'] ?? '', 'date_end' => $_POST['de'] ?? '',
      'active' => !empty($_POST['act']), 'for_clients' => $_POST['scope'] ?? 0, 'clients_group' => $_POST['grp'] ?? 0,
    ], (int)($_POST['id'] ?? 0));
    $msg = !empty($r['ok']) ? '✅ اتحفظ' : '❌ ' . ($r['error'] ?? 'خطأ');
  }
  $rows = $db->query("SELECT * FROM coboun WHERE Is_deleted=0 OR Is_deleted IS NULL ORDER BY id DESC")->fetchAll();
  $cur = isset($_GET['id']) ? $db->query("SELECT * FROM coboun WHERE id=" . (int)$_GET['id'])->fetch() : null;
  $grps = $db->query("SELECT id, name FROM cust_groups ORDER BY id")->fetchAll();
  layout_open('كوبونات الخصم', '');
  echo '<div class="card"><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">'
    . '<div><label>الرقم</label><br><input value="' . ($cur['id'] ?? 'جديد') . '" readonly style="width:70px"></div>'
    . '<div><label>كود الكوبون</label><br><input name="code" value="' . e($cur['code_name'] ?? '') . '"></div>'
    . '<div><label>الخصم %</label><br><input name="pct" type="number" step="0.01" value="' . e($cur['discount'] ?? 0) . '" style="width:80px"></div>'
    . '<div><label>بداية الكوبون</label><br><input name="ds" type="date" value="' . e($cur['date_start'] ?? '') . '"></div>'
    . '<div><label>نهاية الكوبون</label><br><input name="de" type="date" value="' . e($cur['date_end'] ?? '') . '"></div>'
    . '<div><label>تطبيق</label><br><select name="scope"><option value="0">بالعميل</option><option value="1"' . (($cur['for_clients'] ?? 0) == 1 ? ' selected' : '') . '>لكل العملاء</option><option value="2"' . (($cur['for_clients'] ?? 0) == 2 ? ' selected' : '') . '>لمجموعة العملاء</option></select></div>'
    . '<div><label>المجموعة</label><br><select name="grp"><option value="0">—</option>';
  foreach ($grps as $g) echo '<option value="' . $g['id'] . '"' . (($cur && $cur['clients_group'] == $g['id']) ? ' selected' : '') . '>' . e($g['name']) . '</option>';
  echo '</select></div>'
    . '<div><label><input type="checkbox" name="act" value="1" ' . (!isset($cur) || !empty($cur['active']) ? 'checked' : '') . '> فعال</label></div>'
    . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
  echo '<div class="card"><table><tr><th>الكود</th><th>خصم %</th><th>من</th><th>إلى</th><th>فعال</th><th></th></tr>';
  foreach ($rows as $r) echo '<tr><td><b>' . e($r['code_name']) . '</b></td><td>' . $r['discount'] . '</td><td>' . e($r['date_start'] ?? '') . '</td><td>' . e($r['date_end'] ?? '') . '</td><td>' . (!empty($r['active']) ? '✅' : '—') . '</td><td><a href="?id=' . $r['id'] . '">فتح</a></td></tr>';
  echo '</table></div>';
} else {
  $id = (int)($_GET['id'] ?? 0);
  $fc = $id > 0 ? "AND i.cust_id=$id" : "";
  $rows = $db->query("SELECT cu.id, cu.name, cu.mobile, MAX(DATE(i.date)) lastd, SUM(i.paid) tp, SUM(i.tot_net-i.paid) bal FROM customers cu LEFT JOIN inv i ON i.cust_id=cu.id AND i.IS_Deleted=0 $fc GROUP BY cu.id, cu.name, cu.mobile HAVING tp>0 OR bal<>0 ORDER BY lastd DESC LIMIT 200")->fetchAll();
  layout_open('حركة آخر سداد للعملاء', '');
  echo '<div class="card"><form class="row"><div><label>اسم العميل (كود، 0=الكل)</label><br><input name="id" value="' . $id . '" style="width:100px"></div><div><br><button>عرض</button></div></form>'
    . '<table><tr><th>رقم الحساب</th><th>اسم العميل</th><th>الهاتف</th><th>إجمالي المسدد</th><th>آخر حركة</th><th>الرصيد</th></tr>';
  foreach ($rows as $r) echo '<tr><td>' . $r['id'] . '</td><td>' . e($r['name']) . '</td><td>' . e($r['mobile'] ?? '') . '</td><td>' . number_format($r['tp']) . '</td><td>' . ($r['lastd'] ?? '') . '</td><td><b>' . number_format($r['bal']) . '</b></td></tr>';
  echo '</table></div>';
}
layout_close();
