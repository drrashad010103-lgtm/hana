<?php
// pages/api_gard.php — واجهة جرد سكانر (حوض count_list — مطابق لجرد الداش بورد المهندس)
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/cfg.php';
auth_start();
$db = db();

// حوض جرد سكانر — يُنشأ تلقائياً أول مرة (بنفس بنية حوض المهندس)
$db->exec("CREATE TABLE IF NOT EXISTS count_list (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code INT NOT NULL,
  name TEXT NOT NULL,
  counted DOUBLE NOT NULL DEFAULT 0,
  balance DOUBLE NOT NULL DEFAULT 0,
  exp_date VARCHAR(12) NOT NULL DEFAULT '',
  note TEXT,
  username VARCHAR(64) NOT NULL DEFAULT '',
  posted TINYINT NOT NULL DEFAULT 0,
  created DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function out(array $a): void { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$u = auth_user() ?? '';
$act = $_GET['act'] ?? $_POST['act'] ?? '';
if ($u === '') out(['ok' => false, 'error' => 'سجل الدخول أولاً']);
if (in_array($act, ['add', 'del', 'export', 'clear'], true)) {
  csrf_require();
}

if ($act === 'names') {
  $q = mb_substr(trim($_GET['q'] ?? ''), 0, 64);
  if (mb_strlen($q) < 2) out(['rows' => []]);
  $st = $db->prepare("SELECT id code, symbol name, sale_price price FROM currencies WHERE symbol LIKE ? AND (IS_Deleted=0 OR IS_Deleted IS NULL) ORDER BY id LIMIT 20");
  $st->execute(['%' . str_replace('%', '', $q) . '%']);
  out(['rows' => $st->fetchAll()]);
}

if ($act === 'list') {
  $rows = $db->query("SELECT id, code, name, counted, balance, exp_date, note, username FROM count_list WHERE posted=0 ORDER BY id")->fetchAll();
  foreach ($rows as &$r) {
    $r['id'] = (int)$r['id']; $r['code'] = (int)$r['code'];
    $r['counted'] = (float)$r['counted']; $r['balance'] = (float)$r['balance'];
    $r['diff'] = round((float)$r['counted'] - (float)$r['balance'], 2);
  }
  out(['ok' => true, 'rows' => $rows]);
}

if ($act === 'add') {
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  $code = trim((string)($in['code'] ?? ''));
  $counted = (float)($in['counted'] ?? NAN);
  if ($code === '' || !is_numeric($counted) || $counted < 0) out(['ok' => false, 'error' => 'اكتب الكود والمعدود']);
  $expd = (string)($in['exp_date'] ?? '');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expd)) $expd = '';
  $note = mb_substr(trim((string)($in['note'] ?? '')), 0, 120);

  // البحث: بالكود → بالباركود → بأكواد الباركود المتعددة
  $st = $db->prepare("SELECT id, symbol name, purch_price, sale_price FROM currencies WHERE (id = ? OR barcode = ?) AND (IS_Deleted=0 OR IS_Deleted IS NULL) LIMIT 1");
  $st->execute([(int)$code, $code]);
  $c = $st->fetch();
  if (!$c) {
    $st2 = $db->prepare("SELECT c.id, c.symbol name, c.purch_price, c.sale_price FROM curr_barcodes b JOIN currencies c ON c.id=b.curr WHERE b.barcode = ? LIMIT 1");
    $st2->execute([$code]);
    $c = $st2->fetch();
  }
  if (!$c) out(['ok' => false, 'error' => 'الكود مش موجود: ' . $code]);
  $bal = 0.0;
  $st3 = $db->prepare("SELECT bal FROM item_bal WHERE id=?");
  $st3->execute([(int)$c['id']]);
  if ($r = $st3->fetch()) $bal = (float)$r['bal'];

  $db->prepare("INSERT INTO count_list (code, name, counted, balance, exp_date, note, username) VALUES (?,?,?,?,?,?,?)")
     ->execute([(int)$c['id'], (string)$c['name'], $counted, $bal, $expd, $note, $u]);
  out(['ok' => true, 'name' => $c['name'], 'code' => (int)$c['id'], 'counted' => $counted, 'balance' => $bal, 'diff' => round($counted - $bal, 2)]);
}

if ($act === 'del') {
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = (int)($in['id'] ?? 0);
  if ($id <= 0) out(['ok' => false, 'error' => 'id']);
  $db->prepare("DELETE FROM count_list WHERE id=? AND posted=0")->execute([$id]);
  out(['ok' => true]);
}

if ($act === 'export') {
  // تصدير الجرد المفتوح كورقة جرد GARD في البرنامج (بنفس آلية المهندس)
  $rows = $db->query("SELECT code, name, counted, balance, exp_date, note, username FROM count_list WHERE posted=0 ORDER BY id")->fetchAll();
  if (!$rows) out(['ok' => false, 'error' => 'لا بنود مفتوحة']);
  $meta = [];
  foreach ($db->query("SELECT id, symbol, purch_price, sale_price FROM currencies") as $c) {
    $meta[(int)$c['id']] = [$c['symbol'], (float)$c['purch_price'], (float)$c['sale_price']];
  }
  $bal = [];
  foreach ($db->query("SELECT id, bal FROM item_bal") as $b) $bal[(int)$b['id']] = (float)$b['bal'];
  $gid = seq_next_table('gard');
  $tq = $tp = $ts = 0;
  $db->beginTransaction();
  $db->prepare("INSERT INTO gard (id, date, user_id, emp_name, branch) VALUES (?,?,?,?,99)")
     ->execute([$gid, date('Y-m-d H:i:s'), 1, 'جرد سكانر']);
  $ins = $db->prepare("INSERT INTO gard_sub (GARD_id, code, item_name, purch, sale, quintity, tot_purch, tot_sale, sys_Qty, settlement) VALUES (?,?,?,?,?,?,?,?,?,0)");
  foreach ($rows as $r) {
    $m = $meta[(int)$r['code']] ?? ['', 0, 0];
    $sys = $bal[(int)$r['code']] ?? 0;   // رصيد لحظة الترحيل (أدق من قيمة قديمة)
    $qty = (int)round((float)$r['counted']);   // ورقة GARD في البرنامج بقيم صحيحة
    $ins->execute([$gid, $r['code'], $m[0] !== '' ? $m[0] : $r['name'], $m[1], $m[2], $qty, $qty * $m[1], $qty * $m[2], $sys]);
    $tq += $qty; $tp += $qty * $m[1]; $ts += $qty * $m[2];
  }
  $db->prepare("UPDATE gard SET tot_quntity=?, tot_items=?, tot_purch=?, tot_sale=? WHERE id=?")->execute([$tq, count($rows), $tp, $ts, $gid]);
  $db->prepare("UPDATE count_list SET posted=1 WHERE posted=0")->execute();
  $db->commit();
  out(['ok' => true, 'gid' => (int)$gid, 'count' => count($rows), 'qtq' => $tq, 'tp' => $tp, 'ts' => $ts, 'log' => "ورقة جرد $gid: " . count($rows) . ' بند']);
}

if ($act === 'clear') {
  $db->prepare("DELETE FROM count_list WHERE posted=0")->execute();
  out(['ok' => true]);
}

out(['ok' => false, 'error' => 'act غير معروف']);
