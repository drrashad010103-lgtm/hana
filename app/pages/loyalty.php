<?php
// pages/loyalty.php — لوحة نقاط الولاء
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/loyalty.php';
$db = db();
$loyRate = (float)(cfg_get('loy_cash_rate', '0.05') ?: '0.05');
$msg = '';
// تعديل نقاط يدوي
if (($_POST['adj'] ?? '') !== '') {
  $cust = (int)($_POST['cust'] ?? 0);
  $pts = (int)($_POST['pts'] ?? 0);
  $note = mb_substr(trim($_POST['note'] ?? ''), 0, 128);
  if ($cust > 0 && $pts !== 0) {
    loyalty_adjust($cust, $pts, $note ?: 'تعديل يدوي');
    $msg = '✅ اتعديل';
  }
}
// فلتر
$q = trim($_GET['q'] ?? '');
$rows = [];
if ($q !== '' || isset($_GET['all'])) {
  if ($q !== '') {
    $like = '%' . str_replace(['%', '_'], '', mb_substr($q, 0, 64)) . '%';
    $st = $db->prepare("SELECT l.*, c.name FROM loyalty l JOIN customers c ON c.id=l.cust_id WHERE c.name LIKE ? OR CAST(l.cust_id AS CHAR)=? ORDER BY l.points DESC LIMIT 100");
    $st->execute([$like, $q]); $rows = $st->fetchAll();
  } else {
    $rows = $db->query("SELECT l.*, c.name FROM loyalty l JOIN customers c ON c.id=l.cust_id ORDER BY l.points DESC LIMIT 200")->fetchAll();
  }
}
$stat = $db->query("SELECT COUNT(*) n, IFNULL(SUM(points),0) tp, IFNULL(SUM(total_earned),0) te, IFNULL(SUM(total_redeemed),0) tr FROM loyalty")->fetch();
// آخر الحركات
$logs = $db->query("SELECT lg.*, c.name FROM loyalty_log lg JOIN customers c ON c.id=lg.cust_id ORDER BY lg.id DESC LIMIT 30")->fetchAll();
layout_open('⭐ نقاطي', '');
echo '<div class="cards" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">'
  . '<div class="card">👥 عملاء لديهم نقاط: <b>' . number_format($stat['n']) . '</b></div>'
  . '<div class="card">⭐ إجمالي النقاط: <b>' . number_format($stat['tp']) . '</b> = <b>' . number_format($stat['tp'] * $loyRate, 2) . '</b> ج</div>'
  . '<div class="card">📥 مكتسبة: <b>' . number_format($stat['te']) . '</b> | 📤 مستبدلة: <b>' . number_format($stat['tr']) . '</b></div></div>';
// تعديل يدوي
echo '<div class="card"><h3>✏️ تعديل نقاط عميل</h3><form method="post" class="row">'
  . '<div><label>كود العميل</label><br><input name="cust" type="number" style="width:100px"></div>'
  . '<div><label>نقاط (+/-)</label><br><input name="pts" type="number" style="width:100px"></div>'
  . '<div><label>سبب</label><br><input name="note" style="width:200px"></div>'
  . '<div><br><button name="adj" value="1">💾 تطبيق</button> <span>' . $msg . '</span></div></form></div>';
echo '<div class="card"><h3>🔍 بحث</h3><form class="row" method="get"><div><input name="q" value="' . e($q) . '" placeholder="اسم أو كود عميل" style="width:220px"></div><div><button>عرض</button> <a href="?all=1"><button type="button">الكل</button></a></div></form>';
if ($rows) {
  echo '<table><tr><th>كود</th><th>العميل</th><th>النقاط</th><th>قيمتها (ج)</th><th>مكتسبة</th><th>مستبدلة</th><th>آخر تحديث</th></tr>';
  foreach ($rows as $r) echo '<tr><td>' . $r['cust_id'] . '</td><td>' . e($r['name']) . '</td><td><b>' . number_format($r['points']) . '</b></td><td>' . number_format($r['points'] * $loyRate, 2) . '</td><td>' . number_format($r['total_earned']) . '</td><td>' . number_format($r['total_redeemed']) . '</td><td>' . e($r['updated_at'] ?? '') . '</td></tr>';
  echo '</table>';
}
echo '<div class="note">النظام: كل جنيه شراء = <b>' . ((float)(cfg_get('loy_earn', '1') ?: '1')) . '</b> نقطة | كل نقطة = <b>' . $loyRate . '</b> جنيه خصم</div></div>';
echo '<div class="card"><h3>📜 آخر 30 حركة</h3><table><tr><th>العميل</th><th>النقاط</th><th>النوع</th><th>الفاتورة</th><th>ملاحظة</th><th>التاريخ</th></tr>';
foreach ($logs as $g) {
  $t = ['earn' => '📥 كسب', 'redeem' => '📤 استبدال', 'adjust' => '✏️ تعديل'][$g['type']] ?? $g['type'];
  echo '<tr><td>' . e($g['name']) . '</td><td>' . ($g['points'] > 0 ? '+' : '') . $g['points'] . '</td><td>' . $t . '</td><td>' . ($g['inv_id'] ?? '—') . '</td><td>' . e($g['note'] ?? '') . '</td><td>' . e($g['created_at']) . '</td></tr>';
}
echo '</table></div>';
layout_close();
