<?php
// lib/guard.php — بوابة الدخول للنسخة الحرة (نفس جلسة lib/auth.php: $_SESSION['u'])
// تُستدعى صراحة من أول كل ملف (لا تعتمد على .user.ini وحده — لا يعمل على كل السيرفرات)
require_once __DIR__ . '/cfg.php';
require_once __DIR__ . '/session.php';
$__public = ['login.php', 'lang.php', 'ping.php', 'sync_in.php', 'migrate_in.php'];
// اسم الملف الحقيقي — يصحّه router.php لأن SCRIPT_NAME داخل الروتر نفسه
$__script = basename($GLOBALS['__GUARD_REAL_SCRIPT'] ?? ($_SERVER['SCRIPT_NAME'] ?? ''));
if (in_array($__script, $__public, true)) return;
app_session_start();
$__isApi = (strpos($__script, 'api_') === 0) || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

// — CSRF: توكن لكل جلسة + فحص تلقائي لأي POST خارج api_* وحقنه في كل النماذج —
function csrf_token(): string {
  app_session_start();
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_field(): string {
  return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_require(): void {
  app_session_start();
  $submitted = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? '');
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || $submitted === ''
    || !hash_equals(csrf_token(), $submitted)) {
    http_response_code(403);
    if (strpos($GLOBALS['__GUARD_REAL_SCRIPT'] ?? basename($_SERVER['SCRIPT_NAME'] ?? ''), 'api_') === 0) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
    } else {
      echo '⚠️ انتهت صلاحية الطلب (CSRF) — حدّث الصفحة وحاول تاني.';
    }
    exit;
  }
}
$__ct = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST)
  && (strpos($__script, 'api_') !== 0) && (stripos($__ct, 'application/json') === false)) {
  if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    exit('⚠️ انتهت صلاحية الطلب (CSRF) — حدّث الصفحة وحاول تاني.');
  }
}
// — api_* POST: فحص الأصل كامل (scheme + host + port) عبر lib/origin.php —
// يمنع المواقع الأجنبية من تمرير طلبات مع جلسة الضحية حتى لو كانت معفاة من التوكن.
// لا Origin ولا Referer (curl/CLI) مسموح؛ Origin: null مرفوض صراحةً.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && strpos($__script, 'api_') === 0) {
  if (!function_exists('api_origin_ok')) require_once __DIR__ . '/origin.php';
  if (!api_origin_ok($_SERVER)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'origin']);
    exit;
  }
}
// حقن التوكن تلقائياً في كل نماذج POST في مخرجات الصفحات
// (callback كسول: ما نستدعيش csrf_field إلا لو فيه فعلاً <form> — وإلا كان عاد يعيد
//  فتح الجلسة بعد logout ويعيد إنشاء ملف sess الممسوح)
ob_start(function ($h) {
  if (strpos($h, '<form') === false) return $h;
  return preg_replace_callback('/<form\b[^>]*method="post"[^>]*>/i', function ($m) {
    return $m[0] . csrf_field();
  }, $h);
});

if (empty($_SESSION['u'])) {
  if ($__isApi) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
  }
  $here = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  header('Location: ' . ($here === 'pages' ? 'login.php' : 'pages/login.php'));
  exit;
}
// إيقاف مستخدم (IS_Deleted=1) = قطع جلساته فوراً في نفس الطلب — إيقاف الحساب
// وترك الجلسة شغالة كان نقصاً معروفاً. يُفحص قبل ob_start حتى لا يُعاد إنشاء ملف
// الجلسة الممسوح عبر حقن csrf. فشل القراءة المؤقت لا يقطع أحداً (الصلاحيات تحت fail-closed).
if (!empty($_SESSION['u'])) {
  try {
    if (!function_exists('db')) require_once __DIR__ . '/db.php';
    $__gs = db()->prepare('SELECT IS_Deleted FROM users WHERE username=? LIMIT 1');
    $__gs->execute([$_SESSION['u']]);
    $__gu = $__gs->fetch();
    if (!$__gu || (int)$__gu['IS_Deleted'] === 1) {
      $_SESSION = [];
      if (ini_get('session.use_cookies')) {
        $__cp = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $__cp['path'], $__cp['domain'], $__cp['secure'], $__cp['httponly']);
      }
      session_destroy();
      if ($__isApi) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'login']);
        exit;
      }
      $__here = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
      header('Location: ' . ($__here === 'pages' ? 'login.php' : 'pages/login.php'));
      exit;
    }
  } catch (Throwable $__tg) { /* فشل مؤقت في قاعدة البيانات → نكمل (فحص الصلاحيات fail-closed) */ }
}
// صلاحيات الشاشات: الخريطة المركزية في cfg.php ( مصدر واحد مع القوائم) — admin كامل / بلا صفوف = لا وصول
$__map = cfg_perm_map();
$__need = $__map[$__script] ?? null;
if ($__need !== null && ($_SESSION['u'] ?? '') !== 'admin') {
  $__allow = [];
  try {
    // من الروتر: guard يعمل قبل db.php — نحرص على تعريف db() قبل أي استعلام صلاحيات
    if (!function_exists('db')) require_once __DIR__ . '/db.php';
    $__uid = (int)($_SESSION['uid'] ?? 0);
    if ($__uid === 0) {
      $__st = db()->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
      $__st->execute([$_SESSION['u']]);
      $__uid = (int)($__st->fetch()['id'] ?? 0);
    }
    if ($__uid > 0) {
      try {
        $__st = db()->prepare('SELECT perm FROM user_perms WHERE user_id=? AND val=1');
        $__st->execute([$__uid]);
        $__allow = array_column($__st->fetchAll(), 'perm');
        $__st = db()->prepare('SELECT COUNT(*) n FROM user_perms WHERE user_id=?');
        $__st->execute([$__uid]);
        if ((int)$__st->fetch()['n'] === 0) $__allow = []; // بلا صفوف = لا وصول
      } catch (Throwable $t2b) {
        if (strpos($t2b->getMessage(), "42S02") === false && strpos($t2b->getMessage(), "1146") === false) throw $t2b;
        // الجدول غير موجود فقط — ينشأ مرة واحدة ثم تُعاد القراءة (بدل DDL كل طلب)
        db()->exec("CREATE TABLE IF NOT EXISTS user_perms (user_id INT NOT NULL, perm VARCHAR(32) NOT NULL, val TINYINT NOT NULL DEFAULT 1, PRIMARY KEY (user_id, perm))");
        $__st = db()->prepare('SELECT perm FROM user_perms WHERE user_id=? AND val=1');
        $__st->execute([$__uid]);
        $__allow = array_column($__st->fetchAll(), 'perm');
        $__st = db()->prepare('SELECT COUNT(*) n FROM user_perms WHERE user_id=?');
        $__st->execute([$__uid]);
        if ((int)$__st->fetch()['n'] === 0) $__allow = [];
      }
    } else {
      $__allow = []; // لا يمكن تحديد الهوية → ممنوع
    }
  } catch (Throwable $t) {
    // fail-closed: أي خطأ في قراءة الصلاحيات = ممنوع (لا نترك الباب مفتوحاً)
    $__allow = [];
  }
  $__ok = in_array($__need, $__allow, true)
    || ($__need === 'm_item' && (in_array('m_sale', $__allow, true) || in_array('m_buy', $__allow, true)));
  if (!$__ok) {
    if ($__isApi) {
      http_response_code(403);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'noperm']);
      exit;
    }
    http_response_code(403);
    exit('⛔ مفيش صلاحية للصفحة دي — كلم الإدارة.');
  }
}
// موديول مُقفل من "لوحة تحكم المحل": يختفي من القوائم ويُمنع لغير المالك.
require_once __DIR__ . '/i18n.php';
if (!$__isApi) ob_start('i18n_inject_html');
if (!cfg_mod_allow($__script)) {
  if ($__isApi) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'module-off']);
    exit;
  }
  http_response_code(403);
  exit('🔒 الموديول ده مقفل أو تعذر التحقق من إعداداته — كلم الإدارة.');
}
