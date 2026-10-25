<?php
// lib/origin.php — فحص أصل طلبات api_* POST: مطابقة الأصل كاملة (scheme + host + port)
// دالة نقية تستقبل مصفوفة $_SERVER — للاختبار الآلي وللوضوح. لا تعتمد على سياق الجلسة.
function api_origin_ok(array $server): bool {
  $src = '';
  $o = $server['HTTP_ORIGIN'] ?? '';
  if (is_string($o) && trim($o) !== '') {
    if (trim($o) === 'null') return false; // Origin: null = سياق معزول (iframe مصنّع/file:) — مرفوض صراحةً
    $src = trim($o);
  } elseif (!empty($server['HTTP_REFERER'])) {
    $src = trim((string)$server['HTTP_REFERER']);
  } else {
    return true; // لا Origin ولا Referer — طلبات غير المتصفح (curl/CLI/اختبارات) مسموحة
  }
  $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = strtolower((string)($server['HTTP_HOST'] ?? ''));
  if ($host === '') return false;
  $canon = function (string $url): ?string {
    $p = parse_url($url);
    if ($p === false || empty($p['scheme']) || empty($p['host'])) return null;
    $sch = strtolower((string)$p['scheme']);
    if ($sch !== 'http' && $sch !== 'https') return null;
    $h = strtolower((string)$p['host']);
    $def = ($sch === 'https') ? 443 : 80;
    $port = isset($p['port']) ? (int)$p['port'] : $def;
    return $sch . '://' . $h . ($port === $def ? '' : ':' . $port);
  };
  $a = $canon($src);
  $b = $canon($scheme . '://' . $host);
  return $a !== null && $b !== null && $a === $b;
}
