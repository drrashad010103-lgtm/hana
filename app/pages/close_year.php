<?php
// pages/backup.php + close_year.php — نسخة احتياطية وإقفال السنة (نسخة حرة)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$which = basename($_SERVER['SCRIPT_NAME'], '.php');
if ($which === 'backup') {
  layout_open('نسخة احتياطية', '');
  $log = is_file('D:/RS_Own/backup.log') ? array_slice(file('D:/RS_Own/backup.log'), -8) : [];
  $n = $db->query("SELECT COUNT(*) n FROM inv")->fetch()['n'];
  echo '<div class="card"><h3>💾 النسخة الاحتياطية</h3>'
    . '<div>الفواتير في النسخة: <b>' . number_format($n) . '</b></div>'
    . '<div class="note">التحديث الأسبوعي تلقائي من جهاز المحل (backup_now.bat) + زر الموبايل (طلب نسخة احتياطية).</div>'
    . '<div class="note">آخر سطور السجل:</div><pre style="background:#f8fafc;padding:8px;direction:ltr;text-align:left;font-size:12px">' . e(implode('', $log)) . '</pre></div>';
} else {
  layout_open('إقفال السنة المالية', '');
  $y = yr($_GET['y'] ?? '', date('Y'));
  $x = $db->query("SELECT SUM(IF(IS_Buy=0 AND proc_type=1,tot_net,0))-SUM(IF(IS_Buy=0 AND proc_type=2,tot_net,0)) s, SUM(IF(IS_Buy=1 AND proc_type=1,tot_net,0)) b, COUNT(*) n FROM inv WHERE IS_Deleted=0 AND YEAR(date)='$y'")->fetch();
  echo '<div class="card"><h3>📅 ملخص سنة ' . e($y) . '</h3>'
    . '<form class="row"><div><label>السنة</label><br><input name="y" value="' . e($y) . '" style="width:90px"></div><div><br><button>عرض</button></div></form>'
    . '<div>المبيعات: <b>' . number_format($x['s']) . '</b> — المشتريات: <b>' . number_format($x['b']) . '</b> — الفواتير: <b>' . number_format($x['n']) . '</b></div>'
    . '<div class="note">الإقفال الفعلي (ترحيل الأرصدة الافتتاحية) يتم يدوياً بعد مراجعة المحاسب — هذه الشاشة للعرض فقط.</div></div>';
}
layout_close();
