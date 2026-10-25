<?php
// pages/api_easy_sync.php — تشغيل مزامنة EasyOrders (stock / prices / costs / full)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
require_once __DIR__ . '/../lib/paths.php';
$__elog = app_data_dir() . '/logs';
if (!is_dir($__elog)) @mkdir($__elog, 0777, true);
if (is_dir($__elog) && is_writable($__elog)) ini_set('error_log', $__elog . '/easy_sync_errors.log');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/easyorders.php';
require_once __DIR__ . '/../lib/eo_item.php';
require_once __DIR__ . '/../lib/auth.php';
if (empty($_SESSION['uid'])) { echo json_encode(['ok'=>false,'error'=>'غير مسجل دخول']); exit; }
$db = db();
$act = $_GET['act'] ?? $_POST['act'] ?? '';
try {

// — استعلام منتجات EasyOrders لجلسة مزامنة —
if ($act === 'fetch') {
  $fetch = eo_fetch_all_products();
  if ($fetch['error'] !== null) { echo json_encode(['ok' => false, 'error' => $fetch['error']], JSON_UNESCAPED_UNICODE); exit; }
  $products = $fetch['items'];
  $map = [];
  foreach ($products as $p) {
    $slug = (string)($p['slug'] ?? '');
    if (preg_match('/^(\d+)/', $slug, $m)) $map[(int)$m[1]] = $p;
  }
  $_SESSION['eo_products'] = $map;
  echo json_encode(['ok' => true, 'total' => count($products), 'matched' => count($map)], JSON_UNESCAPED_UNICODE);
  exit;
}

// — مزامنة الأرصدة (و/أو الأسعار والتكاليف) —
if ($act === 'sync_stock') {
  $apply   = !empty($_POST['apply']);
  $prices  = !empty($_POST['prices']);
  $costs   = !empty($_POST['costs']);
  $moved   = !empty($_POST['moved']);   // أصناف متحركة فقط
  $limit   = min(500, max(0, (int)($_POST['limit'] ?? 0)));
  $log = [];
  $stats = ['total'=>0,'ok'=>0,'skip'=>0,'fail'=>0,'notfound'=>0];

  if (empty($_SESSION['eo_products'])) {
    $fetch = eo_fetch_all_products();
    if ($fetch['error'] !== null) { echo json_encode(['ok'=>false,'error'=>$fetch['error']], JSON_UNESCAPED_UNICODE); exit; }
    foreach ($fetch['items'] as $p) {
      $slug = (string)($p['slug'] ?? '');
      if (preg_match('/^(\d+)/', $slug, $m)) $_SESSION['eo_products'][(int)$m[1]] = $p;
    }
  }
  $eoMap = $_SESSION['eo_products'] ?? [];
  $bals = eo_read_balances();

  $targetIds = array_values(array_intersect(array_keys($bals), array_keys($eoMap)));
  if ($moved) {
    $since = date('Y-m-d H:i:s', time() - 86400);
    $mv = eo_moved_since($since);
    $targetIds = array_values(array_intersect($targetIds, $mv));
  }
  $cursor = (int)($_SESSION['eo_cursor'] ?? 0);
  $matchedTotal = count($targetIds);
  if ($cursor >= $matchedTotal) $cursor = 0;
  if ($limit > 0) $targetIds = array_slice($targetIds, $cursor, $limit);

  foreach ($targetIds as $rsId) {
    $stats['total']++;
    if (!isset($eoMap[$rsId])) { $stats['notfound']++; continue; }
    $fp = $eoMap[$rsId];
    $pid = trim((string)($fp['id'] ?? ''));
    if ($pid === '' || !preg_match('/^[0-9a-fA-F-]{36}$/', $pid)) { $stats['notfound']++; continue; }
    $newQty = max(0, (int)floor($bals[$rsId]['bal']));
    $rsSale = $bals[$rsId]['sale'];
    $rsCost = $bals[$rsId]['cost'];

    $curQty = (int)($fp['quantity'] ?? -1);
    $wantSale = ($prices && $rsSale > 0) ? (int)round($rsSale * 1.05) : null;
    $wantPrice = $wantSale !== null ? (int)round($wantSale * 1.18) : null;
    $wantCost = ($costs && $rsCost > 0) ? round($rsCost, 2) : null;

    $priceDiff = $wantSale !== null && (abs((float)($fp['sale_price'] ?? 0) - $wantSale) >= 0.005 || abs((float)($fp['price'] ?? 0) - $wantPrice) >= 0.005);
    $costDiff  = $wantCost !== null && (abs((float)($fp['expense'] ?? 0) - $wantCost) >= 0.005);
    $zeroStock = $newQty < 1;
    $flagsOk = ($fp['track_stock'] ?? false) && ($fp['disable_orders_for_no_stock'] ?? false);

    if ($curQty === $newQty && !$priceDiff && !$costDiff && (!$zeroStock || $flagsOk)) {
      $stats['skip']++; continue;
    }
    if (!$apply) { $stats['ok']++; $log[] = "[$rsId] qty {$curQty}→{$newQty}" . ($priceDiff?' price':'' ) . ($costDiff?' cost':''); if (count($log)>=50) break; continue; }

    // لازم نجيب المنتج كامل (GET /products/{id}) قبل أي PATCH — قائمة المنتجات مختصرة
    // وبدون ميتا/وصف/ترتيب، وبعت الـ PATCH بقائمة مختصرة بيمسح الحقول دي على إيزي أوردر.
    $full = eo_api('GET', "/products/$pid");
    if ($full['s'] !== 200 || !is_array($full['j'])) {
      $stats['fail']++; $log[] = "[$rsId] ❌ تعذر قراءة المنتج كامل (HTTP " . $full['s'] . ')'; continue;
    }
    $fresh = $full['j'];
    if (empty($fresh['name']) || !isset($fresh['price']) || empty($fresh['slug'])) { $stats['fail']++; $log[] = "[$rsId] ❌ بيانات ناقصة من المنتج"; continue; }
    $thumb = (string)($fresh['thumb'] ?? '');
    if ($thumb === '') { $stats['fail']++; $log[] = "[$rsId] ❌ مفيش صورة (الـ API ملزم بصورة)"; continue; }

    // الحقول المطلوبة إجبارياً في updateProductDto: name + price + slug + thumb
    $tu = ['name'=>$fresh['name'], 'price'=>$fresh['price'], 'slug'=>$fresh['slug'], 'thumb'=>$thumb, 'quantity'=>$newQty];
    if ($zeroStock) { $tu['track_stock'] = true; $tu['disable_orders_for_no_stock'] = true; }
    if ($wantSale !== null) { $tu['sale_price'] = $wantSale; $tu['sellPrice'] = $wantSale; $tu['price'] = $wantPrice; $tu['amount'] = $wantPrice; }
    if ($wantCost !== null) $tu['expense'] = $wantCost;
    if (!empty($fresh['categories'])) $tu['categories'] = array_map(fn($c) => ['id'=>$c['id']], $fresh['categories']);

    $sp = eo_safe_payload($fresh, $tu);
    unset($sp['id'], $sp['created_at'], $sp['updated_at'], $sp['store_id'], $sp['parsed_categories']);
    $pr = eo_api('PATCH', "/products/$pid", $sp);
    if ($pr['s'] === 200) { $stats['ok']++; $log[] = "[$rsId] ✅ qty=$newQty"; $eoMap[$rsId] = array_merge($fresh, $tu); }
    else {
      $stats['fail']++;
      $err = is_array($pr['j'] ?? null) ? mb_substr((string)($pr['j']['message'] ?? json_encode($pr['j'], JSON_UNESCAPED_UNICODE)), 0, 160) : 'HTTP ' . $pr['s'];
      $log[] = "[$rsId] ❌ $err";
    }
    if (count($log) >= 200) break;
  }
  $_SESSION['eo_products'] = $eoMap;
  $_SESSION['eo_cursor'] = $cursor + $stats['total'];
  echo json_encode(['ok'=>true,'apply'=>$apply,'stats'=>$stats,'log'=>$log,'from'=>$cursor+1,'to'=>$cursor+$stats['total'],'of'=>$matchedTotal], JSON_UNESCAPED_UNICODE);
  exit;
}

// — أصناف غير مرفوعة على الموقع —
if ($act === 'new_items') {
  eo_item_tbl($db);
  $eoMap = $_SESSION['eo_products'] ?? null;
  $eoIds = [];
  $eoPid = [];
  if (is_array($eoMap)) {
    foreach ($eoMap as $rsId => $p) {
      if (!empty($p['id'])) $eoIds[strtolower((string)$p['id'])] = true;
      $slug = (string)($p['slug'] ?? '');
      if (preg_match('/^(\d+)/', $slug, $m)) $eoPid[(int)$m[1]] = true;
    }
  }
  if (empty($eoMap)) {
    $fetch = eo_fetch_all_products();
    if ($fetch['error'] !== null) { echo json_encode(['ok'=>false,'error'=>$fetch['error']], JSON_UNESCAPED_UNICODE); exit; }
    $map = [];
    foreach ($fetch['items'] as $p) {
      if (!empty($p['id'])) $eoIds[strtolower((string)$p['id'])] = true;
      $slug = (string)($p['slug'] ?? '');
      if (preg_match('/^(\d+)/', $slug, $m)) { $map[(int)$m[1]] = $p; $eoPid[(int)$m[1]] = true; }
    }
    $_SESSION['eo_products'] = $map;
  }
  $rows = $db->query("SELECT c.id, c.symbol, c.sale_price, c.purch_price, c.group_id, c.imge, c.web_sku,
                             IFNULL(g.name,'') AS gname, IFNULL(g.web_id,'') AS web_cat, IFNULL(b.bal,0) AS bal,
                             IFNULL(m.path,'') AS img_path, IFNULL(m.url,'') AS img_remote
                        FROM currencies c
                   LEFT JOIN item_bal b ON b.id = c.id
                   LEFT JOIN `groups` g ON g.id = c.group_id
                   LEFT JOIN eo_item_img m ON m.id = c.id
                       WHERE c.IS_Deleted = 0
                    ORDER BY c.id")->fetchAll(PDO::FETCH_ASSOC);
  $items = [];
  foreach ($rows as $r) {
    $id = (int)$r['id'];
    $sku = strtolower(trim((string)$r['web_sku']));
    $uploaded = ($sku !== '' && isset($eoIds[$sku])) || isset($eoPid[$id]);
    if ($uploaded) continue;
    $imgPath = trim((string)$r['img_path']);
    $imgRemote = trim((string)$r['img_remote']);
    $preview = '';
    if ($imgPath !== '') {
      $abs = dirname(__DIR__) . '/' . str_replace('\\', '/', $imgPath);
      if (is_file($abs)) $preview = 'item_img.php?id=' . $id . '&v=' . filemtime($abs);
    }
    $items[] = [
      'id'    => $id,
      'name'  => (string)$r['symbol'],
      'group' => (string)$r['gname'],
      'bal'   => (float)$r['bal'],
      'sale'  => (float)$r['sale_price'],
      'cost'  => (float)$r['purch_price'],
      'img'   => $preview !== '' ? $preview : (preg_match('~^https?://~i', trim((string)$r['imge'])) ? trim((string)$r['imge']) : ''),
      'has_img' => ($preview !== '' || preg_match('~^https?://~i', $imgRemote)),
      'hosted'  => preg_match('~^https?://~i', $imgRemote) ? 1 : 0,
    ];
  }
  $thumb = 'https://files.easy-orders.net/1786895273437938889.webp';
  try { $sv = $db->prepare("SELECT v FROM app_settings WHERE k='eo_new_thumb' LIMIT 1"); $sv->execute(); $tv = $sv->fetchColumn(); if ($tv) $thumb = (string)$tv; } catch (Throwable $t) {}
  echo json_encode(['ok'=>true,'items'=>$items,'total'=>count($items),'thumb'=>$thumb], JSON_UNESCAPED_UNICODE);
  exit;
}

// — حفظ الصورة الافتراضية للمنتجات الجديدة —
if ($act === 'save_thumb') {
  $u = trim((string)($_POST['thumb'] ?? ''));
  if (!preg_match('~^https?://\S+$~i', $u)) { echo json_encode(['ok'=>false,'error'=>'رابط الصورة غير صالح'], JSON_UNESCAPED_UNICODE); exit; }
  $db->prepare("INSERT INTO app_settings (k,v) VALUES ('eo_new_thumb',?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$u]);
  echo json_encode(['ok'=>true,'thumb'=>$u], JSON_UNESCAPED_UNICODE);
  exit;
}

// — حفظ صورة الصنف (تُستضاف تلقائياً لاستخدامها على الموقع) —
if ($act === 'save_img') {
  $rsId = (int)($_POST['id'] ?? 0);
  $img = (string)($_POST['img'] ?? '');
  if ($img === '') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $rsId = (int)($in['id'] ?? $rsId);
    $img = (string)($in['img'] ?? '');
  }
  $r = eo_item_image_store($db, $rsId, $img);
  echo json_encode($r, JSON_UNESCAPED_UNICODE);
  exit;
}

// — تحديث صورة صنف مرفوع بالفعل على الموقع —
if ($act === 'push_img') {
  $rsId = (int)($_POST['id'] ?? 0);
  echo json_encode(eo_item_push_thumb($db, $rsId), JSON_UNESCAPED_UNICODE);
  exit;
}

// — رفع صنف جديد على EasyOrders —
if ($act === 'upload_new') {
  $rsId = (int)($_POST['id'] ?? 0);
  if ($rsId <= 0) { echo json_encode(['ok'=>false,'error'=>'كود الصنف غير صالح'], JSON_UNESCAPED_UNICODE); exit; }
  echo json_encode(eo_item_upload($db, $rsId), JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(['ok'=>false,'error'=>'unknown act']);
} catch (Throwable $t) {
  echo json_encode(['ok'=>false,'error'=>$t->getMessage()], JSON_UNESCAPED_UNICODE);
}
