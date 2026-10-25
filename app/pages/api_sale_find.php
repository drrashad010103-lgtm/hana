<?php
// pages/api_sale_find.php + api_buy_find.php — بحث فواتير النسخة والمستوردة بفترة
require __DIR__ . '/../lib/db.php';
header('Content-Type: application/json; charset=utf-8');
$db = db();
$buy = basename($_SERVER['SCRIPT_NAME']) === 'api_buy_find.php' ? 1 : 0;
$pt = (int)($_GET['pt'] ?? 1);
$pt = $buy ? (in_array($pt, [1, 2, 3], true) ? $pt : 1) : ($pt === 2 ? 2 : 1);
$f = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-d');
$t = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
$st = $db->prepare("SELECT id, branch, DATE(date) d, tot_net net, paid, IS_Temp temp FROM inv WHERE branch IN (1,99) AND IS_Buy=$buy AND proc_type=$pt AND IS_Deleted=0 AND DATE(date)>='$f' AND DATE(date)<='$t' ORDER BY date DESC, id DESC LIMIT 200");
$st->execute();
echo json_encode(['ok' => true, 'rows' => array_map(fn($r) => ['id' => (int)$r['id'], 'branch' => (int)$r['branch'], 'd' => $r['d'], 'net' => (float)$r['net'], 'paid' => (float)$r['paid'], 'temp' => (int)$r['temp']], $st->fetchAll())], JSON_UNESCAPED_UNICODE);
