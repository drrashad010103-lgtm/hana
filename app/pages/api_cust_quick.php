<?php
// pages/api_cust_quick.php — عميل جديد سريع من الفاتورة (يتطلب بيع أو عملاء)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/customers.php';
$db = db();
$me = $_SESSION['u'] ?? '';
if ($me !== 'admin') {
  $uid = (int)($_SESSION['uid'] ?? 0);
  $allow = [];
  try {
    if ($uid > 0) {
      $st = $db->prepare("SELECT perm FROM user_perms WHERE user_id=? AND val=1");
      $st->execute([$uid]);
      $allow = array_column($st->fetchAll(), 'perm');
      $n = $db->prepare("SELECT COUNT(*) n FROM user_perms WHERE user_id=?");
      $n->execute([$uid]);
      if ((int)$n->fetch()['n'] === 0) $allow = [];
    } else $allow = [];
  } catch (Throwable $t) { $allow = []; }
  if (!in_array('m_sale', $allow, true) && !in_array('m_cust', $allow, true)) { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'noperm']); exit; }
}
$r = customer_save(['name' => $_POST['name'] ?? '', 'mobile' => $_POST['mobile'] ?? '']);
if (empty($r['ok'])) { echo json_encode(['ok' => false, 'error' => $r['error'] ?? 'الاسم مطلوب']); exit; }
echo json_encode(['ok' => true, 'id' => $r['id'], 'name' => mb_substr(trim($_POST['name'] ?? ''), 0, 250)], JSON_UNESCAPED_UNICODE);
