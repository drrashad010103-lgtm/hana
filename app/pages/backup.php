<?php
// pages/backup.php + close_year.php — نسخة احتياطية + إغلاق سنة (rs_own فقط)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$which = basename($_SERVER['SCRIPT_NAME'], '.php');
if ($which === 'backup') {
  layout_open('نسخة احتياطية وسجلات', '');
  $bkFiles = glob('D:/RS_Own/backups/rs_own_*.sql') ?: [];
  $lastBk = $bkFiles ? max(array_map('filemtime', $bkFiles)) : 0;
  $stale = ($lastBk === 0) || ((time() - $lastBk) > 36 * 3600);
  $n = $db->query("SELECT COUNT(*) n FROM inv")->fetch()['n'];
  $log = is_file('D:/RS_Own/backup.log') ? array_slice(file('D:/RS_Own/backup.log'), -8) : [];
  $failFlag = is_file('D:/RS_Own/backup.failed') ? trim(file_get_contents('D:/RS_Own/backup.failed')) : '';
  if ($failFlag !== '') {
    echo '<div style="background:#fee2e2;border:1px solid #dc2626;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px;font-weight:700">آخر محاولة نسخ فاشلة: ' . e($failFlag) . '</div>';
  }
  if ($stale) {
    $msg = '⚠️ لا توجد نسخة احتياطية لـ rs_own خلال 36 ساعة'
         . ($lastBk ? ' (آخر نسخة: ' . date('Y-m-d H:i', $lastBk) . ')' : '')
         . ' — تحقق من المهمة RS_Own_NightlyBackup (جدول المهام).';
    echo '<div style="background:#fee2e2;border:1px solid #dc2626;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px;font-weight:700">' . e($msg) . '</div>';
  } else {
    $lastFile = array_reduce($bkFiles, function ($c, $f) { return (filemtime($f) > filemtime($c)) ? $f : $c; }, $bkFiles[0]);
    echo '<div style="background:#dcfce7;border:1px solid #16a34a;color:#166534;padding:8px;border-radius:8px;margin-bottom:10px">✅ آخر نسخة احتياطية: ' . date('Y-m-d H:i', $lastBk) . ' — ' . e(basename($lastFile)) . '</div>';
  }
  echo '<div class="card"><h3>نسخة احتياطية مجدولة (rs_own فقط)</h3>'
    . '<div>عدد السجلات في inv: <b>' . number_format($n) . '</b></div>'
    . '<div class="note">نسخة ليلية تلقائية (RS_Own_NightlyBackup، 01:30) تُحفظ في D:/RS_Own/backups (14 نسخة). نسخة يدوية: snapshot.php → dump. فشل النسخ يُسجَّل في D:/RS_Own/backup.log ويُنشئ D:/RS_Own/backup.failed + banner here.</div>'
    . '<div class="note">آخر 8 أسطر من backup.log:</div><pre style="background:#f8fafc;padding:8px;direction:ltr;text-align:left;font-size:12px">' . e(implode('', $log)) . '</pre></div>';
} else {
  layout_open('إغلاق سنة مالية', '');
  $y = yr($_GET['y'] ?? '', date('Y'));
  $x = $db->query("SELECT SUM(IF(IS_Buy=0 AND proc_type=1,tot_net,0))-SUM(IF(IS_Buy=0 AND proc_type=2,tot_net,0)) s, SUM(IF(IS_Buy=1 AND proc_type=1,tot_net,0)) b, COUNT(*) n FROM inv WHERE IS_Deleted=0 AND YEAR(date)='$y'")->fetch();
  echo '<div class="card"><h3>إغلاق سنة ' . e($y) . '</h3>'
    . '<form class="row"><div><label>السنة</label><br><input name="y" value="' . e($y) . '" style="width:90px"></div><div><br><button>عرض</button></div></form>'
    . '<div>المبيعات: <b>' . number_format($x['s']) . '</b> — المشتريات: <b>' . number_format($x['b']) . '</b> — السجلات: <b>' . number_format($x['n']) . '</b></div>'
    . '<div class="note">النسخة الاحتياطية (يدوي/مجدول) تُحفظ في D:/RS_Own/backups (خارج جذر الويب). أغلق السنة بعد التأكد من النسخة.</div></div>';
}
layout_close();
