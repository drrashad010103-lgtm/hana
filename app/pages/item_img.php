<?php
// pages/item_img.php — معاينة صورة الصنف المحفوظة محلياً
require __DIR__ . '/../lib/db.php';
if (empty($_SESSION['u'])) { http_response_code(403); exit; }
$db = db();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit; }
try {
  $st = $db->prepare("SELECT path FROM eo_item_img WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $p = (string)$st->fetchColumn();
} catch (Throwable $t) { http_response_code(404); exit; }
if ($p === '') { http_response_code(404); exit; }
$base = realpath(dirname(__DIR__) . '/uploads/items');
$abs = realpath(dirname(__DIR__) . '/' . str_replace('\\', '/', $p));
if (!$base || !$abs || strpos($abs, $base) !== 0 || !is_file($abs)) { http_response_code(404); exit; }
$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
if (!isset($types[$ext])) { http_response_code(404); exit; }
header('Content-Type: ' . $types[$ext]);
header('Content-Length: ' . filesize($abs));
header('Cache-Control: private, max-age=86400');
readfile($abs);
