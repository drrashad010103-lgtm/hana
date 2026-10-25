<?php
// lib/easyorders.php — ربط مخزون rs_own عبر بروكسي مشروع EasyOrder المحلي
const EO_PROXY = 'http://127.0.0.1:3456/api';

function eo_api(string $method, string $path, ?array $body = null, int $tries = 5): array {
  $url = EO_PROXY . $path;
  for ($n = 1; $n <= $tries; $n++) {
    $ch = curl_init($url);
    $opts = [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST  => $method,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT        => 60,
      CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/json'],
    ];
    curl_setopt_array($ch, $opts);
    if ($body !== null) {
      $json = json_encode($body);
      if ($json === false) {
        curl_close($ch);
        return ['s' => 0, 'j' => null, 'error' => 'تعذر تجهيز بيانات الطلب'];
      }
      curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = $resp === false ? curl_error($ch) : '';
    curl_close($ch);
    if ($code === 429 && $n < $tries) { sleep(2 * $n); continue; }
    $j = $resp === false ? null : json_decode($resp, true);
    return ['s' => $code, 'j' => $j, 'error' => $error];
  }
  return ['s' => 0, 'j' => null, 'error' => 'تجاوز عدد محاولات البروكسي المحلي'];
}

// يرجع ['items' => [...], 'error' => null|string] — error يُضبط عند فشل الشبكة/الخادم
// حتى لا تُعرض فجوة الصفر منتجات كنجاح زائف
function eo_fetch_all_products(int $maxPages = 30): array {
  $all = []; $page = 1;
  while ($page <= $maxPages) {
    $r = eo_api('GET', "/products?page=$page&limit=200");
    $s = (int)$r['s'];
    if ($s === 0 || $s >= 400 || !is_array($r['j'] ?? null)) {
      $detail = is_array($r['j'] ?? null) ? (string)($r['j']['error'] ?? $r['j']['message'] ?? '') : '';
      if ($s === 0) $detail = (string)($r['error'] ?? '');
      return ['items' => $all, 'error' => ($s === 0
        ? 'تعذر الاتصال بخدمة EasyOrder المحلية على المنفذ 3456' . ($detail !== '' ? ': ' . $detail : '')
        : 'خدمة EasyOrder رجعت HTTP ' . $s . ($detail !== '' ? ': ' . $detail : ''))];
    }
    $data = $r['j']['data'] ?? null;
    if (!is_array($data)) {
      return ['items' => $all, 'error' => 'استجابة غير متوقعة من EasyOrders'];
    }
    if (!$data) break;
    $all = array_merge($all, $data);
    if (count($data) < 200) break;
    $page++;
  }
  return ['items' => $all, 'error' => null];
}

function eo_safe_payload(array $p, array $task): array {
  $del = ['image','images','gallery','attachments','reviews','related_products','relatedProducts','cross_sell','upsell','seo','audit_logs','history'];
  foreach ($del as $k) unset($p[$k]);
  $catId = $p['category_id'] ?? $p['categoryId'] ?? ($p['category']['id'] ?? null)
    ?? ($p['categories'][0]['id'] ?? null);
  if ($catId) { $p['category_id'] = $catId; $p['categoryId'] = $catId; $p['category'] = ['id' => $catId]; }
  return array_merge($p, $task);
}

// قراءة أرصدة rs_own (MySQL) — عبر view جاهز v_stock
function eo_read_balances(): array {
  $db = db();
  $map = [];
  foreach ($db->query("SELECT id, bal, sale_price, purch_price, group_id FROM v_stock") as $r) {
    $map[(int)$r['id']] = [
      'bal'   => (float)$r['bal'],
      'sale'  => (float)($r['sale_price'] ?? 0),
      'cost'  => (float)($r['purch_price'] ?? 0),
      'group' => (int)($r['group_id'] ?? 0),
    ];
  }
  return $map;
}

// أصناف عليها حركة منذ تاريخ
function eo_moved_since(string $since): array {
  $db = db();
  $st = $db->prepare("SELECT DISTINCT s.currency_from FROM inv i JOIN inv_sub s ON i.proc_id=s.proc_id WHERE i.IS_Deleted=0 AND i.date>=? AND s.currency_from>0 ORDER BY s.currency_from");
  $st->execute([$since]);
  $ids = [];
  foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $v) $ids[] = (int)$v;
  return $ids;
}
