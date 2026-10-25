<?php
// pages/offers.php — تبويب العروض والخصومات (عروض أصناف + كوبونات + تقرير)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
// أعمدة العروض — تُنشأ تلقائياً لو ناقصة (كما على الاستضافة)
foreach (['offersale' => 'int(11) DEFAULT NULL', 'offer_price' => 'double DEFAULT NULL', 'offer_until' => 'datetime DEFAULT NULL'] as $col => $def) {
  if (!$db->query("SHOW COLUMNS FROM currencies LIKE " . $db->quote($col))->fetch()) $db->exec("ALTER TABLE currencies ADD COLUMN `$col` $def");
}
$msg = '';
if (($_POST['tg'] ?? '') !== '') {
  $id = (int)$_POST['tg'];
  $db->prepare("UPDATE currencies SET offersale=IF(offersale=1 AND (offer_until IS NULL OR offer_until>=NOW()),0,1), offer_until=NULL WHERE id=?")->execute([$id]);
  $msg = '✅ اتبدلت حالة العرض';
}
if (($_POST['setp'] ?? '') !== '' && is_array($_POST['price'] ?? null)) {
  // حفظ كل صفوف الجدول دفعة واحدة (أسماء فورم مصفوفة price[id]/until[id])
  $n = 0;
  foreach (($_POST['price'] ?? []) as $id => $price) {
    $id = (int)$id;
    if ($id <= 0) continue;
    if (trim((string)$price) === '') continue; // خانة فاضية = السيب زي ما هو
    $until = trim($_POST['until'][$id] ?? '') !== '' ? $_POST['until'][$id] : null;
    $db->prepare("UPDATE currencies SET offer_price=?, offersale=1, offer_until=? WHERE id=? AND IS_Deleted=0")->execute([max(0, (float)$price), $until, $id]);
    $n++;
  }
  $msg = "✅ اتحفظ $n سعر عرض";
}
if (($_POST['bulk'] ?? '') !== '' && (int)($_POST['grp'] ?? 0) > 0) {
  $pct = min(90, max(1, (float)($_POST['pct'] ?? 0)));
  $db->prepare("UPDATE currencies SET offer_price=ROUND(sale_price*(1-?/100),2), offersale=1, offer_until=NULL WHERE group_id=? AND IS_Deleted=0")->execute([$pct, (int)$_POST['grp']]);
  $msg = "✅ اتفعل عرض $pct% على المجموعة";
}
$q = trim($_GET['q'] ?? '');
$items = [];
if ($q !== '' || isset($_GET['on'])) {
  if (isset($_GET['on']))     $items = $db->query("SELECT id, symbol, sale_price, offer_price, CASE WHEN offersale=1 AND (offer_until IS NULL OR offer_until>=NOW()) THEN 1 ELSE 0 END offersale, offer_until FROM currencies WHERE offersale=1 AND IS_Deleted=0 ORDER BY id LIMIT 200")->fetchAll();
  else {
    $like = '%' . str_replace(['%', '_'], '', mb_substr($q, 0, 64)) . '%';
    $st = $db->prepare("SELECT id, symbol, sale_price, offer_price, CASE WHEN offersale=1 AND (offer_until IS NULL OR offer_until>=NOW()) THEN 1 ELSE 0 END offersale, offer_until FROM currencies WHERE IS_Deleted=0 AND (CAST(id AS CHAR)=? OR symbol LIKE ?) ORDER BY id LIMIT 50");
    $st->execute([$q, $like]); $items = $st->fetchAll();
  }
}
$groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
$stat = $db->query("SELECT COUNT(*) n, SUM(CASE WHEN offersale=1 AND (offer_until IS NULL OR offer_until>=NOW()) THEN 1 ELSE 0 END) onn FROM currencies WHERE IS_Deleted=0")->fetch();
$coups = $db->query("SELECT code_name, discount, date_start, date_end, active FROM coboun WHERE Is_deleted=0 OR Is_deleted IS NULL ORDER BY id DESC")->fetchAll();
layout_open('العروض والخصومات', '');
echo '<div class="cards" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">'
  . '<div class="card">🏷️ أصناف عليها عرض: <b>' . number_format($stat['onn']) . '</b> / ' . number_format($stat['n']) . '</div>'
  . '<div class="card">🎟️ <a href="coupons.php">الكوبونات</a> | 🎫 <a href="vouchershop.php">القسائم</a></div></div>';
echo '<div class="card"><h3>🏷️ عروض الأصناف</h3><form class="row" method="get"><div><label>بحث (كود/اسم)</label><br><input name="q" value="' . e($q) . '" style="width:220px"></div><div><br><button>بحث</button> <a href="?on=1"><button type="button">المفعلة فقط</button></a> <span>' . e($msg) . '</span></div></form>';
if ($items) {
  echo '<form method="post"><table><tr><th>الكود</th><th>الصنف</th><th>البيع</th><th>العرض</th><th>ينتهي في</th><th>الحالة</th><th></th></tr>';
  foreach ($items as $it) {
    $until = $it['offer_until'] ? date('Y-m-d\TH:i', strtotime($it['offer_until'])) : '';
    echo '<tr><td>' . $it['id'] . '</td><td>' . e($it['symbol']) . '</td><td>' . $it['sale_price'] . '</td>'
      . '<td><input name="price[' . $it['id'] . ']" type="number" step="0.01" value="' . e($it['offer_price'] ?? '') . '" style="width:90px"></td>'
      . '<td><input name="until[' . $it['id'] . ']" type="datetime-local" value="' . e($until) . '" style="width:170px"></td>'
      . '<td>' . ($it['offersale'] ? '✅ عرض' : '—') . '</td>'
      . '<td><button name="setp" value="1">💾 حفظ الكل</button> <button name="tg" value="' . $it['id'] . '">' . ($it['offersale'] ? '⏸️' : '▶️') . '</button></td></tr>';
  }
  echo '</table></form><div class="note">💾 يحفظ السعر ويفعل — لو حددت "ينتهي في" بيرجع السعر أصلي تلقائياً بعد المدة. فاضي = دائم. خانة السعر الفاضية تُسَاب زي ما هي. ▶️/⏸️ تبديل سريع.</div>';
}
echo '</div><div class="card"><h3>📦 عرض على مجموعة كاملة</h3><form class="row" method="post"><div><label>المجموعة</label><br><select name="grp">';
foreach ($groups as $g) echo '<option value="' . $g['id'] . '">' . e($g['name']) . '</option>';
echo '</select></div><div><label>خصم %</label><br><input name="pct" type="number" value="10" min="1" max="90" style="width:80px"></div><div><br><button name="bulk" value="1" onclick="return confirm(\'تفعيل العرض على كل أصناف المجموعة؟\')">تفعيل</button></div></form></div>';
echo '<div class="card"><h3>🎟️ الكوبونات (' . count($coups) . ')</h3><table><tr><th>الكود</th><th>خصم %</th><th>من</th><th>إلى</th><th>الحالة الآن</th></tr>';
$td = date('Y-m-d');
foreach ($coups as $c) {
  $live = !empty($c['active']) && (!$c['date_start'] || $c['date_start'] <= $td) && (!$c['date_end'] || $c['date_end'] >= $td);
  echo '<tr><td><b>' . e($c['code_name']) . '</b></td><td>' . $c['discount'] . '</td><td>' . e($c['date_start'] ?? '') . '</td><td>' . e($c['date_end'] ?? '') . '</td><td>' . ($live ? '✅ شغال' : '⏸️ واقف') . '</td></tr>';
}
echo '</table><div class="note">إدارة الكوبونات من <a href="coupons.php">صفحتها</a> — وتُستخدم في الفاتورة بخانة الكوبون.</div></div>';
layout_close();
