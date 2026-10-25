<?php
// tools/watchdog.php — فحص صحة المنظومة + تنبيه (تلجرام اختياري) عند تغيّر الحالة
// يعمل من Task Scheduler كل 10 دقائق. لا يطبع أسراراً ولا يعدّل أي بيانات.
date_default_timezone_set('Africa/Cairo');
$ROOT = 'D:/RS_Own';
$STATE = $ROOT . '/status';
$LOG = $ROOT . '/logs/watchdog.log';
if (!is_dir($STATE)) @mkdir($STATE, 0775, true);
if (!is_dir($ROOT . '/logs')) @mkdir($ROOT . '/logs', 0775, true);

$lock = @fopen($ROOT . '/status/watchdog.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) exit(0);

function port_up(int $p): bool {
  $c = @fsockopen('127.0.0.1', $p, $e, $s, 2);
  if ($c) { fclose($c); return true; }
  return false;
}
function tail_lines(string $f, int $n = 200): array {
  if (!is_file($f)) return [];
  $sz = filesize($f);
  $fh = @fopen($f, 'rb');
  if (!$fh) return [];
  $read = min($sz, 65536);
  fseek($fh, -$read, SEEK_END);
  $txt = fread($fh, $read);
  fclose($fh);
  return array_slice(preg_split('/\r?\n/', (string)$txt), -$n);
}
function stamp_of(string $line): int {
  if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $m)) {
    $t = strtotime($m[1]);
    return $t ?: 0;
  }
  if (preg_match('~\[[^\]]*?(\d{2}/\d{2}/\d{4} \d{2}:\d{2}:\d{2})~', $line, $m)) {
    $d = DateTime::createFromFormat('m/d/Y H:i:s', $m[1]);
    return $d ? $d->getTimestamp() : 0;
  }
  return 0;
}

$checks = [];
$add = function (string $name, string $level, string $msg) use (&$checks) {
  $checks[$name] = ['level' => $level, 'msg' => $msg];
};

// 1) المنافذ
foreach ([3306 => 'MariaDB', 8082 => 'Nginx', 3456 => 'EO proxy', 8081 => 'rs_online'] as $p => $label) {
  $add('port_' . $p, port_up($p) ? 'ok' : 'bad', $label . ' :' . $p . (port_up($p) ? ' شغال' : ' واقف'));
}
$wk = 0;
for ($p = 8083; $p <= 8086; $p++) if (port_up($p)) $wk++;
$add('workers', $wk >= 2 ? 'ok' : ($wk === 1 ? 'warn' : 'bad'), "PHP workers: $wk/4");

// 2) آخر مزامنة ناجحة
$syncT = 0;
foreach (tail_lines($ROOT . '/tools/sync.log', 400) as $ln) {
  if (strpos($ln, 'item_bal refreshed') !== false) $syncT = max($syncT, stamp_of($ln));
}
$syncAge = $syncT ? (time() - $syncT) : PHP_INT_MAX;
$add('sync', $syncAge < 26 * 3600 ? 'ok' : ($syncAge < 50 * 3600 ? 'warn' : 'bad'),
  'آخر مزامنة: ' . ($syncT ? date('Y-m-d H:i', $syncT) . ' (من ' . round($syncAge / 3600, 1) . ' ساعة)' : 'غير معروفة'));

// 3) النسخ الاحتياطي
$latestBk = null;
foreach (glob($ROOT . '/backups/rs_own_*.sql') ?: [] as $f) {
  if ($latestBk === null || filemtime($f) > filemtime($latestBk)) $latestBk = $f;
}
$bkFail = is_file($ROOT . '/backup.failed');
$bkAge = $latestBk ? (time() - filemtime($latestBk)) : PHP_INT_MAX;
$bkLevel = $bkFail ? 'bad' : ($bkAge < 36 * 3600 ? 'ok' : ($bkAge < 72 * 3600 ? 'warn' : 'bad'));
$add('backup', $bkLevel, $bkFail ? 'فشل باك أب: ' . trim((string)@file_get_contents($ROOT . '/backup.failed'))
  : ('آخر باك أب: ' . ($latestBk ? basename($latestBk) . ' (' . round($bkAge / 3600, 1) . ' ساعة)' : 'لا يوجد')));

// 3b) اختبار الاسترجاع الأسبوعي
$verifyFail = is_file($ROOT . '/backup.verify.failed');
$verifyOkT = 0;
foreach (tail_lines($ROOT . '/backup.log', 400) as $ln) {
  if (strpos($ln, 'restore-test-OK') !== false) $verifyOkT = max($verifyOkT, stamp_of($ln));
}
$verifyAge = $verifyOkT ? (time() - $verifyOkT) : PHP_INT_MAX;
$add('restore_test', $verifyFail ? 'bad' : ($verifyAge < 15 * 86400 ? 'ok' : 'warn'),
  $verifyFail ? 'اختبار استرجاع فشل: ' . trim((string)@file_get_contents($ROOT . '/backup.verify.failed'))
  : ('آخر استرجاع ناجح: ' . ($verifyOkT ? date('Y-m-d H:i', $verifyOkT) : 'لا يوجد')));

// 4) الديسك
$free = @disk_free_space('D:');
$total = @disk_total_space('D:');
$pct = ($free && $total) ? ($free / $total * 100) : 100;
$add('disk', $pct < 5 ? 'bad' : ($pct < 10 ? 'warn' : 'ok'),
  'مساحة D: ' . round($pct, 1) . '% (' . round($free / 1073741824, 1) . 'GB فاضي)');

// 5) مطابقة الأرصدة مع برنامج المهندس (verify_rs.php)
$vj = @json_decode((string)@file_get_contents($ROOT . '/status/verify.json'), true);
$vAge = is_array($vj) && !empty($vj['ts']) ? (time() - (strtotime((string)$vj['ts']) ?: 0)) : PHP_INT_MAX;
$vOk = is_array($vj) ? !empty($vj['ok']) : false;
$add('verify_rs', $vOk && $vAge < 26 * 3600 ? 'ok' : ($vAge < 50 * 3600 ? 'warn' : 'bad'),
  'مطابقة المهندس: ' . ($vj ? (($vOk ? 'مطابق' : 'فيه فروق') . ' — ' . date('Y-m-d H:i', strtotime((string)$vj['ts']))) : 'لم يتم الفحص'));

// 5b) دفع البيانات لموقع إنفينيتي (push_infinity.php)
$pf = $ROOT . '/tools/push_infinity.log';
$pAge = is_file($pf) ? (time() - (int)filemtime($pf)) : PHP_INT_MAX;
$pLast = '';
foreach (tail_lines($pf, 40) as $ln) if (strpos($ln, 'done fail=') !== false) { $pLast = $ln; }
$pFail = (bool)preg_match('/done fail=[1-9]/', $pLast);
$add('push_infinity', $pFail ? 'bad' : ($pAge < 26 * 3600 ? 'ok' : ($pAge < 50 * 3600 ? 'warn' : 'bad')),
  'دفع إنفينيتي: ' . ($pLast !== '' ? substr($pLast, 0, 40) : 'لم يتم بعد'));

// 6) أخطاء حديثة في لوج المزامنة (بعد آخر done)
$lines = tail_lines($ROOT . '/tools/sync.log', 200);
$lastDone = -1; $lastFatal = -1;
foreach ($lines as $i => $ln) {
  if (strpos($ln, ' done') !== false) $lastDone = $i;
  if (stripos($ln, 'Fatal error') !== false || stripos($ln, 'PDOException') !== false) $lastFatal = $i;
}
$add('sync_errors', $lastFatal > $lastDone ? 'warn' : 'ok', $lastFatal > $lastDone ? 'فيه خطأ في آخر مزامنة' : 'مفيش أخطاء حديثة');

$bad = array_filter($checks, fn($c) => $c['level'] === 'bad');
$warn = array_filter($checks, fn($c) => $c['level'] === 'warn');
$overall = $bad ? 'bad' : ($warn ? 'warn' : 'ok');

$status = ['ts' => date('c'), 'overall' => $overall, 'checks' => $checks];
@file_put_contents($STATE . '/health.json', json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$txt = 'تقرير الصحة — ' . date('Y-m-d H:i') . ' — الحالة: ' . $overall . "\n";
foreach ($checks as $k => $c) $txt .= '[' . $c['level'] . '] ' . $c['msg'] . "\n";
@file_put_contents($STATE . '/health.txt', $txt);

// تنبيه عند تغيّر الحالة فقط
$prevFile = $STATE . '/watchdog_prev.txt';
$prev = is_file($prevFile) ? trim((string)file_get_contents($prevFile)) : '';
@file_put_contents($prevFile, $overall);
$emit = ($overall !== 'ok' && $overall !== $prev) || ($prev === 'bad' && $overall === 'ok');
if ($emit) {
  $msg = "RS_Own [$overall] " . date('H:i') . "\n";
  foreach ($checks as $c) if ($c['level'] !== 'ok') $msg .= $c['msg'] . "\n";
  @file_put_contents($LOG, date('Y-m-d H:i:s') . " ALERT $overall\n" . $msg . "\n", FILE_APPEND | LOCK_EX);
  $secrets = 'C:/ProgramData/RS_Own/secrets.php';
  if (is_file($secrets)) include $secrets;
  $tgT = (string)($RS_TG_TOKEN ?? '');
  $tgC = (string)($RS_TG_CHAT ?? '');
  if ($tgT !== '' && $tgC !== '' && function_exists('curl_init')) {
    $ch = curl_init('https://api.telegram.org/bot' . $tgT . '/sendMessage');
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => ['chat_id' => $tgC, 'text' => $msg],
      CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 15,
    ]);
    @curl_exec($ch); curl_close($ch);
  }
} else {
  @file_put_contents($LOG, date('Y-m-d H:i:s') . " $overall\n", FILE_APPEND | LOCK_EX);
}
flock($lock, LOCK_UN);
fclose($lock);
exit(0);
