<?php
// pages/items.php — تعريف الأصناف: بحث + بطاقة صنف كاملة + تعديل أسعار
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/items.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $r = item_price_save((int)($_POST['id'] ?? 0), [
    'sale' => $_POST['sale'] ?? 0, 'purch' => $_POST['purch'] ?? 0,
    'whole' => $_POST['whole'] ?? 0, 'barcode' => $_POST['bc'] ?? '',
  ]);
  $msg = !empty($r['ok']) ? '✅ اتحفظ' : '❌ ' . ($r['error'] ?? 'خطأ');
}
$q = trim($_GET['q'] ?? '');
$id = (int)($_GET['id'] ?? 0);
$rows = [];
if ($q !== '') {
  if (ctype_digit($q)) {
    $st = $db->prepare("SELECT id, symbol, barcode, sale_price, purch_price FROM currencies WHERE (id=? OR barcode=?) AND IS_Deleted=0 LIMIT 20");
    $st->execute([(int)$q, $q]);
  } else {
    $parts = array_values(array_filter(preg_split('/\s+/u', mb_substr($q, 0, 64))));
    $w = []; $p = [];
    foreach ($parts as $x) { $w[] = 'symbol LIKE ?'; $p[] = "%$x%"; }
    $st = $db->prepare("SELECT id, symbol, barcode, sale_price, purch_price FROM currencies WHERE " . implode(' AND ', $w) . " AND IS_Deleted=0 ORDER BY id LIMIT 50");
    $st->execute($p);
  }
  $rows = $st->fetchAll();
}
$card = null;
if ($id > 0) {
  $st = $db->prepare("SELECT c.*, g.name gname, u.name uname FROM currencies c LEFT JOIN `groups` g ON g.id=c.group_id LEFT JOIN units u ON u.id=c.unit WHERE c.id=? AND c.IS_Deleted=0");
  $st->execute([$id]);
  $card = $st->fetch();
  if ($card) {
    $st = $db->prepare("SELECT IFNULL((SELECT bal FROM item_bal WHERE id=?),0) b");
    $st->execute([$id]);
    $card['bal'] = round((float)($st->fetch()['b'] ?? 0), 2);
    $st = $db->prepare("SELECT DATE(i.date) d, s.val q, s.exchange_price p FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=? AND i.IS_Deleted=0 AND i.IS_Buy=1 AND i.proc_type IN (1,3) ORDER BY i.date DESC LIMIT 1");
    $st->execute([$id]);
    $card['lastb'] = $st->fetch();
    $st = $db->prepare("SELECT DATE(i.date) d, s.val q, s.exchange_price p FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=? AND i.IS_Deleted=0 AND i.IS_Buy=0 AND i.proc_type=1 ORDER BY i.date DESC LIMIT 1");
    $st->execute([$id]);
    $card['lasts'] = $st->fetch();
  }
}
layout_open('تعريف الأصناف', '');
echo '<div class="card"><form class="row"><div><label>بحث (اسم/كود/باركود)</label><br><input name="q" style="width:280px" value="' . e($q) . '" autocomplete="off"></div><div><br><button>بحث</button></div></form></div>';
if ($rows) {
  echo '<div class="card"><table><tr><th>الكود</th><th>الصنف</th><th>باركود</th><th>بيع</th><th>شراء</th></tr>';
  foreach ($rows as $r) echo '<tr><td><a href="?id=' . $r['id'] . '">' . $r['id'] . '</a></td><td><a href="?id=' . $r['id'] . '">' . e($r['symbol']) . '</a></td><td>' . e($r['barcode'] ?? '') . '</td><td>' . e($r['sale_price']) . '</td><td>' . e($r['purch_price']) . '</td></tr>';
  echo '</table></div>';
}
if ($card) {
  echo '<div class="card"><h3>🗂️ بطاقة صنف: ' . e($card['symbol']) . ' (' . $card['id'] . ')</h3>'
    . '<div class="row">'
    . '<div>المجموعة: <b>' . e($card['gname'] ?? '') . '</b></div><div>الوحدة: <b>' . e($card['uname'] ?? '') . '</b></div>'
    . '<div>الرصيد: <b>' . e($card['bal']) . '</b></div><div>باركود: <b>' . e($card['barcode'] ?? '') . '</b></div>'
    . '</div><div class="note">آخر شراء: ' . ($card['lastb'] ? e($card['lastb']['d'] . ' — ' . $card['lastb']['q'] . ' × ' . $card['lastb']['p']) : '—')
    . ' | آخر بيع: ' . ($card['lasts'] ? e($card['lasts']['d'] . ' — ' . $card['lasts']['q'] . ' × ' . $card['lasts']['p']) : '—') . '</div>'
    . '<form method="post" class="row"><input type="hidden" name="id" value="' . $card['id'] . '">'
    . '<div><label>سعر الشراء</label><br><input name="purch" type="number" step="0.01" value="' . e($card['purch_price']) . '"></div>'
    . '<div><label>سعر البيع</label><br><input name="sale" type="number" step="0.01" value="' . e($card['sale_price']) . '"></div>'
    . '<div><label>سعر الجملة</label><br><input name="whole" type="number" step="0.01" value="' . e($card['whole_price']) . '"></div>'
    . '<div><label>باركود</label><br><input name="bc" value="' . e($card['barcode'] ?? '') . '"></div>'
    . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></form></div>';
}
layout_close();
