<?php
// tests/origin_check_test.php — اختبارات api_origin_ok (فحص أصل api_* POST)
require __DIR__ . '/../app/lib/origin.php';

$fail = 0;
function t(array $server, bool $expect, string $label): void {
  global $fail;
  $got = api_origin_ok($server);
  if ($got === $expect) { echo "PASS: $label\n"; }
  else { echo "FAIL: $label (expected " . ($expect ? 'true' : 'false') . " got " . ($got ? 'true' : 'false') . ")\n"; $fail++; }
}

// مطابقة كاملة تعمل
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'http://127.0.0.1:8082'], true, 'same origin http with port');
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'http://127.0.0.1:8082/'], true, 'trailing slash ignored');
t(['HTTP_HOST' => '127.0.0.1', 'HTTP_ORIGIN' => 'http://127.0.0.1'], true, 'default port 80 omitted both sides');
t(['HTTP_HOST' => '127.0.0.1:8443', 'HTTPS' => 'on', 'HTTP_ORIGIN' => 'https://127.0.0.1:8443'], true, 'https with explicit port');
t(['HTTP_HOST' => '127.0.0.1', 'HTTPS' => 'on', 'HTTP_ORIGIN' => 'https://127.0.0.1'], true, 'https default port 443 omitted');
t(['HTTP_HOST' => 'shop.local:8082', 'HTTP_ORIGIN' => 'http://Shop.Local:8082'], true, 'host case-insensitive');

// رفض: بروتوكول/منفذ/مضيف مختلف
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'https://127.0.0.1:8082'], false, 'scheme mismatch https vs http');
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'http://127.0.0.1:8100'], false, 'port mismatch');
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'http://evil.example'], false, 'foreign host');
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'http://127.0.0.1.evil.example:8082'], false, 'suffix-spoof host');
t(['HTTP_HOST' => 'localhost:8099', 'HTTP_ORIGIN' => 'http://127.0.0.1:8099'], false, 'localhost vs 127.0.0.1 are different origins');

// رفض صريح لـOrigin: null
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'null'], false, 'literal null origin rejected');
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'null', 'HTTP_REFERER' => 'http://127.0.0.1:8082/x'], false, 'null origin not overridden by referer');

// Referer كبديل
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_REFERER' => 'http://127.0.0.1:8082/pages/x.php'], true, 'referer same origin');
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_REFERER' => 'https://evil.example/x'], false, 'foreign referer rejected');

// لا Origin ولا Referer — مسموح (curl/CLI/اختبارات آلية)
t(['HTTP_HOST' => '127.0.0.1:8082'], true, 'no origin no referer allowed');

// قيم فاسدة
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'not-a-url'], false, 'malformed origin rejected');
t(['HTTP_HOST' => '127.0.0.1:8082', 'HTTP_ORIGIN' => 'ftp://127.0.0.1:8082'], false, 'non-http scheme rejected');
t(['HTTP_HOST' => '', 'HTTP_ORIGIN' => 'http://127.0.0.1:8082'], false, 'empty host rejected');

if ($fail > 0) { echo "origin_check: $fail failures\n"; exit(1); }
echo "All origin check tests passed.\n";
exit(0);
