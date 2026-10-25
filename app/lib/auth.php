<?php
// lib/auth.php — دخول النسخة الحرة: جدول users (نفس مستخدمي البرنامج الأصلي) + حماية مقواة
if (!function_exists('db')) require __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

if (!function_exists('auth_start')) {
function auth_start(): void {
  app_session_start();
}
function auth_user(): ?string {
  auth_start();
  return $_SESSION['u'] ?? null;
}
function auth_need(): void {
  if (!auth_user()) {
    $here = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $login = ($here === 'pages') ? 'login.php' : 'pages/login.php';
    header('Location: ' . $login);
    exit;
  }
}
// تشفير كلمة مرور للتخزين (فراغ يبقى فراغاً حتى لا يُنشأ حساب بكلمة مرور فارغة مشفّرة)
function auth_hash_pwd(string $pass): string {
  $pass = trim($pass);
  if ($pass === '') return '';
  return password_hash(mb_substr($pass, 0, 72), PASSWORD_DEFAULT);
}
// IP الحقيقي: خلف nginx/cloudflared الـ REMOTE_ADDR دايماً 127.0.0.1
function auth_client_ip(): string {
  $ra = (string)($_SERVER['REMOTE_ADDR'] ?? '');
  $trusted = ($ra === '' || $ra === '127.0.0.1' || $ra === '::1');
  if ($trusted) {
    $cf = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if ($cf !== '' && preg_match('/^[0-9a-fA-F:.]+$/', $cf)) return $cf;
    $h = trim((string)($_SERVER['HTTP_X_REAL_IP'] ?? ''));
    if ($h === '') $h = trim((string)(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0] ?? ''));
    if ($h !== '' && preg_match('/^[0-9a-fA-F:.]+$/', $h)) $ra = $h;
  }
  return $ra !== '' ? $ra : 'unknown';
}
// حالة محاولات الدخول لكل IP (ملف واحد + قفل) — قفل 15 دقيقة بعد 10 فشل/15 دقيقة
function auth_throttle_file(): string {
  $d = app_data_dir();
  if (!is_dir($d)) @mkdir($d, 0777, true);
  return $d . '/login_attempts.json';
}
function auth_throttle_state(string $ip, bool $save = false, ?array $set = null): array {
  $f = auth_throttle_file();
  $fh = @fopen($f, 'c+');
  if (!$fh) return ['fail' => 0, 'first' => 0, 'lock' => 0];
  @flock($fh, LOCK_EX);
  $raw = stream_get_contents($fh);
  $all = json_decode((string)$raw, true);
  if (!is_array($all)) $all = [];
  $k = md5($ip);
  $now = time();
  foreach ($all as $kk => $vv) {
    if (!is_array($vv) || ($now - (int)($vv['last'] ?? 0)) > 86400) unset($all[$kk]);
  }
  $st = $all[$k] ?? ['fail' => 0, 'first' => $now, 'last' => $now, 'lock' => 0];
  if ($save && is_array($set)) {
    $st = array_merge($st, $set);
    $st['last'] = $now;
    $all[$k] = $st;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($all));
    fflush($fh);
  }
  @flock($fh, LOCK_UN);
  fclose($fh);
  return $st;
}
function auth_check(string $user, string $pass): bool {
  $ip = auth_client_ip();
  $st = auth_throttle_state($ip);
  if ((int)$st['lock'] > time()) {
    $min = (int)ceil(((int)$st['lock'] - time()) / 60);
    throw new RuntimeException('محاولات كتير غلط — جرّب بعد ' . $min . ' دقيقة');
  }
  $n = (int)$st['fail'];
  if ($n >= 5) sleep(min(2 * ($n - 4), 10));
  try {
    $stq = db()->prepare('SELECT id, username, pwd FROM users WHERE username = ? AND (IS_Deleted=0 OR IS_Deleted IS NULL) LIMIT 1');
    $stq->execute([$user]);
    $row = $stq->fetch();
  } catch (Throwable $t) { $row = false; }
  $stored = (string)($row['pwd'] ?? '');
  $pass = mb_substr($pass, 0, 72);
  $ok = false;
  if ($row && $stored !== '') {
    $info = password_get_info($stored);
    if (!empty($info['algo'])) {
      // bcrypt/argon2
      $ok = password_verify($pass, $stored);
      if ($ok && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
        try { db()->prepare('UPDATE users SET pwd=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$row['id']]); } catch (Throwable $t) {}
      }
    } else {
      // كلمة مرور قديمة نص صريح — مقارنة آمنة زمنياً + حد للطول
      $ok = hash_equals($stored, $pass);
      if ($ok) {
        // ترقية تلقائية إلى hash عند أول دخول ناجح
        try { db()->prepare('UPDATE users SET pwd=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$row['id']]); } catch (Throwable $t) {}
      }
    }
  }
  if ($ok) {
    auth_throttle_state($ip, true, ['fail' => 0, 'first' => 0, 'lock' => 0]);
    auth_start();
    session_regenerate_id(true);
    $_SESSION['u'] = $row['username'];
    $_SESSION['uid'] = (int)$row['id'];
    try { require_once __DIR__ . '/audit.php'; audit_log('login', 'users', (int)$row['id'], 'ok'); } catch (Throwable $t) {}
    return true;
  }
  $now = time();
  $first = (int)($st['first'] ?? 0);
  if ($first === 0 || ($now - $first) > 900) { $first = $now; $n = 0; }
  $n++;
  $lock = $n >= 10 ? $now + 900 : (int)($st['lock'] ?? 0);
  auth_throttle_state($ip, true, ['fail' => $n, 'first' => $first, 'lock' => $lock]);
  try { require_once __DIR__ . '/audit.php'; audit_log('login_fail', 'users', 0, 'u=' . mb_substr($user, 0, 64) . ' ip=' . $ip); } catch (Throwable $t) {}
  sleep(1);
  return false;
}
function auth_logout(): void {
  auth_start();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}
}
