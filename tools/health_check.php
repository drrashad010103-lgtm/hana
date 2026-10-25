<?php
date_default_timezone_set('Africa/Cairo');
if (PHP_OS_FAMILY === 'Windows' && function_exists('exec')) @exec('chcp 65001 >NUL');

$ROOT = dirname(__DIR__);
$APP = $ROOT . '/app';
$OPTS = getopt('', ['no-db', 'no-lint', 'with-tests', 'with-e2e', 'html', 'quiet', 'no-report']);
$NO_DB = isset($OPTS['no-db']);
$NO_LINT = isset($OPTS['no-lint']);
$WITH_TESTS = isset($OPTS['with-tests']);
$WITH_E2E = isset($OPTS['with-e2e']);
$HTML = isset($OPTS['html']);
$QUIET = isset($OPTS['quiet']);
$NO_REPORT = isset($OPTS['no-report']);

$GLOBALS['HC'] = [
    'root' => $ROOT,
    'app' => $APP,
    'cfg' => null,
    'cfg_loaded' => false,
    'pdo' => null,
    'pdo_failed' => false,
    'pdo_error' => '',
    'manifest' => null,
    'checks' => [],
    'no_db' => $NO_DB,
    'no_lint' => $NO_LINT,
];

$AREAS = [
    'env' => 'البيئة',
    'config' => 'الإعدادات',
    'db' => 'قاعدة البيانات',
    'data' => 'سلامة البيانات',
    'security' => 'الأمان والصلاحيات',
    'code' => 'الكود والملفات',
    'ops' => 'التشغيل والنسخ',
    'tests' => 'الاختبارات',
];

function hc_r(string $status, string $message, array $details = []): array {
    return ['status' => $status, 'message' => $message, 'details' => array_map('strval', array_values($details))];
}
function hc_ok(string $m, array $d = []): array { return hc_r('OK', $m, $d); }
function hc_info(string $m, array $d = []): array { return hc_r('INFO', $m, $d); }
function hc_warn(string $m, array $d = []): array { return hc_r('WARN', $m, $d); }
function hc_fail(string $m, array $d = []): array { return hc_r('FAIL', $m, $d); }
function hc_crit(string $m, array $d = []): array { return hc_r('CRIT', $m, $d); }

function hc_check(string $id, string $area, string $title, callable $fn): void {
    try {
        $r = $fn();
    } catch (Throwable $e) {
        $r = hc_fail('استثناء: ' . $e->getMessage());
    }
    $GLOBALS['HC']['checks'][] = [
        'id' => $id,
        'area' => $area,
        'title' => $title,
        'status' => $r['status'],
        'message' => $r['message'],
        'details' => $r['details'],
    ];
}

function hc_cfg(): ?array {
    if ($GLOBALS['HC']['cfg_loaded']) return $GLOBALS['HC']['cfg'];
    $GLOBALS['HC']['cfg_loaded'] = true;
    $f = $GLOBALS['HC']['app'] . '/config.php';
    if (!is_file($f)) return null;
    try {
        $c = require $f;
        return $GLOBALS['HC']['cfg'] = is_array($c) ? $c : null;
    } catch (Throwable $e) {
        return null;
    }
}

function hc_pdo(): ?PDO {
    if ($GLOBALS['HC']['pdo'] instanceof PDO) return $GLOBALS['HC']['pdo'];
    if (!empty($GLOBALS['HC']['no_db'])) {
        $GLOBALS['HC']['pdo_failed'] = true;
        $GLOBALS['HC']['pdo_error'] = '--no-db';
        return null;
    }
    if ($GLOBALS['HC']['pdo_failed']) return null;
    $c = hc_cfg();
    if (!$c || empty($c['db']) || !is_array($c['db'])) {
        $GLOBALS['HC']['pdo_failed'] = true;
        $GLOBALS['HC']['pdo_error'] = 'config.php غير موجود أو لا يحتوي db';
        return null;
    }
    $d = $c['db'];
    try {
        $GLOBALS['HC']['pdo'] = new PDO(
            'mysql:host=' . ($d['host'] ?? '') . ';dbname=' . ($d['name'] ?? '') . ';charset=' . ($d['charset'] ?? 'utf8mb4'),
            (string)($d['user'] ?? ''),
            (string)($d['pass'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        return $GLOBALS['HC']['pdo'];
    } catch (Throwable $e) {
        $GLOBALS['HC']['pdo_failed'] = true;
        $GLOBALS['HC']['pdo_error'] = $e->getMessage();
        return null;
    }
}

function hc_rows(string $sql, array $params = []): array {
    $pdo = hc_pdo();
    if (!$pdo) return [];
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}
function hc_val(string $sql, array $params = []) {
    $pdo = hc_pdo();
    if (!$pdo) return null;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}
function hc_table_exists(string $t): bool {
    $pdo = hc_pdo();
    if (!$pdo) return false;
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$t]);
    return (bool)$st->fetchColumn();
}
function hc_columns(string $t): array {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $t)) return [];
    $out = [];
    foreach (hc_rows('SHOW COLUMNS FROM `' . $t . '`') as $r) $out[strtolower((string)$r['Field'])] = (string)$r['Type'];
    return $out;
}
function hc_manifest(): array {
    if (is_array($GLOBALS['HC']['manifest'])) return $GLOBALS['HC']['manifest'];
    $f = $GLOBALS['HC']['app'] . '/lib/schema_manifest.php';
    if (!is_file($f)) return $GLOBALS['HC']['manifest'] = [];
    try {
        $m = require $f;
        return $GLOBALS['HC']['manifest'] = is_array($m) ? $m : [];
    } catch (Throwable $e) {
        return $GLOBALS['HC']['manifest'] = [];
    }
}
function hc_files(string $dir, string $ext = 'php'): array {
    if (!is_dir($dir)) return [];
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && strtolower($f->getExtension()) === strtolower($ext)) $out[] = $f->getPathname();
    }
    sort($out);
    return $out;
}
function hc_rel(string $path): string {
    $root = str_replace('\\', '/', $GLOBALS['HC']['root']) . '/';
    return str_replace('\\', '/', str_replace($root, '', $path));
}
function hc_age(int $seconds): string {
    if ($seconds < 0) return 'غير معروف';
    if ($seconds < 3600) return round($seconds / 60) . ' دقيقة';
    if ($seconds < 86400) return round($seconds / 3600, 1) . ' ساعة';
    return round($seconds / 86400, 1) . ' يوم';
}
function hc_bytes(float $n): string {
    if ($n >= 1073741824) return round($n / 1073741824, 1) . ' GB';
    if ($n >= 1048576) return round($n / 1048576, 1) . ' MB';
    if ($n >= 1024) return round($n / 1024, 1) . ' KB';
    return round($n) . ' B';
}
function hc_port(int $p): bool {
    $c = @fsockopen('127.0.0.1', $p, $e, $s, 1);
    if ($c) { fclose($c); return true; }
    return false;
}
function hc_last_stamp(string $file, int $tail = 400): int {
    if (!is_file($file)) return 0;
    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $lines = array_slice($lines, -$tail);
    $best = 0;
    foreach ($lines as $ln) {
        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $ln, $m)) {
            $t = strtotime($m[1]) ?: 0;
            if ($t > $best) $best = $t;
        }
    }
    return $best;
}
function hc_scan_calls(string $file, array $names): array {
    $src = @file_get_contents($file);
    if ($src === false) return [];
    $tokens = token_get_all($src);
    $out = [];
    $line = 1;
    $count = count($tokens);
    $prev = null;
    foreach ($tokens as $i => $t) {
        if (is_array($t)) {
            $line = (int)$t[2];
            $lower = strtolower((string)$t[1]);
            $isCall = ($t[0] === T_EVAL) || ($t[0] === T_STRING && in_array($lower, $names, true));
            if ($isCall) {
                $skip = is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR, T_FUNCTION, T_NEW], true);
                if (!$skip) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $n = $tokens[$j];
                        if (is_array($n) && in_array($n[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
                        if ($n === '(') $out[] = $lower . ' @ ' . hc_rel($file) . ':' . $line;
                        break;
                    }
                }
            }
            if (!in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) $prev = $t;
        } elseif (trim($t) !== '') {
            $prev = $t;
        }
    }
    return $out;
}
function hc_perm_map(): array {
    if (!function_exists('cfg_perm_map')) {
        $f = $GLOBALS['HC']['app'] . '/lib/cfg.php';
        if (is_file($f)) require_once $f;
    }
    return function_exists('cfg_perm_map') ? cfg_perm_map() : [];
}
function hc_cfg_links(): array {
    if (!function_exists('cfg_tools')) {
        $f = $GLOBALS['HC']['app'] . '/lib/cfg.php';
        if (is_file($f)) require_once $f;
    }
    $out = [];
    if (function_exists('cfg_tools')) {
        foreach (cfg_tools() as $tab => $items) {
            foreach ($items as $it) $out[] = ['source' => 'cfg_tools:' . $tab, 'file' => (string)($it[2] ?? '')];
        }
    }
    if (function_exists('cfg_screens')) {
        foreach (array_keys(cfg_screens()) as $file) $out[] = ['source' => 'cfg_screens', 'file' => (string)$file];
    }
    return $out;
}

hc_check('env.php', 'env', 'إصدار PHP', function () {
    $v = PHP_VERSION;
    if (version_compare($v, '8.0', '<')) return hc_fail('PHP ' . $v . ' — الحد الأدنى 8.0');
    if (version_compare($v, '8.1', '<')) return hc_warn('PHP ' . $v . ' — يفضل 8.1 أو أحدث');
    return hc_ok('PHP ' . $v);
});

hc_check('env.ext', 'env', 'الإضافات المطلوبة', function () {
    $req = ['pdo_mysql', 'mbstring', 'json', 'session', 'openssl'];
    $opt = ['curl', 'zip', 'fileinfo'];
    $miss = array_values(array_filter($req, fn($e) => !extension_loaded($e)));
    $optMiss = array_values(array_filter($opt, fn($e) => !extension_loaded($e)));
    if ($miss) return hc_fail('ناقصة: ' . implode(', ', $miss));
    if ($optMiss) return hc_info('اختيارية ناقصة: ' . implode(', ', $optMiss));
    return hc_ok('كل الإضافات المطلوبة موجودة');
});

hc_check('env.paths', 'env', 'المسارات الأساسية', function () {
    $root = $GLOBALS['HC']['root'];
    $app = $GLOBALS['HC']['app'];
    $d = [];
    $bad = [];
    foreach (['app' => $app, 'config.php' => $app . '/config.php', 'lib' => $app . '/lib', 'pages' => $app . '/pages', 'reports' => $root . '/reports', 'backups' => $root . '/backups'] as $k => $p) {
        $ok = is_file($p) || is_dir($p);
        $d[] = ($ok ? 'OK' : 'MISSING') . ' ' . $k . ' → ' . hc_rel($p);
        if (!$ok && in_array($k, ['app', 'config.php', 'lib', 'pages'], true)) $bad[] = $k;
    }
    return $bad ? hc_fail('مسارات ناقصة: ' . implode(', ', $bad), $d) : hc_ok('كل المسارات موجودة', $d);
});

hc_check('env.ini', 'env', 'إعدادات PHP', function () {
    $d = [];
    $warn = [];
    $de = strtolower((string)ini_get('display_errors'));
    $d[] = 'display_errors=' . ($de === '' ? '?' : $de);
    if (in_array($de, ['1', 'on', 'true'], true)) $d[] = 'تنبيه: display_errors مفعّل في CLI — راجع php.ini للإنتاج';
    $ml = (string)ini_get('memory_limit');
    $d[] = 'memory_limit=' . $ml;
    if ($ml !== '-1') {
        $bytes = (float)$ml;
        if (stripos($ml, 'g') !== false) $bytes *= 1073741824;
        elseif (stripos($ml, 'm') !== false) $bytes *= 1048576;
        elseif (stripos($ml, 'k') !== false) $bytes *= 1024;
        if ($bytes < 134217728) $warn[] = 'memory_limit أقل من 128M';
    }
    $d[] = 'timezone=' . date_default_timezone_get();
    return $warn ? hc_warn(implode(' | ', $warn), $d) : hc_ok('إعدادات PHP مقبولة', $d);
});

hc_check('env.server', 'env', 'سيرفر التطبيق :8082', function () {
    return hc_port(8082) ? hc_ok('السيرفر شغال على 127.0.0.1:8082') : hc_info('السيرفر مش شغال على 8082 (طبيعي لو بتستخدم Apache/إنفينيتي)');
});

hc_check('config.load', 'config', 'تحميل config.php', function () {
    $c = hc_cfg();
    if (!$c) return hc_crit('config.php غير موجود أو لا يرجع array');
    if (empty($c['db']) || !is_array($c['db'])) return hc_crit('مفتاح db غير موجود في config.php');
    return hc_ok('config.php محمّل', ['admin_user=' . ($c['admin_user'] ?? '?')]);
});

hc_check('config.db', 'config', 'إعدادات القاعدة', function () {
    $c = hc_cfg();
    if (!$c || empty($c['db'])) return hc_info('تم التخطي — لا config');
    $d = $c['db'];
    $miss = [];
    foreach (['host', 'name', 'user', 'charset'] as $k) if (!isset($d[$k]) || (string)$d[$k] === '') $miss[] = $k;
    $name = (string)($d['name'] ?? '');
    $charset = (string)($d['charset'] ?? '');
    $det = ['name=' . $name, 'host=' . ($d['host'] ?? ''), 'charset=' . $charset];
    if ($miss) return hc_fail('ناقص: ' . implode(', ', $miss), $det);
    if ($charset !== 'utf8mb4') return hc_warn('charset=' . $charset . ' (المتوقع utf8mb4)', $det);
    return hc_ok('إعدادات القاعدة مكتملة', $det);
});

hc_check('config.secrets', 'config', 'الأسرار و EasyOrders', function () {
    $root = $GLOBALS['HC']['root'];
    $c = hc_cfg();
    $d = [];
    $sec = is_file($root . '/secrets.php');
    $d[] = 'secrets.php: ' . ($sec ? 'موجود' : 'غير موجود');
    $key = (string)($c['easyorders']['key'] ?? '');
    $d[] = 'easyorders.key: ' . ($key !== '' ? 'مضبوط' : 'فاضي');
    if ($key !== '') return hc_ok('مفتاح EasyOrders مضبوط', $d);
    if (!$sec) return hc_warn('لا secrets.php ولا easyorders.key — المزامنة معطلة', $d);
    return hc_warn('easyorders.key فاضي — مزامنة EasyOrders معطلة', $d);
});

hc_check('config.setup', 'config', 'setup.php', function () {
    $f = $GLOBALS['HC']['app'] . '/setup.php';
    return is_file($f) ? hc_crit('setup.php موجود — يجب حذفه فوراً') : hc_ok('setup.php محذوف');
});

hc_check('config.htaccess', 'config', 'حماية .htaccess', function () {
    $f = $GLOBALS['HC']['app'] . '/.htaccess';
    if (!is_file($f)) return hc_fail('.htaccess غير موجود');
    $s = (string)@file_get_contents($f);
    $d = [];
    $bad = [];
    $need = [
        'config' => ['config.php', 'config\\.php'],
        'lib' => ['lib'],
        'sql' => ['sql'],
        'push_secret.php' => ['push_secret.php'],
        'schema_manifest.php' => ['schema_manifest.php'],
    ];
    foreach ($need as $label => $needles) {
        $ok = false;
        foreach ($needles as $needle) {
            if (str_contains($s, $needle)) { $ok = true; break; }
        }
        $d[] = ($ok ? 'OK' : 'MISSING') . ' ' . $label;
        if (!$ok) $bad[] = $label;
    }
    return $bad ? hc_warn('حماية ناقصة: ' . implode(', ', $bad), $d) : hc_ok('حماية .htaccess سليمة', $d);
});

hc_check('config.secrets_location', 'config', 'موقع secrets.php', function () {
    $root = realpath($GLOBALS['HC']['root']);
    $app = realpath($GLOBALS['HC']['app']);
    $sec = realpath($GLOBALS['HC']['root'] . '/secrets.php');
    if (!$sec) return hc_info('secrets.php غير موجود');
    if ($app && str_starts_with(str_replace('\\', '/', $sec), str_replace('\\', '/', $app) . '/')) {
        return hc_fail('secrets.php داخل web root — خطر تعريضه');
    }
    return hc_ok('secrets.php خارج web root');
});

hc_check('db.connect', 'db', 'الاتصال بقاعدة البيانات', function () {
    if (!empty($GLOBALS['HC']['no_db'])) return hc_info('تم التخطي (--no-db)');
    if ($GLOBALS['HC']['cfg_loaded'] === false) hc_cfg();
    if ($GLOBALS['HC']['pdo_failed']) return hc_crit('فشل الاتصال: ' . $GLOBALS['HC']['pdo_error']);
    $pdo = hc_pdo();
    if (!$pdo) return hc_crit('فشل الاتصال: ' . $GLOBALS['HC']['pdo_error']);
    $c = hc_cfg();
    return hc_ok('متصل بـ ' . (string)($c['db']['name'] ?? '?'), ['server=' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)]);
});

hc_check('db.tables', 'db', 'جداول القاعدة مقابل schema_manifest', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $m = hc_manifest();
    if (!$m || empty($m['tables'])) return hc_warn('schema_manifest غير موجود — تعذر المقارنة');
    $live = [];
    foreach (hc_rows('SHOW TABLES') as $r) $live[strtolower((string)reset($r))] = true;
    $missing = [];
    foreach (array_keys($m['tables']) as $t) if (!isset($live[strtolower($t)])) $missing[] = $t;
    $extra = array_values(array_diff(array_keys($live), array_map('strtolower', array_keys($m['tables']))));
    $d = ['live=' . count($live), 'manifest=' . count($m['tables'])];
    if ($missing) return hc_fail('جداول ناقصة: ' . implode(', ', array_slice($missing, 0, 20)), $d);
    if ($extra) return hc_warn('جداول إضافية غير موجودة في المانيفست: ' . implode(', ', array_slice($extra, 0, 10)), $d);
    return hc_ok('كل الجداول موجودة', $d);
});

hc_check('db.columns', 'db', 'أعمدة الجداول مقابل schema_manifest', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $m = hc_manifest();
    if (!$m || empty($m['tables'])) return hc_warn('schema_manifest غير موجود');
    $missing = [];
    foreach ($m['tables'] as $t => $def) {
        if (!hc_table_exists($t)) continue;
        $cols = hc_columns($t);
        foreach (array_keys($def['cols'] ?? []) as $col) {
            if (!isset($cols[strtolower($col)])) $missing[] = $t . '.' . $col;
        }
    }
    if ($missing) return hc_fail('أعمدة ناقصة (' . count($missing) . ')', array_slice($missing, 0, 30));
    return hc_ok('كل الأعمدة موجودة');
});

hc_check('db.indexes', 'db', 'الفهارس المهمة', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $need = [
        'inv' => ['proc_id', 'branch'],
        'inv_sub' => ['proc_id'],
        'currencies' => ['id'],
        'item_bal' => ['id'],
    ];
    $bad = [];
    foreach ($need as $t => $keys) {
        if (!hc_table_exists($t)) continue;
        $have = [];
        foreach (hc_rows('SHOW INDEX FROM `' . $t . '`') as $r) $have[strtolower((string)$r['Column_name'])] = true;
        foreach ($keys as $k) if (!isset($have[strtolower($k)])) $bad[] = $t . '.' . $k;
    }
    return $bad ? hc_warn('فهارس ناقصة: ' . implode(', ', $bad)) : hc_ok('الفهارس المهمة موجودة');
});

hc_check('db.counts', 'db', 'إحصاءات القاعدة', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $q = [
        'inv' => 'SELECT COUNT(*) FROM inv',
        'inv_sub' => 'SELECT COUNT(*) FROM inv_sub',
        'currencies' => 'SELECT COUNT(*) FROM currencies',
        'item_bal' => 'SELECT COUNT(*) FROM item_bal',
        'users' => 'SELECT COUNT(*) FROM users',
        'audit_log' => 'SELECT COUNT(*) FROM audit_log',
    ];
    $d = [];
    foreach ($q as $k => $sql) {
        try { $d[] = $k . '=' . (int)hc_val($sql); } catch (Throwable $e) { $d[] = $k . '=?'; }
    }
    return hc_info('إحصاءات الجداول', $d);
});

hc_check('data.dup_proc', 'data', 'تكرار proc_id في الفواتير', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $rows = hc_rows('SELECT proc_id, COUNT(*) c FROM inv GROUP BY proc_id HAVING c > 1 ORDER BY c DESC LIMIT 20');
    if ($rows) {
        $d = [];
        foreach ($rows as $r) $d[] = 'proc_id=' . $r['proc_id'] . ' ×' . $r['c'];
        return hc_fail('يوجد تكرار (' . count($rows) . ')', $d);
    }
    return hc_ok('لا يوجد تكرار');
});

hc_check('data.orphan_heads', 'data', 'فواتير سارية بدون بنود', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $all = (int)hc_val('SELECT COUNT(*) FROM inv i WHERE IFNULL(i.IS_Deleted,0)=0 AND NOT EXISTS (SELECT 1 FROM inv_sub s WHERE s.proc_id=i.proc_id)');
    $b99 = (int)hc_val('SELECT COUNT(*) FROM inv i WHERE IFNULL(i.IS_Deleted,0)=0 AND i.branch=99 AND NOT EXISTS (SELECT 1 FROM inv_sub s WHERE s.proc_id=i.proc_id)');
    $d = ['all=' . $all, 'branch99=' . $b99];
    foreach (hc_rows('SELECT i.proc_id,i.branch FROM inv i WHERE IFNULL(i.IS_Deleted,0)=0 AND NOT EXISTS (SELECT 1 FROM inv_sub s WHERE s.proc_id=i.proc_id) ORDER BY i.proc_id DESC LIMIT 5') as $r) {
        $d[] = 'proc_id=' . $r['proc_id'] . ' branch=' . $r['branch'];
    }
    if ($b99 > 0) return hc_fail('فواتير فرع 99 بدون بنود: ' . $b99, $d);
    if ($all > 0) return hc_warn('فواتير بدون بنود: ' . $all, $d);
    return hc_ok('لا توجد فواتير سارية بدون بنود', $d);
});

hc_check('data.orphan_lines', 'data', 'بنود يتيمة (بدون رأس)', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $n = (int)hc_val('SELECT COUNT(*) FROM inv_sub s WHERE NOT EXISTS (SELECT 1 FROM inv i WHERE i.proc_id=s.proc_id)');
    return $n > 0 ? hc_warn('بنود بدون رأس: ' . $n . ' (راجع _orphan_sub)') : hc_ok('لا توجد بنود يتيمة');
});

hc_check('data.negative_bal', 'data', 'الأرصدة السالبة', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $n = (int)hc_val('SELECT COUNT(*) FROM item_bal WHERE bal < 0');
    $sum = (float)hc_val('SELECT IFNULL(SUM(bal),0) FROM item_bal WHERE bal < 0');
    if ($n === 0) return hc_ok('لا توجد أرصدة سالبة');
    return hc_warn('أرصدة سالبة: ' . $n . ' (مجموع ' . round($sum, 3) . ') — موروثة من بيانات المهندس غالباً');
});

hc_check('data.item_bal', 'data', 'تغطية item_bal للأصناف', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $miss = (int)hc_val('SELECT COUNT(*) FROM currencies c LEFT JOIN item_bal b ON b.id=c.id WHERE b.id IS NULL');
    $extra = (int)hc_val('SELECT COUNT(*) FROM item_bal b LEFT JOIN currencies c ON c.id=b.id WHERE c.id IS NULL');
    $d = ['missing=' . $miss, 'orphan=' . $extra];
    if ($miss > 0) return hc_warn('أصناف بدون رصيد: ' . $miss, $d);
    if ($extra > 0) return hc_warn('أرصدة بدون صنف: ' . $extra, $d);
    return hc_ok('تغطية item_bal كاملة', $d);
});

hc_check('data.branch99', 'data', 'ترقيم فرع 99', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $mx = hc_rows('SELECT MAX(proc_id) p, MAX(id) i FROM inv WHERE branch=99');
    $p = (int)($mx[0]['p'] ?? 0);
    $i = (int)($mx[0]['i'] ?? 0);
    $active = (int)hc_val('SELECT COUNT(*) FROM inv WHERE branch=99 AND IS_Deleted=0');
    $seq = [];
    foreach (hc_rows("SELECT name,val FROM rs_seq WHERE name IN ('seq_proc','seq_inv')") as $r) $seq[$r['name']] = (int)$r['val'];
    $d = ['max_proc=' . $p, 'max_id=' . $i, 'active=' . $active, 'seq_proc=' . ($seq['seq_proc'] ?? '?'), 'seq_inv=' . ($seq['seq_inv'] ?? '?')];
    $warn = [];
    if ($p > 0 && $p < 10000000) $warn[] = 'أرقام فرع 99 أقل من 10000000';
    if (($seq['seq_inv'] ?? 0) < $i) $warn[] = 'seq_inv أقل من max_id المحلي';
    if (($seq['seq_proc'] ?? 0) < $p) $warn[] = 'seq_proc أقل من max_proc المحلي';
    if ($warn) return hc_warn(implode(' | ', $warn), $d);
    return hc_ok('ترقيم فرع 99 متسق', $d);
});

hc_check('data.audit', 'data', 'سجل التدقيق', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    if (!hc_table_exists('audit_log')) return hc_fail('جدول audit_log غير موجود');
    $n = (int)hc_val('SELECT COUNT(*) FROM audit_log');
    $last = (string)hc_val('SELECT MAX(ts) FROM audit_log');
    if ($n === 0) return hc_warn('audit_log فاضي');
    $age = $last ? time() - (strtotime($last) ?: time()) : PHP_INT_MAX;
    $d = ['rows=' . $n, 'last=' . ($last ?: '—')];
    if ($age > 30 * 86400) return hc_warn('آخر تدقيق قديم (' . hc_age($age) . ')', $d);
    return hc_ok('سجل التدقيق شغال', $d);
});

hc_check('data.flags', 'data', 'أعلام الفواتير للمستخدمين', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $n = (int)hc_val("SELECT COUNT(*) FROM users WHERE (IS_Deleted=0 OR IS_Deleted IS NULL) AND username<>'admin' AND (edit_inv IS NULL OR edit_inv=0)");
    return $n > 0 ? hc_info('مستخدمون بلا صلاحية تعديل فاتورة (Fail-Closed): ' . $n) : hc_ok('كل المستخدمين لديهم أعلام واضحة');
});

hc_check('data.pay_methods', 'data', 'طرق الدفع', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    if (!hc_table_exists('pay_methods')) return hc_warn('جدول pay_methods غير موجود (يُنشأ عند أول استخدام)');
    $n = (int)hc_val('SELECT COUNT(*) FROM pay_methods WHERE active=1');
    return $n > 0 ? hc_ok('طرق دفع مفعّلة: ' . $n) : hc_warn('لا توجد طرق دفع مفعّلة');
});

hc_check('data.inv_types', 'data', 'قيم أنواع الفواتير', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $n = (int)hc_val('SELECT COUNT(*) FROM inv WHERE proc_type NOT IN (1,2,3,4)');
    $b = (int)hc_val('SELECT COUNT(*) FROM inv WHERE IS_Buy NOT IN (0,1)');
    if ($n > 0 || $b > 0) return hc_warn('قيم غير متوقعة: proc_type=' . $n . ' IS_Buy=' . $b);
    return hc_ok('أنواع الفواتير سليمة');
});

hc_check('sec.users_empty_pwd', 'security', 'مستخدمون بكلمة مرور فارغة', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $n = (int)hc_val("SELECT COUNT(*) FROM users WHERE (IS_Deleted=0 OR IS_Deleted IS NULL) AND (pwd IS NULL OR pwd='')");
    return $n > 0 ? hc_fail('مستخدمون بكلمة مرور فارغة: ' . $n) : hc_ok('لا توجد كلمات مرور فارغة');
});

hc_check('sec.users_plaintext', 'security', 'كلمات مرور غير مشفرة', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $rows = hc_rows("SELECT username,pwd FROM users WHERE (IS_Deleted=0 OR IS_Deleted IS NULL) AND pwd IS NOT NULL AND pwd<>''");
    $plain = [];
    foreach ($rows as $r) {
        $info = password_get_info((string)$r['pwd']);
        if (empty($info['algo'])) $plain[] = (string)$r['username'];
    }
    return $plain ? hc_warn('نص صريح (يترقى عند أول دخول): ' . implode(', ', $plain)) : hc_ok('كل كلمات المرور مشفرة');
});

hc_check('sec.users_noperms', 'security', 'مستخدمون بلا صلاحيات', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    if (!hc_table_exists('user_perms')) return hc_fail('جدول user_perms غير موجود');
    $rows = hc_rows("SELECT u.username FROM users u LEFT JOIN user_perms p ON p.user_id=u.id WHERE (u.IS_Deleted=0 OR u.IS_Deleted IS NULL) AND u.username<>'admin' GROUP BY u.id,u.username HAVING COUNT(p.perm)=0");
    $names = array_map(fn($r) => (string)$r['username'], $rows);
    return $names ? hc_warn('ممنوعون (Fail-Closed): ' . implode(', ', $names)) : hc_ok('كل المستخدمين لديهم صلاحيات');
});

hc_check('sec.admin', 'security', 'حساب admin', function () {
    if (!hc_pdo()) return hc_info('تم التخطي — لا اتصال');
    $n = (int)hc_val("SELECT COUNT(*) FROM users WHERE username='admin' AND (IS_Deleted=0 OR IS_Deleted IS NULL)");
    return $n > 0 ? hc_ok('حساب admin موجود') : hc_warn('حساب admin غير موجود/موقوف');
});

hc_check('sec.perm_map', 'security', 'خريطة الصلاحيات', function () {
    $map = hc_perm_map();
    if (!$map) return hc_warn('تعذر تحميل cfg_perm_map');
    $pagesDir = $GLOBALS['HC']['app'] . '/pages';
    $missing = [];
    foreach (array_keys($map) as $file) if (!is_file($pagesDir . '/' . $file)) $missing[] = $file;
    if ($missing) return hc_warn('صفحات في الخريطة غير موجودة: ' . implode(', ', array_slice($missing, 0, 20)));
    return hc_ok('خريطة الصلاحيات سليمة (' . count($map) . ' صفحة)');
});

hc_check('sec.pages_unmapped', 'security', 'صفحات بلا صلاحية محددة', function () {
    $map = hc_perm_map();
    if (!$map) return hc_info('تم التخطي — لا خريطة');
    $public = ['login.php', 'logout.php', 'lang.php', 'ping.php', 'sync_in.php', 'migrate_in.php', 'item_img.php'];
    $unmapped = [];
    foreach (glob($GLOBALS['HC']['app'] . '/pages/*.php') ?: [] as $f) {
        $b = basename($f);
        if (in_array($b, $public, true)) continue;
        if (!isset($map[$b])) $unmapped[] = $b;
    }
    return $unmapped ? hc_warn('صفحات بدون m_*: ' . implode(', ', $unmapped)) : hc_ok('كل الصفحات مغطاة بالخريطة');
});

hc_check('sec.session', 'security', 'إعدادات الجلسة', function () {
    $f = $GLOBALS['HC']['app'] . '/lib/session.php';
    if (!is_file($f)) return hc_fail('lib/session.php غير موجود');
    $s = (string)@file_get_contents($f);
    $bad = [];
    foreach (['httponly' => 'HttpOnly', 'samesite' => 'SameSite', 'secure' => 'Secure'] as $k => $label) {
        if (stripos($s, $k) === false) $bad[] = $label;
    }
    return $bad ? hc_warn('ناقص: ' . implode(', ', $bad)) : hc_ok('إعدادات الجلسة آمنة');
});

hc_check('sec.csrf', 'security', 'حماية CSRF', function () {
    $f = $GLOBALS['HC']['app'] . '/lib/guard.php';
    if (!is_file($f)) return hc_fail('lib/guard.php غير موجود');
    $s = (string)@file_get_contents($f);
    $d = [];
    foreach (['csrf_require' => 'csrf_require()', 'HTTP_X_CSRF_TOKEN' => 'X-CSRF-Token header', 'csrf_field' => 'csrf_field()'] as $k => $label) {
        $ok = stripos($s, $k) !== false;
        $d[] = ($ok ? 'OK' : 'MISSING') . ' ' . $label;
    }
    return str_contains(implode(' ', $d), 'MISSING') ? hc_warn('حماية CSRF ناقصة', $d) : hc_ok('حماية CSRF موجودة', $d);
});

hc_check('sec.dangerous_calls', 'security', 'دوال خطرة في app', function () {
    $names = ['eval', 'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'unserialize', 'extract', 'create_function', 'assert'];
    $hits = [];
    foreach (hc_files($GLOBALS['HC']['app']) as $f) {
        foreach (hc_scan_calls($f, $names) as $h) $hits[] = $h;
    }
    return $hits ? hc_warn('دوال تحتاج مراجعة (' . count($hits) . ')', array_slice($hits, 0, 30)) : hc_ok('لا توجد دوال خطرة');
});

hc_check('code.lint', 'code', 'فحص الصياغة (lint)', function () {
    if (!empty($GLOBALS['HC']['no_lint'])) return hc_info('تم التخطي (--no-lint)');
    if (!function_exists('exec')) return hc_warn('exec معطّل — تعذر تشغيل lint');
    $files = array_merge(hc_files($GLOBALS['HC']['app']), hc_files($GLOBALS['HC']['root'] . '/tools'), hc_files($GLOBALS['HC']['root'] . '/tests'));
    $bad = [];
    foreach ($files as $f) {
        $out = [];
        $code = 0;
        @exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($f) . ' 2>&1', $out, $code);
        if ($code !== 0) $bad[] = hc_rel($f);
    }
    return $bad ? hc_fail('أخطاء صياغة: ' . count($bad), array_slice($bad, 0, 20)) : hc_ok('كل الملفات سليمة (' . count($files) . ' ملف)');
});

hc_check('code.guards', 'code', 'حماية إعادة التحميل في lib', function () {
    $lib = $GLOBALS['HC']['app'] . '/lib';
    $plain = [];
    foreach (hc_files($GLOBALS['HC']['app']) as $f) {
        $s = (string)@file_get_contents($f);
        if (preg_match_all('/\brequire\b(?!_once)\s+[^;]*?\/([A-Za-z0-9_]+\.php)/', $s, $m)) {
            foreach ($m[1] as $b) $plain[$b] = true;
        }
    }
    $bad = [];
    foreach (array_keys($plain) as $b) {
        $f = $lib . '/' . $b;
        if (!is_file($f)) continue;
        $s = (string)@file_get_contents($f);
        if (str_contains($s, 'function ') && !str_contains($s, 'function_exists(')) $bad[] = $b;
    }
    return $bad ? hc_warn('ملفات تُحمّل بـ require بلا function_exists: ' . implode(', ', $bad)) : hc_ok('ملفات lib المحمّلة بـ require محمية');
});

hc_check('code.links', 'code', 'روابط القوائم والشاشات', function () {
    $links = hc_cfg_links();
    if (!$links) return hc_info('تم التخطي — لا cfg');
    $missing = [];
    foreach ($links as $l) {
        $file = explode('?', $l['file'])[0];
        if ($file === '') continue;
        if (!is_file($GLOBALS['HC']['app'] . '/pages/' . $file)) $missing[] = $l['source'] . ' → ' . $l['file'];
    }
    return $missing ? hc_warn('روابط لصفحات غير موجودة: ' . count($missing), array_slice($missing, 0, 20)) : hc_ok('كل روابط القوائم سليمة');
});

hc_check('code.todos', 'code', 'ملاحظات TODO/FIXME', function () {
    $hits = [];
    foreach (hc_files($GLOBALS['HC']['app']) as $f) {
        $s = (string)@file_get_contents($f);
        if (preg_match_all('/\b(TODO|FIXME|HACK|XXX)\b/i', $s, $m)) {
            foreach ($m[0] as $x) $hits[] = strtoupper($x) . ' @ ' . hc_rel($f);
        }
    }
    return $hits ? hc_info('ملاحظات: ' . count($hits), array_slice($hits, 0, 20)) : hc_ok('لا توجد ملاحظات TODO/FIXME');
});

hc_check('code.devpaths', 'code', 'مسارات تطوير مثبتة', function () {
    $hits = [];
    foreach (hc_files($GLOBALS['HC']['app']) as $f) {
        foreach (@file($f, FILE_IGNORE_NEW_LINES) ?: [] as $i => $ln) {
            if (preg_match('~[A-Za-z]:[\\\\/](?:Users|phplocal|xampp|wamp|laragon|phpstudy)(?:[\\\\/]|$)~i', $ln)) $hits[] = hc_rel($f) . ':' . ($i + 1);
        }
    }
    return $hits ? hc_warn('مسارات مطلقة في app: ' . count($hits), array_slice($hits, 0, 20)) : hc_ok('لا توجد مسارات مطلقة');
});

hc_check('code.size', 'code', 'أكبر الملفات', function () {
    $files = [];
    foreach (hc_files($GLOBALS['HC']['app']) as $f) $files[$f] = (int)filesize($f);
    arsort($files);
    $d = [];
    foreach (array_slice($files, 0, 5, true) as $f => $sz) $d[] = hc_rel($f) . ' = ' . hc_bytes((float)$sz);
    return hc_info('أكبر 5 ملفات', $d);
});

hc_check('ops.backup', 'ops', 'النسخ الاحتياطي', function () {
    $root = $GLOBALS['HC']['root'];
    if (is_file($root . '/backup.failed')) return hc_fail('backup.failed موجود: ' . trim((string)@file_get_contents($root . '/backup.failed')));
    $files = glob($root . '/backups/rs_own_*.sql') ?: [];
    if (!$files) return hc_fail('لا توجد نسخ احتياطية');
    $latest = null;
    foreach ($files as $f) if ($latest === null || filemtime($f) > filemtime($latest)) $latest = $f;
    $age = time() - filemtime($latest);
    $d = [basename($latest) . ' (' . hc_age($age) . ')'];
    if ($age > 7 * 86400) return hc_fail('أحدث نسخة قديمة: ' . hc_age($age), $d);
    if ($age > 48 * 3600) return hc_warn('أحدث نسخة عمرها ' . hc_age($age), $d);
    return hc_ok('نسخة حديثة', $d);
});

hc_check('ops.sync', 'ops', 'آخر مزامنة', function () {
    $t = hc_last_stamp($GLOBALS['HC']['root'] . '/tools/sync.log', 500);
    if (!$t) return hc_warn('لا يوجد سجل مزامنة');
    $age = time() - $t;
    $d = [date('Y-m-d H:i', $t) . ' (' . hc_age($age) . ')'];
    if ($age > 50 * 3600) return hc_fail('المزامنة متأخرة: ' . hc_age($age), $d);
    if ($age > 26 * 3600) return hc_warn('المزامنة متأخرة: ' . hc_age($age), $d);
    return hc_ok('المزامنة حديثة', $d);
});

hc_check('ops.watchdog', 'ops', 'مراقب الصحة', function () {
    $f = $GLOBALS['HC']['root'] . '/status/health.json';
    if (!is_file($f)) return hc_warn('status/health.json غير موجود');
    $j = json_decode((string)@file_get_contents($f), true);
    if (!is_array($j) || empty($j['ts'])) return hc_warn('health.json غير صالح');
    $age = time() - (strtotime((string)$j['ts']) ?: 0);
    $d = ['overall=' . ($j['overall'] ?? '?'), 'ts=' . $j['ts']];
    $bad = [];
    foreach (($j['checks'] ?? []) as $k => $v) {
        if (($v['level'] ?? '') === 'bad') $bad[] = $k . ': ' . (string)($v['msg'] ?? '');
    }
    foreach ($bad as $b) $d[] = $b;
    if (($j['overall'] ?? '') === 'bad') return hc_warn('المراقب يقول bad (' . count($bad) . ')', $d);
    if ($age > 2 * 3600) return hc_warn('آخر تقرير مراقب عمره ' . hc_age($age), $d);
    return hc_ok('المراقب شغال', $d);
});

hc_check('ops.verify', 'ops', 'مطابقة المهندس', function () {
    $f = $GLOBALS['HC']['root'] . '/status/verify.json';
    if (!is_file($f)) return hc_info('status/verify.json غير موجود');
    $j = json_decode((string)@file_get_contents($f), true);
    if (!is_array($j) || empty($j['ts'])) return hc_warn('verify.json غير صالح');
    $age = time() - (strtotime((string)$j['ts']) ?: 0);
    $d = ['ok=' . (!empty($j['ok']) ? '1' : '0'), 'ts=' . $j['ts']];
    if (empty($j['ok'])) return hc_warn('آخر مطابقة فيها فروق', $d);
    if ($age > 50 * 3600) return hc_warn('آخر مطابقة عمرها ' . hc_age($age), $d);
    return hc_ok('المطابقة سليمة', $d);
});

hc_check('ops.disk', 'ops', 'مساحة القرص', function () {
    $free = @disk_free_space('D:');
    $total = @disk_total_space('D:');
    if (!$free || !$total) return hc_info('تعذر قراءة مساحة D:');
    $pct = $free / $total * 100;
    $d = [round($pct, 1) . '% فاضي (' . hc_bytes((float)$free) . ')'];
    if ($pct < 5) return hc_fail('مساحة منخفضة جداً', $d);
    if ($pct < 10) return hc_warn('مساحة منخفضة', $d);
    return hc_ok('المساحة كافية', $d);
});

hc_check('ops.logs', 'ops', 'أخطاء حديثة في اللوج', function () {
    $f = $GLOBALS['HC']['root'] . '/tools/sync.log';
    if (!is_file($f)) return hc_info('sync.log غير موجود');
    $lines = @file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $lines = array_slice($lines, -300);
    $lastDone = -1;
    $lastErr = -1;
    foreach ($lines as $i => $ln) {
        if (str_contains($ln, ' done')) $lastDone = $i;
        if (stripos($ln, 'Fatal error') !== false || stripos($ln, 'PDOException') !== false) $lastErr = $i;
    }
    return $lastErr > $lastDone ? hc_warn('خطأ بعد آخر مزامنة ناجحة') : hc_ok('لا أخطاء حديثة في sync.log');
});

hc_check('tests.files', 'tests', 'ملفات الاختبارات', function () {
    $root = $GLOBALS['HC']['root'];
    $unit = count(glob($root . '/tests/*_test.php') ?: []);
    $phpunit = count(glob($root . '/tests/*Test.php') ?: []);
    $d = ['focused=' . $unit, 'phpunit=' . $phpunit];
    if ($unit + $phpunit === 0) return hc_warn('لا توجد اختبارات', $d);
    return hc_ok('اختبارات موجودة', $d);
});

hc_check('tests.tools', 'tests', 'أدوات الاختبار', function () {
    $root = $GLOBALS['HC']['root'];
    $d = [];
    $bad = [];
    foreach (['phpunit.xml' => $root . '/phpunit.xml', 'phpunit.phar' => $root . '/tools/phpunit.phar', 'run_all.ps1' => $root . '/tests/run_all.ps1'] as $k => $f) {
        $ok = is_file($f);
        $d[] = ($ok ? 'OK' : 'MISSING') . ' ' . $k;
        if (!$ok) $bad[] = $k;
    }
    return $bad ? hc_warn('ناقص: ' . implode(', ', $bad), $d) : hc_ok('أدوات الاختبار موجودة', $d);
});

if ($WITH_TESTS) {
    hc_check('tests.run', 'tests', 'تشغيل PHPUnit', function () {
        if (!function_exists('exec')) return hc_warn('exec معطّل');
        $root = $GLOBALS['HC']['root'];
        $out = [];
        $code = 0;
        @exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/phpunit.phar') . ' -c ' . escapeshellarg($root . '/phpunit.xml') . ' 2>&1', $out, $code);
        $tail = array_slice($out, -8);
        return $code === 0 ? hc_ok('PHPUnit نجح', $tail) : hc_fail('PHPUnit فشل (exit ' . $code . ')', $tail);
    });
}

if ($WITH_E2E) {
    hc_check('tests.e2e', 'tests', 'تشغيل E2E الكامل', function () {
        if (!function_exists('exec')) return hc_warn('exec معطّل');
        $root = $GLOBALS['HC']['root'];
        $out = [];
        $code = 0;
        @exec('powershell -ExecutionPolicy Bypass -File ' . escapeshellarg($root . '/tests/run_all.ps1') . ' 2>&1', $out, $code);
        $tail = array_slice($out, -12);
        return $code === 0 ? hc_ok('E2E نجح', $tail) : hc_fail('E2E فشل (exit ' . $code . ')', $tail);
    });
}

$checks = $GLOBALS['HC']['checks'];
$counts = ['OK' => 0, 'INFO' => 0, 'WARN' => 0, 'FAIL' => 0, 'CRIT' => 0];
foreach ($checks as $c) $counts[$c['status']] = ($counts[$c['status']] ?? 0) + 1;
$overall = $counts['CRIT'] > 0 ? 'CRIT' : ($counts['FAIL'] > 0 ? 'FAIL' : ($counts['WARN'] > 0 ? 'WARN' : 'OK'));
$overallAr = ['CRIT' => 'حرج', 'FAIL' => 'فشل', 'WARN' => 'تحذير', 'OK' => 'سليم', 'INFO' => 'معلومة'][$overall] ?? $overall;
$stamp = date('Ymd_His');
$reportDir = $ROOT . '/reports';
if (!$NO_REPORT && !is_dir($reportDir)) @mkdir($reportDir, 0775, true);

$md = [];
$md[] = '# تقرير فحص RS_Own — ' . date('Y-m-d H:i:s');
$md[] = '';
$md[] = '- **النتيجة العامة:** ' . $overall . ' (' . $overallAr . ')';
$md[] = '- **الجذر:** `' . $ROOT . '`';
$md[] = '- **PHP:** ' . PHP_VERSION;
$c = hc_cfg();
$md[] = '- **القاعدة:** `' . (string)($c['db']['name'] ?? '—') . '`';
$md[] = '- **الفحوصات:** OK=' . $counts['OK'] . ' | INFO=' . $counts['INFO'] . ' | WARN=' . $counts['WARN'] . ' | FAIL=' . $counts['FAIL'] . ' | CRIT=' . $counts['CRIT'];
$md[] = '';
foreach ($AREAS as $key => $label) {
    $group = array_values(array_filter($checks, fn($x) => $x['area'] === $key));
    if (!$group) continue;
    $md[] = '## ' . $label;
    $md[] = '';
    $md[] = '| الحالة | الفحص | النتيجة |';
    $md[] = '|---|---|---|';
    foreach ($group as $x) {
        $msg = str_replace('|', '\\|', $x['message']);
        $md[] = '| ' . $x['status'] . ' | ' . str_replace('|', '\\|', $x['title']) . ' | ' . $msg . ' |';
        foreach ($x['details'] as $det) $md[] = '|  | ↳ ' . str_replace('|', '\\|', $det) . ' |';
    }
    $md[] = '';
}
$mdText = implode("\n", $md) . "\n";

$jsonPayload = [
    'generated_at' => date('c'),
    'root' => $ROOT,
    'php' => PHP_VERSION,
    'db' => (string)($c['db']['name'] ?? ''),
    'overall' => $overall,
    'summary' => $counts,
    'checks' => $checks,
];
$jsonText = json_encode($jsonPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";

$htmlText = '';
if ($HTML) {
    $color = ['OK' => '#16a34a', 'INFO' => '#0369a1', 'WARN' => '#b45309', 'FAIL' => '#dc2626', 'CRIT' => '#7f1d1d'];
    $h = [];
    $h[] = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>تقرير فحص RS_Own</title>';
    $h[] = '<style>body{font-family:Tahoma,Arial;background:#f8fafc;color:#0f172a;margin:0;padding:20px}main{max-width:1100px;margin:auto}';
    $h[] = 'h1{font-size:22px}table{width:100%;border-collapse:collapse;background:#fff;margin:10px 0 22px}th,td{border:1px solid #e2e8f0;padding:8px;text-align:right;font-size:13px;vertical-align:top}';
    $h[] = 'th{background:#f1f5f9}.b{font-weight:800}.muted{color:#64748b;font-size:12px}ul{margin:4px 0;padding-right:18px}.c{font-weight:800}</style></head><body><main>';
    $h[] = '<h1>تقرير فحص RS_Own — ' . htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') . '</h1>';
    $h[] = '<p class="b">النتيجة العامة: <span class="c" style="color:' . ($color[$overall] ?? '#0f172a') . '">' . htmlspecialchars($overall . ' (' . $overallAr . ')', ENT_QUOTES, 'UTF-8') . '</span></p>';
    $h[] = '<p class="muted">PHP ' . htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') . ' — ' . htmlspecialchars($ROOT, ENT_QUOTES, 'UTF-8') . '</p>';
    $h[] = '<p>OK=' . $counts['OK'] . ' | INFO=' . $counts['INFO'] . ' | WARN=' . $counts['WARN'] . ' | FAIL=' . $counts['FAIL'] . ' | CRIT=' . $counts['CRIT'] . '</p>';
    foreach ($AREAS as $key => $label) {
        $group = array_values(array_filter($checks, fn($x) => $x['area'] === $key));
        if (!$group) continue;
        $h[] = '<h2>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</h2><table><tr><th>الحالة</th><th>الفحص</th><th>النتيجة</th></tr>';
        foreach ($group as $x) {
            $h[] = '<tr><td class="c" style="color:' . ($color[$x['status']] ?? '#0f172a') . '">' . htmlspecialchars($x['status'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars($x['title'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars($x['message'], ENT_QUOTES, 'UTF-8');
            if ($x['details']) {
                $h[] = '<ul>';
                foreach ($x['details'] as $det) $h[] = '<li>' . htmlspecialchars($det, ENT_QUOTES, 'UTF-8') . '</li>';
                $h[] = '</ul>';
            }
            $h[] = '</td></tr>';
        }
        $h[] = '</table>';
    }
    $h[] = '</main></body></html>';
    $htmlText = implode("\n", $h);
}

$written = [];
if (!$NO_REPORT) {
    $files = [
        $reportDir . '/health_' . $stamp . '.md' => $mdText,
        $reportDir . '/health_latest.md' => $mdText,
        $reportDir . '/health_latest.json' => $jsonText,
    ];
    if ($HTML) $files[$reportDir . '/health_' . $stamp . '.html'] = $htmlText;
    if ($HTML) $files[$reportDir . '/health_latest.html'] = $htmlText;
    foreach ($files as $f => $content) {
        if (@file_put_contents($f, $content) !== false) $written[] = $f;
    }
}

if (!$QUIET) {
    foreach ($AREAS as $key => $label) {
        $group = array_values(array_filter($checks, fn($x) => $x['area'] === $key));
        if (!$group) continue;
        echo "\n== " . $label . " ==\n";
        foreach ($group as $x) {
            echo '[' . $x['status'] . '] ' . $x['title'] . ' — ' . $x['message'] . "\n";
            foreach ($x['details'] as $det) echo '    - ' . $det . "\n";
        }
    }
}
echo "\n";
echo 'النتيجة العامة: ' . $overall . ' (' . $overallAr . ')' . "\n";
echo 'OK=' . $counts['OK'] . ' INFO=' . $counts['INFO'] . ' WARN=' . $counts['WARN'] . ' FAIL=' . $counts['FAIL'] . ' CRIT=' . $counts['CRIT'] . "\n";
foreach ($written as $f) echo 'تقرير: ' . $f . "\n";

$exit = $counts['CRIT'] > 0 ? 2 : ($counts['FAIL'] > 0 ? 1 : 0);
exit($exit);
