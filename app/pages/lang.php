<?php
require_once __DIR__ . '/../lib/i18n.php';

$lang = strtolower(trim((string)($_GET['set'] ?? '')));
$back = trim((string)($_GET['back'] ?? 'main.php'));

if (!preg_match('~^[a-z0-9_./?=&%#-]+$~i', $back) || strpos($back, '//') !== false || strpos($back, '..') !== false || stripos($back, 'http:') === 0 || stripos($back, 'https:') === 0) {
  $back = 'main.php';
}
if ($lang === '' || !i18n_valid($lang)) {
  header('Location: ' . $back);
  exit;
}

i18n_save_user($lang);
if (!empty($_GET['global']) && ($_SESSION['u'] ?? '') === 'admin') {
  i18n_global_save($lang);
}
setcookie('rs_lang', $lang, [
  'expires' => time() + 60 * 60 * 24 * 365,
  'path' => '/',
  'samesite' => 'Lax',
]);

header('Location: ' . $back);
exit;
