<?php
// lib/db.php — اتصال rs_own فقط (+ بوابة الدخول لكل صفحات الويب)
// ملاحظة ترتيب مهم: التعريفات أولاً ثم guard — لأن فرع الصلاحيات في guard يستدعي db()
// حماية redeclare: الدوال جوه شرط — لأن الدوال العليا تُسجَّل وقت الـ compile
// فيفشل الـ return وقت التشغيل، والصفحات بتستدعي require عادي (مش _once) بعد تحميل guard
if (!function_exists('db')) {
  function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $cfg = require __DIR__ . '/../config.php';
    $d = $cfg['db'];
    $pdo = new PDO("mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}", $d['user'], $d['pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
  }
  function fmt($n) { return number_format((float)$n, 0); }
  function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
  // تحقق من تاريخ YYYY-MM-DD — يمنع حقن SQL عبر from/to/y
  function dt(?string $v, string $fallback = ''): string {
    $v = (string)$v;
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && checkdate((int)substr($v,5,2), (int)substr($v,8,2), (int)substr($v,0,4)) ? $v : $fallback;
  }
  // تحقق من سنة YYYY
  function yr(?string $v, string $fallback = ''): string {
    $v = (string)$v;
    return preg_match('/^\d{4}$/', $v) ? $v : $fallback;
  }
}
require_once __DIR__ . '/seq.php';
if (php_sapi_name() !== 'cli') require_once __DIR__ . '/guard.php';
