<?php
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/items.php';
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['u'])) { echo json_encode(['ok'=>false,'error'=>'لازم تسجل دخول أولاً']); exit; }
$db = db();
$act = $_GET['act'] ?? '';

if ($act === 'next') {
  echo json_encode(['ok'=>true,'next'=>item_next_code()], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($act === 'search') {
  $q = trim($_GET['q'] ?? '');
  if ($q === '') { echo json_encode(['ok'=>true,'rows'=>[]], JSON_UNESCAPED_UNICODE); exit; }
  $like = '%' . $q . '%';
  $st = $db->prepare("SELECT c.id, c.symbol, c.barcode, c.sale_price, c.purch_price, c.group_id, IFNULL(b.bal,0) bal, g.name gname
                      FROM currencies c
                      LEFT JOIN item_bal b ON b.id=c.id
                      LEFT JOIN `groups` g ON g.id=c.group_id
                      WHERE c.IS_Deleted=0 AND (c.symbol LIKE ? OR c.barcode = ? OR c.id = ? OR c.nameEN LIKE ?)
                      ORDER BY c.id DESC LIMIT 30");
  $st->execute([$like, $q, (int)$q, $like]);
  echo json_encode(['ok'=>true,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($act === 'get') {
  $id = (int)($_GET['id'] ?? 0);
  $st = $db->prepare("SELECT c.*, IFNULL(b.bal,0) bal FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE c.id=?");
  $st->execute([$id]);
  $it = $st->fetch(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>(bool)$it,'item'=>$it ?: null], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($act === 'del') {
  csrf_require();
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($in['id'] ?? 0);
  if (!cfg_is_admin()) { echo json_encode(['ok' => false, 'error' => 'حذف الصنف للمالك فقط'], JSON_UNESCAPED_UNICODE); exit; }
  if ($id <= 0) { echo json_encode(['ok' => false, 'error' => 'كود غير صالح'], JSON_UNESCAPED_UNICODE); exit; }
  $db->prepare("UPDATE currencies SET IS_Deleted=1 WHERE id=?")->execute([$id]);
  try { audit_log('del', 'currencies', $id, 'item delete'); } catch (Throwable $t) {}
  echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$editId = (int)($body['id'] ?? 0);
try {
  if ($editId > 0 && empty(cfg_user_flags((int)($_SESSION['uid'] ?? 0))['edit_price'])) {
    echo json_encode(['ok'=>false,'error'=>'لا صلاحية تعديل صنف/سعر — كلم الإدارة'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $out = item_save($body, $editId);
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $t) {
  echo json_encode(['ok'=>false,'error'=>$t->getMessage()], JSON_UNESCAPED_UNICODE);
}
