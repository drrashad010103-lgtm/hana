<?php
// router.php — حماية إجبارية لسيرفر PHP المدمج: كل ملف PHP يمر على lib/guard.php قبل أن يُنفذ
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$root = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
$f = realpath($root . urldecode((string)$uri));
$safe = $f !== false && str_starts_with(str_replace('\\', '/', $f), $root);
if ($safe && is_file($f) && pathinfo($f, PATHINFO_EXTENSION) === 'php') {
  $GLOBALS['__GUARD_REAL_SCRIPT'] = basename($f);
  require_once $root . '/lib/guard.php';
}
return false;
