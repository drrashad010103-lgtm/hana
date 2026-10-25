<?php
require_once __DIR__ . '/paths.php';
if (!function_exists('app_session_start')) {
  function app_session_cookie_secure(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
    if (stripos((string)($_SERVER['HTTP_CF_VISITOR'] ?? ''), 'https') !== false) return true;
    return strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))) === 'https';
  }

  function app_session_start(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    // جلسة طويلة (30 يوم) — عشان التطبيق العميل يشيل التثبيت والمدخلات، مرتان اليوم.
    @ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
    @ini_set('session.gc_probability', '1');
    @ini_set('session.gc_divisor', '100000');
    // جلسات خارج مجلد الموقع (مش متاحة كملفات ثابتة عبر الويب)
    $__sp = app_data_dir() . '/sessions';
    if (!is_dir($__sp)) @mkdir($__sp, 0777, true);
    if (is_dir($__sp) && is_writable($__sp)) session_save_path($__sp);
    session_set_cookie_params([
      'lifetime' => 60 * 60 * 24 * 30,
      'path' => '/',
      'secure' => app_session_cookie_secure(),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
    session_start();
  }
}
