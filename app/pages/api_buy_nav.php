<?php
// pages/api_buy_nav.php — تنقل بين فواتير النسخة (فرع 99 مشتريات)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
$db = db();
$dir = $_GET['dir'] ?? 'last';
$cur = (int)($_GET['cur'] ?? 0);
$pt = ((int)($_GET['pt'] ?? 1)) === 2 ? 2 : 1;
$where = "branch=99 AND IS_Buy=1 AND proc_type=$pt AND IS_Deleted=0";
if ($dir === 'first') $r = $db->query("SELECT id FROM inv WHERE $where ORDER BY id ASC LIMIT 1")->fetch();
elseif ($dir === 'last') $r = $db->query("SELECT id FROM inv WHERE $where ORDER BY id DESC LIMIT 1")->fetch();
elseif ($dir === 'prev') { $st = $db->prepare("SELECT id FROM inv WHERE $where AND id<? ORDER BY id DESC LIMIT 1"); $st->execute([$cur]); $r = $st->fetch(); }
else { $st = $db->prepare("SELECT id FROM inv WHERE $where AND id>? ORDER BY id ASC LIMIT 1"); $st->execute([$cur]); $r = $st->fetch(); }
echo json_encode($r ? ['ok' => true, 'id' => (int)$r['id']] : ['ok' => false], JSON_UNESCAPED_UNICODE);
