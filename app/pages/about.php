<?php
// pages/about.php — عن النسخة الحرة
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/auth.php';
if (($_GET['exit'] ?? '') === '1') {
  auth_logout();
  header('Location: login.php');
  exit;
}
$db = db();
$inv = $db->query("SELECT COUNT(*) n FROM inv")->fetch()['n'];
$it = $db->query("SELECT COUNT(*) n FROM currencies WHERE IS_Deleted=0")->fetch()['n'];
$sales = $db->query("SELECT ROUND(SUM(IF(proc_type=1,tot_net,0))-SUM(IF(proc_type=2,tot_net,0)),2) s FROM inv WHERE IS_Deleted=0 AND IS_Buy=0 AND proc_type IN (1,2)")->fetch()['s'];
layout_open('عن البرنامج', '');
echo '<div class="card" style="text-align:center"><div style="font-size:50px">🌿</div><h2>' . e(cfg_shop_name()) . '</h2>'
  . '<div>نسخة مستقلة تعمل على بيانات المحل — فرع 99 (لا تمس البرنامج الأصلي)</div>'
  . '<div class="row" style="justify-content:center;margin-top:10px"><div>الفواتير: <b>' . number_format($inv) . '</b></div><div>الأصناف: <b>' . number_format($it) . '</b></div><div>صافي المبيعات: <b>' . number_format($sales) . '</b></div></div>'
  . '<div class="note">PHP + MariaDB — صُنعت بحب في المحل ❤️</div></div>';
layout_close();
