<?php
// pages/api_item.php — بحث أصناف (من rs_own)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
$q = mb_substr(trim($_GET['q'] ?? ''), 0, 64);
if (mb_strlen($q) < 1) { echo json_encode(['rows' => []]); exit; }
$db = db();
if (ctype_digit($q)) {
  $st = $db->prepare("SELECT id, symbol name, sale_price price FROM currencies WHERE (id = ? OR barcode = ?) AND IS_Deleted=0 LIMIT 10");
  $st->execute([(int)$q, $q]);
} else {
  $parts = array_values(array_filter(preg_split('/\s+/u', $q)));
  $w = []; $p = [];
  foreach ($parts as $x) { $w[] = 'symbol LIKE ?'; $p[] = "%$x%"; }
  $st = $db->prepare("SELECT id, symbol name, sale_price price FROM currencies WHERE " . implode(' AND ', $w) . " AND IS_Deleted=0 ORDER BY id LIMIT 20");
  $st->execute($p);
}
echo json_encode(['rows' => $st->fetchAll()], JSON_UNESCAPED_UNICODE);
