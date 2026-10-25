<?php
// pages/api_coupon.php — التحقق من كوبون خصم (كود + تواريخ + فعال)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
$code = mb_substr(trim($_GET['code'] ?? ''), 0, 64);
if ($code === '') { echo json_encode(['ok' => false]); exit; }
$st = db()->prepare("SELECT id, discount FROM coboun WHERE code_name=? AND (Is_deleted=0 OR Is_deleted IS NULL) AND (active=1) AND (date_start IS NULL OR date_start<=CURDATE()) AND (date_end IS NULL OR date_end>=CURDATE()) LIMIT 1");
$st->execute([$code]);
$r = $st->fetch();
if ($r) echo json_encode(['ok' => true, 'id' => (int)$r['id'], 'pct' => (float)$r['discount']], JSON_UNESCAPED_UNICODE);
else echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
