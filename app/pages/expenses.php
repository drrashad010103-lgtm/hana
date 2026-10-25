<?php
// pages/expenses.php — تعريف مصروفات (كود + بند)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$db->exec("CREATE TABLE IF NOT EXISTS rs_expenses (code INT PRIMARY KEY, name TEXT)");
$msg = '';
if (($_POST['add'] ?? '') !== '') {
  $nm = mb_substr(trim($_POST['nm'] ?? ''), 0, 128);
  if ($nm !== '') {
    $code = (int)($_POST['code'] ?? 0);
    if ($code <= 0) $code = (int)$db->query("SELECT IFNULL(MAX(code),20202000)+1 n FROM rs_expenses")->fetch()['n'];
    $db->prepare("INSERT INTO rs_expenses (code, name) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)")->execute([$code, $nm]);
    $msg = '✅ اتحفظ ' . $code;
  }
}
if (($_POST['del'] ?? '') !== '') {
  $db->prepare("DELETE FROM rs_expenses WHERE code=?")->execute([(int)$_POST['code']]);
  $msg = '🗑️ اتحذف';
}
$rows = $db->query("SELECT code, name FROM rs_expenses ORDER BY code")->fetchAll();
layout_open('تعريف مصروفات', '');
echo '<div class="card"><form method="post" class="row"><div><label>كود المصروف</label><br><input name="code" inputmode="numeric" style="width:120px" placeholder="تلقائي"></div><div><label>بند مصروف</label><br><input name="nm" style="width:260px"></div><div><br><button name="add" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></form></div>';
echo '<div class="card"><table><tr><th>كود المصروف</th><th>بند مصروف</th><th></th></tr>';
foreach ($rows as $r) echo '<tr><td>' . $r['code'] . '</td><td>' . e($r['name']) . '</td><td><form method="post" onsubmit="return confirm(\'حذف؟\')"><input type="hidden" name="code" value="' . $r['code'] . '"><button name="del" value="1">🗑️</button></form></td></tr>';
echo '</table></div>';
layout_close();
