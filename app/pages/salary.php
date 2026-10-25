<?php
// pages/salary.php — صرف الرواتب (نسخة SalaryPay الأصلي: صافي + شهر + سنة + مسئول الصرف)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$db->exec("CREATE TABLE IF NOT EXISTS salarypay (id INT AUTO_INCREMENT PRIMARY KEY, emp INT NOT NULL, month INT NOT NULL, year INT NOT NULL, salary_net DOUBLE NOT NULL DEFAULT 0, date DATETIME NULL, emp_resp VARCHAR(64) NULL, UNIQUE KEY ux_emp_my (emp,month,year)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$msg = '';
$m = max(1, min(12, (int)($_GET['m'] ?? date('n'))));
$y = max(2000, min(2100, (int)($_GET['y'] ?? date('Y'))));
$me = $_SESSION['u'] ?? '';
if (($_POST['pay'] ?? '') !== '') {
  $eid = (int)$_POST['pay'];
  $e = $db->prepare("SELECT * FROM employees WHERE id=?");
  $e->execute([$eid]); $e = $e->fetch();
  if ($e) {
    $net = (float)($e['salary_basic'] ?? 0) + (float)($e['salary_add'] ?? 0) + (float)($e['salary_other'] ?? 0) + (float)($e['house'] ?? 0) + (float)($e['food'] ?? 0) + (float)($e['travel'] ?? 0) + (float)($e['medical'] ?? 0);
    try {
      $db->prepare("INSERT INTO salarypay (emp,month,year,salary_net,date,emp_resp) VALUES (?,?,?,?,NOW(),?)")->execute([$eid, $m, $y, $net, mb_substr($me, 0, 64)]);
      $msg = '✅ اتصرف ' . number_format($net) . ' لـ ' . $e['name'];
    } catch (Throwable $t) { $msg = '❌ مصروف قبل كده للشهر ده'; }
  }
}
if (($del = (int)($_POST['del'] ?? 0)) > 0) {
  $db->prepare("DELETE FROM salarypay WHERE id=?")->execute([$del]);
  $msg = '🗑️ اتمسح الصرف';
}
$emps = $db->query("SELECT id, name, IFNULL(salary_basic,0)+IFNULL(salary_add,0)+IFNULL(salary_other,0)+IFNULL(house,0)+IFNULL(food,0)+IFNULL(travel,0)+IFNULL(medical,0) net FROM employees WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY name")->fetchAll();
$paid = [];
foreach ($db->query("SELECT * FROM salarypay WHERE month=$m AND year=$y")->fetchAll() as $r) $paid[(int)$r['emp']] = $r;
$tot = array_sum(array_column($emps, 'net'));
$totP = 0; foreach ($emps as $e) if (isset($paid[(int)$e['id']])) $totP += (float)$paid[(int)$e['id']]['salary_net'];
layout_open('صرف الرواتب', '');
echo '<div class="card"><form class="row" method="get"><div><label>الشهر</label><br><input type="number" name="m" value="' . $m . '" min="1" max="12" style="width:70px"></div><div><label>السنة</label><br><input type="number" name="y" value="' . $y . '" style="width:90px"></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">طباعة</button></div><div><br><span>' . e($msg) . '</span></div></form>'
  . '<div class="note">إجمالي المرتبات: <b>' . number_format($tot) . '</b> — المصروف: <b>' . number_format($totP) . '</b> — الباقي: <b>' . number_format($tot - $totP) . '</b></div></div>';
echo '<div class="card"><table><tr><th>الموظف</th><th>الصافي المحسوب</th><th>الحالة</th><th>تاريخ الصرف</th><th>المسئول</th><th></th></tr>';
foreach ($emps as $e) {
  $id = (int)$e['id']; $p = $paid[$id] ?? null;
  echo '<tr><td><b>' . e($e['name']) . '</b></td><td>' . number_format($e['net']) . '</td>'
    . ($p ? '<td style="color:green">✅ مصروف</td><td>' . e($p['date'] ?? '') . '</td><td>' . e($p['emp_resp'] ?? '') . '</td><td><form method="post" style="margin:0" onsubmit="return confirm(\'مسح الصرف؟\')"><input type="hidden" name="del" value="' . (int)$p['id'] . '"><button type="submit" aria-label="مسح الصرف">🗑️</button></form></td>'
      : '<td style="color:red">لم يُصرف</td><td>—</td><td>—</td><td><form method="post" style="margin:0"><button name="pay" value="' . $id . '">💰 صرف</button></form></td>') . '</tr>';
}
echo '</table></div>';
layout_close();
