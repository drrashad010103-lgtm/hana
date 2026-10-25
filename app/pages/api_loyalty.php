<?php
// pages/api_loyalty.php — واجهة نقاط الولاء
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/loyalty.php';
$db = db();
$act = $_GET['act'] ?? $_POST['act'] ?? '';

// جلب نقاط عميل
if ($act === 'get') {
  $cust = (int)($_GET['cust'] ?? 0);
  if ($cust <= 0) { echo json_encode(['ok' => true, 'points' => 0]); exit; }
  $st = $db->prepare("SELECT points, total_earned, total_redeemed FROM loyalty WHERE cust_id=?");
  $st->execute([$cust]);
  $r = $st->fetch();
  $points = $r ? (int)$r['points'] : 0;
  $earned = $r ? (int)$r['total_earned'] : 0;
  $redeemed = $r ? (int)$r['total_redeemed'] : 0;
  $cash = round($points * (float)(cfg_get('loy_cash_rate', '0.05') ?: '0.05'), 2);
  echo json_encode(['ok' => true, 'points' => $points, 'earned' => $earned, 'redeemed' => $redeemed, 'cash' => $cash], JSON_UNESCAPED_UNICODE);
  exit;
}

// إضافة نقاط (earn) أو خصم (redeem) — يُستدعى من api_sale_save
if ($act === 'add_earn') {
  $cust = (int)($_POST['cust'] ?? 0);
  $pts = (int)($_POST['points'] ?? 0);
  $inv = (int)($_POST['inv'] ?? 0);
  if ($cust <= 0 || $pts <= 0) { echo json_encode(['ok' => false]); exit; }
  loyalty_earn($cust, $pts, $inv, 'شراء');
  echo json_encode(['ok' => true]);
  exit;
}
if ($act === 'add_redeem') {
  $cust = (int)($_POST['cust'] ?? 0);
  $pts = (int)($_POST['points'] ?? 0);
  $inv = (int)($_POST['inv'] ?? 0);
  if ($cust <= 0 || $pts <= 0) { echo json_encode(['ok' => false]); exit; }
  if (!loyalty_redeem($cust, $pts, $inv, 'استبدال')) {
    echo json_encode(['ok' => false, 'error' => 'نقاط غير كافية']);
    exit;
  }
  echo json_encode(['ok' => true]);
  exit;
}

echo json_encode(['ok' => false, 'error' => 'unknown']);
