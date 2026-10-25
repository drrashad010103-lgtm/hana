<?php
// lib/eo_item.php — صور الأصناف + رفع الصنف على EasyOrders (مشترك بين المزامنة وكرت الصنف والفاتورة)
require_once __DIR__ . '/easyorders.php';

const EO_DEFAULT_THUMB = 'https://files.easy-orders.net/1786895273437938889.webp';

function eo_item_log(string $m): void {
  @file_put_contents(__DIR__ . '/../logs/eo_item.log', date('Y-m-d H:i:s') . ' ' . $m . "\n", FILE_APPEND | LOCK_EX);
}

function eo_item_tbl(PDO $db): void {
  static $done = false;
  if ($done) return;
  $db->exec("CREATE TABLE IF NOT EXISTS eo_item_img (
    id INT NOT NULL PRIMARY KEY,
    path VARCHAR(255) NOT NULL DEFAULT '',
    url VARCHAR(255) NOT NULL DEFAULT '',
    updated DATETIME DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $done = true;
}

function eo_item_uploads_dir(): string {
  return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'items';
}

function eo_item_mime_ext(string $bin, string $declared): array {
  if (strncmp($bin, "\xFF\xD8\xFF", 3) === 0) return ['image/jpeg', 'jpg'];
  if (strncmp($bin, "\x89PNG\r\n\x1A\n", 8) === 0) return ['image/png', 'png'];
  if (strncmp($bin, 'RIFF', 4) === 0 && substr($bin, 8, 4) === 'WEBP') return ['image/webp', 'webp'];
  return ['', ''];
}

function eo_item_host_file(string $abs, string $name): string {
  static $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  $mime = $mimes[$ext] ?? 'image/jpeg';
  $try = function (string $url, array $fields, array $opts = []) use ($abs, $name, $mime): string {
    if (!is_file($abs)) return '';
    $ch = curl_init($url);
    $fields = array_merge($fields, ['fileToUpload' => new CURLFile($abs, $mime, $name)]);
    curl_setopt_array($ch, array_replace([
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $fields,
      CURLOPT_CONNECTTIMEOUT => 8,
      CURLOPT_TIMEOUT        => 60,
      CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RS-Own/1.0',
      CURLOPT_FOLLOWLOCATION => true,
    ], $opts));
    $r = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($r === false || $code < 200 || $code >= 300) return '';
    $u = trim((string)$r);
    return preg_match('~^https?://\S+$~i', $u) ? $u : '';
  };
  // catbox (دائم)
  $u = $try('https://catbox.moe/user/api.php', ['reqtype' => 'fileupload']);
  if ($u !== '') return $u;
  // 0x0.st (احتياطي)
  $ch = curl_init('https://0x0.st');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['file' => new CURLFile($abs, $mime, $name)],
    CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 60,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RS-Own/1.0',
  ]);
  $r = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($r === false || $code < 200 || $code >= 300) return '';
  $u = trim((string)$r);
  return preg_match('~^https?://\S+$~i', $u) ? $u : '';
}

function eo_item_image_store(PDO $db, int $id, string $dataUri): array {
  if ($id <= 0) return ['ok' => false, 'error' => 'كود الصنف غير صالح'];
  if (!preg_match('~^data:image/(jpeg|jpg|png|webp);base64,([A-Za-z0-9+/=\r\n]+)$~i', trim($dataUri), $m)) {
    return ['ok' => false, 'error' => 'صيغة الصورة غير مدعومة (JPG / PNG / WEBP)'];
  }
  $bin = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
  if ($bin === false || strlen($bin) < 200) return ['ok' => false, 'error' => 'ملف الصورة تالف أو صغير جداً'];
  if (strlen($bin) > 3 * 1024 * 1024) return ['ok' => false, 'error' => 'حجم الصورة أكبر من 3 ميجا'];
  [$mime, $ext] = eo_item_mime_ext($bin, strtolower($m[1]));
  if ($ext === '') return ['ok' => false, 'error' => 'محتوى الملف ليس صورة صالحة'];

  $row = $db->prepare("SELECT id, symbol FROM currencies WHERE id=? AND IS_Deleted=0 LIMIT 1");
  $row->execute([$id]);
  $it = $row->fetch(PDO::FETCH_ASSOC);
  if (!$it) return ['ok' => false, 'error' => 'الصنف غير موجود'];

  $dir = eo_item_uploads_dir();
  if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) return ['ok' => false, 'error' => 'تعذر إنشاء مجلد الصور'];
  foreach (['jpg', 'png', 'webp'] as $e) { $old = $dir . DIRECTORY_SEPARATOR . $id . '.' . $e; if (is_file($old)) @unlink($old); }
  $abs = $dir . DIRECTORY_SEPARATOR . $id . '.' . $ext;
  if (@file_put_contents($abs, $bin) === false) return ['ok' => false, 'error' => 'تعذر حفظ الصورة'];

  $remote = eo_item_host_file($abs, $id . '.' . $ext);
  $rel = 'uploads/items/' . $id . '.' . $ext;
  eo_item_tbl($db);
  $db->prepare("INSERT INTO eo_item_img (id, path, url, updated) VALUES (?,?,?,NOW())
                ON DUPLICATE KEY UPDATE path=VALUES(path), url=IF(VALUES(url)<>'',VALUES(url),url), updated=NOW()")
     ->execute([$id, $rel, $remote]);
  try { $db->prepare("UPDATE currencies SET imge=? WHERE id=?")->execute([$rel, $id]); } catch (Throwable $t) {}
  eo_item_log("img id=$id bytes=" . strlen($bin) . " hosted=" . ($remote !== '' ? $remote : 'NO'));
  return ['ok' => true, 'id' => $id, 'local' => $rel, 'url' => $remote, 'remote' => $remote !== '',
    'preview' => 'item_img.php?id=' . $id . '&v=' . time(),
    'warning' => $remote === '' ? 'اتحفظت محلياً لكن تعذر رفعها للاستضافة — العلامة المائية هتستخدم الصورة الافتراضية' : ''];
}

function eo_item_image_row(PDO $db, int $id): ?array {
  eo_item_tbl($db);
  $st = $db->prepare("SELECT id, path, url, updated FROM eo_item_img WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}

function eo_item_remote_image(PDO $db, int $id): string {
  $r = eo_item_image_row($db, $id);
  if ($r && preg_match('~^https?://\S+$~i', (string)$r['url'])) return (string)$r['url'];
  return '';
}

function eo_item_thumb(PDO $db, int $id): string {
  $u = eo_item_remote_image($db, $id);
  if ($u !== '') return $u;
  try {
    $sv = $db->prepare("SELECT v FROM app_settings WHERE k='eo_new_thumb' LIMIT 1");
    $sv->execute();
    $tv = trim((string)$sv->fetchColumn());
    if (preg_match('~^https?://\S+$~i', $tv)) return $tv;
  } catch (Throwable $t) {}
  return EO_DEFAULT_THUMB;
}

function eo_item_row(PDO $db, int $id): ?array {
  $st = $db->prepare("SELECT c.id, c.symbol, c.sale_price, c.purch_price, c.imge, c.web_sku, c.group_id,
                             IFNULL(g.web_id,'') AS web_cat, IFNULL(b.bal,0) AS bal
                        FROM currencies c
                   LEFT JOIN item_bal b ON b.id = c.id
                   LEFT JOIN `groups` g ON g.id = c.group_id
                       WHERE c.id = ? AND c.IS_Deleted = 0 LIMIT 1");
  $st->execute([$id]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}

function eo_item_payload(PDO $db, int $id): array {
  $r = eo_item_row($db, $id);
  if (!$r) throw new RuntimeException('الصنف غير موجود');
  $name = trim((string)$r['symbol']);
  $sale = (float)$r['sale_price'];
  $cost = (float)$r['purch_price'];
  $bal  = (float)$r['bal'];
  if ($name === '') throw new RuntimeException('اسم الصنف فاضي');
  if ($sale <= 0) throw new RuntimeException('الصنف مالوش سعر بيع');
  $qty = max(0, (int)floor($bal));
  $siteSale = (int)round($sale * 1.05);
  $sitePrice = (int)round($sale * 1.05 * 1.18);
  $payload = [
    'name'        => $name,
    'slug'        => (string)$id,
    'price'       => $sitePrice,
    'sale_price'  => $siteSale,
    'quantity'    => $qty,
    'thumb'       => eo_item_thumb($db, $id),
    'track_stock' => ($qty < 1),
    'disable_orders_for_no_stock' => ($qty < 1),
  ];
  if ($cost > 0) $payload['expense'] = round($cost, 2);
  $cat = trim((string)$r['web_cat']);
  if ($cat !== '') $payload['categories'] = [['id' => $cat]];
  return $payload;
}

function eo_item_upload(PDO $db, int $id): array {
  $row = eo_item_row($db, $id);
  if (!$row) return ['ok' => false, 'error' => 'الصنف غير موجود'];
  $sku = trim((string)$row['web_sku']);
  if ($sku !== '') {
    $p = eo_item_push_thumb($db, $id);
    return $p + ['existing' => true, 'id' => $sku];
  }
  try { $payload = eo_item_payload($db, $id); }
  catch (Throwable $t) { return ['ok' => false, 'error' => $t->getMessage()]; }
  $pr = eo_api('POST', '/products', $payload);
  $ok = in_array((int)$pr['s'], [200, 201], true) && is_array($pr['j']['data'] ?? null);
  if (!$ok) {
    $err = is_array($pr['j'] ?? null) ? (string)($pr['j']['message'] ?? json_encode($pr['j'], JSON_UNESCAPED_UNICODE)) : ('HTTP ' . $pr['s']);
    eo_item_log("upload fail id=$id " . mb_substr($err, 0, 200));
    return ['ok' => false, 'error' => mb_substr($err, 0, 200)];
  }
  $nid = (string)($pr['j']['data']['id'] ?? '');
  $db->prepare("UPDATE currencies SET web_sku=?, slug=? WHERE id=?")->execute([$nid, (string)$id, $id]);
  if (isset($_SESSION['eo_products']) && is_array($_SESSION['eo_products'])) $_SESSION['eo_products'][$id] = $pr['j']['data'];
  eo_item_log("upload ok id=$id eo=$nid price={$payload['price']} qty={$payload['quantity']}");
  return ['ok' => true, 'id' => $nid, 'rs' => $id, 'slug' => (string)$id,
    'price' => $payload['price'], 'sale_price' => $payload['sale_price'], 'qty' => $payload['quantity'],
    'thumb' => $payload['thumb']];
}

function eo_item_push_thumb(PDO $db, int $id): array {
  $row = eo_item_row($db, $id);
  if (!$row) return ['ok' => false, 'error' => 'الصنف غير موجود'];
  $sku = trim((string)$row['web_sku']);
  if ($sku === '') return ['ok' => false, 'error' => 'الصنف غير مرفوع على الموقع'];
  $g = eo_api('GET', '/products/' . rawurlencode($sku));
  if (!in_array((int)$g['s'], [200, 201], true) || !is_array($g['j']['data'] ?? null)) {
    return ['ok' => false, 'error' => 'تعذر قراءة المنتج من الموقع (HTTP ' . $g['s'] . ')'];
  }
  $p = $g['j']['data'];
  foreach (['id', 'created_at', 'updated_at', 'store_id', 'parsed_categories'] as $k) unset($p[$k]);
  $p['thumb'] = eo_item_thumb($db, $id);
  $r = eo_api('PATCH', '/products/' . rawurlencode($sku), $p);
  if (!in_array((int)$r['s'], [200, 201], true)) {
    $err = is_array($r['j'] ?? null) ? (string)($r['j']['message'] ?? json_encode($r['j'], JSON_UNESCAPED_UNICODE)) : ('HTTP ' . $r['s']);
    eo_item_log("thumb fail id=$id " . mb_substr($err, 0, 200));
    return ['ok' => false, 'error' => mb_substr($err, 0, 200)];
  }
  eo_item_log("thumb ok id=$id eo=$sku url=" . $p['thumb']);
  return ['ok' => true, 'id' => $sku, 'rs' => $id, 'thumb' => $p['thumb']];
}

function eo_item_img_preview(PDO $db, int $id): string {
  $r = eo_item_image_row($db, $id);
  if ($r && trim((string)$r['path']) !== '') {
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$r['path']);
    if (is_file($abs)) return 'item_img.php?id=' . $id . '&v=' . filemtime($abs);
  }
  return '';
}
