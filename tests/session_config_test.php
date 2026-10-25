<?php
require __DIR__ . '/../app/lib/session.php';

$_SERVER['HTTPS'] = 'off';
if (app_session_cookie_secure()) {
  fwrite(STDERR, "FAIL: HTTP session cookie incorrectly marked Secure\n");
  exit(1);
}

$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'localhost';
session_id(bin2hex(random_bytes(8)));
app_session_start();
$params = session_get_cookie_params();
$passed = $params['secure'] === true
  && $params['httponly'] === true
  && $params['samesite'] === 'Lax';
if (session_status() === PHP_SESSION_ACTIVE) session_destroy();

if (!$passed) {
  fwrite(STDERR, "FAIL: HTTPS session cookie settings\n");
  exit(1);
}
echo "PASS: HTTPS session cookie is Secure, HttpOnly, and SameSite=Lax\n";
