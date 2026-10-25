<?php
// pages/snapshot.php — النسخ الاحتياطي واستعادة النسخ وأرشفة سنة مالية
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/archive_year.php';
$db = db();
$op = $_POST['op'] ?? $_GET['op'] ?? '';
$BK_DIR = dirname(__DIR__, 2) . '/backups'; // بره مجلد الويب — منعاً لتنزيل النسخة بدون دخول
$BK_BIN = 'C:\\Program Files\\MariaDB 13.0\\bin\\mysqldump.exe';
if ($op === 'dump' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  // التوليد على الديسك بـ mysqldump (سريع ونظيف) — بدل البث عبر HTTP
  // اللي كان بياخد 120 ثانية وبيموت بـ max_execution_time ويسيب Fatal جوه ملف الـ.sql
  if (!is_dir($BK_DIR)) @mkdir($BK_DIR, 0775, true);
  $file = 'rs_own_' . date('Ymd_His') . '.sql';
  $full = $BK_DIR . DIRECTORY_SEPARATOR . $file;
  set_time_limit(300);
  $o = []; $rc = 0;
  $errf = $full . '.err';
  if (is_file($BK_BIN)) {
    // stdout → ملف النسخة، stderr → ملف منفصل — حتى لا تتلوث الـ.sql برسائل تحذير
    exec('"' . $BK_BIN . '" -u root --skip-lock-tables rs_own > "' . $full . '" 2> "' . $errf . '"', $o, $rc);
  } else { $rc = 127; $o[] = 'mysqldump not found: ' . $BK_BIN; }
  clearstatcache(true, $full);
  $size = is_file($full) ? (int)filesize($full) : 0;
  $ok = $rc === 0 && $size > 1024;
  if (!$ok && is_file($full)) @unlink($full);
  $errMsg = is_file($errf) ? trim((string)@file_get_contents($errf)) : '';
  if ($errMsg === '' && is_file($errf)) @unlink($errf); // بلا تحذيرات — ننظّف الملف الفارغ
  if (!$ok && $errMsg === '' && $o) $errMsg = implode(' | ', array_slice($o, 0, 3));
  header('Location: snapshot.php?dumpok=' . ($ok ? 1 : 0) . '&f=' . rawurlencode($file) . '&s=' . $size . '&e=' . rawurlencode(mb_substr($errMsg, 0, 300)));
  exit;
}
if ($op === 'dl') {
  $f = (string)($_GET['f'] ?? '');
  if (!preg_match('/^rs_own_\d{8}_\d{6}\.sql$/', $f)) { http_response_code(400); exit('اسم ملف غير صالح'); }
  $full = $BK_DIR . DIRECTORY_SEPARATOR . $f;
  if (!is_file($full)) { http_response_code(404); exit('الملف غير موجود'); }
  while (ob_get_level()) ob_end_flush(); // نخرج من buffer الجلسة عشان التنزيل يتدفق مباشرة
  header('Content-Type: application/octet-stream');
  header('Content-Length: ' . filesize($full));
  header('Content-Disposition: attachment; filename="' . $f . '"');
  readfile($full);
  exit;
}
if ($op === 'close' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $yearInput = (string)($_POST['y'] ?? '');
  if (!preg_match('/^\d{4}$/', $yearInput)) {
    http_response_code(400);
    exit('سنة الأرشفة غير صالحة.');
  }
  try {
    $result = archive_year($db, (int)$yearInput);
  } catch (Throwable $e) {
    error_log('snapshot archive failed: ' . $e->getMessage());
    http_response_code(500);
    exit('تعذرت أرشفة السنة. راجع سجل أخطاء الخادم.');
  }
  echo 'تم تحديث أرشيف سنة ' . e($yearInput) . ' في قاعدة ' . e($result['database'])
    . ' — فواتير: ' . $result['invoices']
    . '، بنود: ' . $result['invoice_lines']
    . '، قيود: ' . $result['restrictions']
    . '، بنود القيود: ' . $result['restriction_lines']
    . '، أصناف مرجعية: ' . $result['currencies']
    . '. بيانات قاعدة التشغيل لم تُحذف.';
  exit;
}
$backs = [];
foreach ((glob($BK_DIR . DIRECTORY_SEPARATOR . 'rs_own_*.sql') ?: []) as $b) {
  $backs[] = ['f' => basename($b), 's' => (int)filesize($b), 't' => date('Y-m-d H:i:s', (int)filemtime($b))];
}
usort($backs, fn($a, $b) => strcmp($b['f'], $a['f']));
$banner = '';
if (($_GET['dumpok'] ?? '') === '1') {
  $banner = '<div class="note" style="background:#dcfce7;border:1px solid #16a34a;padding:10px;border-radius:8px">✅ تم حفظ النسخة: <b>' . e($_GET['f'] ?? '') . '</b> (' . number_format(((int)($_GET['s'] ?? 0)) / 1048576, 1) . ' ميجا) — <a href="?op=dl&f=' . e(rawurlencode((string)($_GET['f'] ?? ''))) . '"><button style="background:#16a34a">⬇ تنزيل الملف</button></a></div>';
} elseif (($_GET['dumpok'] ?? '') === '0') {
  $banner = '<div class="note" style="background:#fee2e2;border:1px solid #dc2626;padding:10px;border-radius:8px">❌ فشل النسخ: ' . e($_GET['e'] ?? '') . '</div>';
}
require_once __DIR__ . '/../lib/layout.php';
layout_open('النسخ الاحتياطي', '');
echo $banner;
echo '<div class="card"><div class="row"><div><form method="post"><input type="hidden" name="op" value="dump"><button style="background:#16a34a;font-size:18px;padding:14px 30px">💾 حفظ نسخة جديدة</button></form><div class="note">بيتحفظ على السيرفر في مجلد backups/ (ممكن ياخد دقيقة) وبعدها تنزّلها.</div></div>'
  . '<div><a href="restore.php"><button style="background:#2563eb;font-size:18px;padding:14px 30px">📥 استرجاع نسخة</button></a></div></div>';
if ($backs) {
  echo '<table class="grid" style="width:100%;margin-top:10px"><tr><th>الملف</th><th>الميجا</th><th>التاريخ</th><th></th></tr>';
  foreach ($backs as $b) {
    echo '<tr><td>' . e($b['f']) . '</td><td>' . number_format($b['s'] / 1048576, 1) . '</td><td>' . $b['t'] . '</td>'
      . '<td><a href="?op=dl&f=' . e(rawurlencode($b['f'])) . '">⬇ تنزيل</a></td></tr>';
  }
  echo '</table>';
} else echo '<div class="note">مافيش نسخ لسه — اضغط «حفظ نسخة جديدة».</div>';
echo '</div>';
echo '<div class="card"><h3>أرشفة السنة المالية</h3><form class="row" method="post" onsubmit="return confirm(\'نسخ سجلات السنة المختارة إلى قاعدة أرشيف مع إبقاء بيانات التشغيل؟\')"><input type="hidden" name="op" value="close"><div><label>السنة المراد أرشفتها</label><br><input name="y" type="number" min="1000" max="9998" value="' . date('Y') . '" style="width:100px" required></div><div><br><button type="submit">أرشفة نسخة السنة</button></div></form><div class="note">تُستبدل محتويات أرشيف السنة نفسها عند إعادة الأرشفة؛ قاعدة التشغيل لا تتغير.</div></div>';
layout_close();
