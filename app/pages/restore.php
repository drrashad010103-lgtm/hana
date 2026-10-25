<?php
// pages/restore.php — استرجاع نسخة احتياطية إلى قاعدة + فحص مطابقة العدد (تجربة الاسترجاع)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$BK_DIR = dirname(__DIR__, 2) . '/backups';
$MYSQL = 'C:\\Program Files\\MariaDB 13.0\\bin\\mysql.exe';
$op = $_POST['op'] ?? '';
$msg = '';
$cmp = null;
$extraTbl = [];
$backs = [];
foreach ((glob($BK_DIR . DIRECTORY_SEPARATOR . 'rs_own_*.sql') ?: []) as $b) $backs[] = basename($b);
rsort($backs);

function rstr_valid_name(string $n): bool { return (bool)preg_match('/^[A-Za-z0-9_]{1,64}$/', $n); }
function rstr_is_temp(string $n): bool { return (bool)preg_match('/^rs_own_restore_test\d*$/', $n); }
function rstr_compare(PDO $db, string $target): array {
  $live = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
  $dst = $db->query("SHOW TABLES FROM `$target`")->fetchAll(PDO::FETCH_COLUMN);
  $rows = [];
  foreach ($live as $t) {
    $a = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $b = in_array($t, $dst, true) ? (int)$db->query("SELECT COUNT(*) FROM `$target`.`$t`")->fetchColumn() : -1;
    $rows[] = ['t' => $t, 'a' => $a, 'b' => $b];
  }
  return ['rows' => $rows, 'extra' => array_values(array_diff($dst, $live))];
}

if ($op === 'restore') {
  $target = trim($_POST['target'] ?? '');
  $file = basename(trim($_POST['file'] ?? ''));
  set_time_limit(300);
  if (!rstr_valid_name($target)) $msg = '❌ اسم قاعدة غير صالح (حروف وأرقام فقط)';
  elseif ($target !== 'rs_own' && !rstr_is_temp($target)) $msg = '❌ الهدف لازم يكون rs_own (بتأكيد) أو قاعدة مؤقتة rs_own_restore_test — غير ذلك ممنوع';
  elseif (!preg_match('/^rs_own_\d{8}_\d{6}\.sql$/', $file) || !is_file($BK_DIR . DIRECTORY_SEPARATOR . $file)) $msg = '❌ ملف نسخة غير صالح';
  elseif ($target === 'rs_own' && (($_POST['sure'] ?? '') !== 'rs_own')) $msg = '❌ لاستبدال rs_own لازم تكتب rs_own في خانة التأكيد';
  elseif (!is_file($MYSQL)) $msg = '❌ مفيش mysql.exe في ' . $MYSQL;
  else {
    if ($target !== 'rs_own') {
      $db->exec("DROP DATABASE IF EXISTS `$target`");
      $db->exec("CREATE DATABASE `$target` CHARACTER SET utf8mb4");
    }
    $o = []; $rc = 0;
    exec('"' . $MYSQL . '" -u root --default-character-set=utf8mb4 ' . $target . ' < "' . $BK_DIR . DIRECTORY_SEPARATOR . $file . '" 2>&1', $o, $rc);
    if ($rc !== 0) {
      $msg = '❌ فشل الاسترجاع: ' . e(implode(' | ', array_slice($o, 0, 3)));
    } else {
      $res = rstr_compare($db, $target);
      $cmp = $res['rows']; $extraTbl = $res['extra'];
      $bad = array_filter($cmp, fn($r) => $r['b'] < 0 || $r['a'] !== $r['b']);
      $msg = $bad
        ? '⚠️ استُرجعت لكن في ' . count($bad) . ' جدول عدد الصفوف مختلف (مقارنة أعداد فقط) — راجع الجدول تحت'
        : '✅ تم الاسترجاع في `' . $target . '` — عدد الصفوف مطابق في ' . count($cmp) . ' جدول (' . $file . ')';
      if ($extraTbl) $msg .= ' — وفي الوجهة ' . count($extraTbl) . ' جدول زائد غير موجود في النسخة الحية';
    }
  }
}
if ($op === 'dropdb') {
  $target = trim($_POST['target'] ?? '');
  if ($target === 'rs_own') $msg = '❌ ممنوع مسح rs_own من هنا';
  elseif (!rstr_valid_name($target)) $msg = '❌ اسم قاعدة غير صالح';
  elseif (!rstr_is_temp($target)) $msg = '❌ المسح مسموح فقط لقواعد مؤقتة rs_own_restore_test — غير ذلك ممنوع';
  else { $db->exec("DROP DATABASE IF EXISTS `$target`"); $msg = '🗑️ تم مسح القاعدة ' . $target; }
}

layout_open('استرجاع نسخة', '');
echo '<div class="card"><h3>📥 استرجاع نسخة احتياطية</h3>';
if ($msg) echo '<div class="note" style="padding:10px;border-radius:8px;background:' . (str_starts_with($msg, '✅') || str_starts_with($msg, '🗑️') ? '#dcfce7' : (str_starts_with($msg, '⚠️') ? '#fef9c3' : '#fee2e2')) . '">' . $msg . '</div>';
if (!$backs) echo '<div class="note">مافيش ملفات نسخ في مجلد backups/ — اعمل نسخة الأول من <a href="snapshot.php">النسخ الاحتياطي</a>.</div>';
else {
  echo '<form method="post" class="row" style="align-items:flex-end">'
    . '<input type="hidden" name="op" value="restore">'
    . '<div><label>ملف النسخة</label><br><select name="file" style="min-width:260px">' . implode('', array_map(fn($f) => '<option>' . e($f) . '</option>', $backs)) . '</select></div>'
    . '<div><label>القاعدة المستقبِلة</label><br><input name="target" value="rs_own_restore_test" style="width:190px"></div>'
    . '<div><label>تأكيد rs_own (لو بدل rs_own)</label><br><input name="sure" placeholder="rs_own" style="width:120px"></div>'
    . '<div><br><button style="background:#2563eb;color:#fff;padding:10px 22px" onclick="return confirm(\'استرجاع يمسح/يعيد بناء جداول القاعدة المختارة — متأكد؟\')">▶ استرجاع</button></div>'
    . '</form>';
}
echo '<div class="note">الاسترجاع لقاعدة مؤقتة (افتراضي rs_own_restore_test) يخلّيك تفحص البيانات قبل أي استبدال. لاستبدال rs_own نفسها: اكتب rs_own في خانة التأكيد — والملف فيه DROP TABLE لكل جدول.</div></div>';

if ($cmp) {
  $badN = count(array_filter($cmp, fn($r) => $r['b'] < 0 || $r['a'] !== $r['b']));
  echo '<div class="card"><h3>🔍 فحص مطابقة الأعداد' . ($badN ? ' — <span style="color:#dc2626">' . $badN . ' اختلاف</span>' : ' — <span style="color:#16a34a">الأعداد متطابقة</span>') . '</h3>'
    . '<div class="note">مقارنة عدد الصفوف فقط — لا تقارن محتوى الصفوف؛ واختلاف محتوى قد يبقى مخفياً حتى مع تطابق الأعداد.</div>'
    . '<table class="grid" style="width:100%"><tr><th>الجدول</th><th>النسخة الحية</th><th>المسترجَع</th><th></th></tr>';
  foreach ($cmp as $r) {
    $ok = $r['b'] === $r['a'];
    echo '<tr><td style="text-align:left;direction:ltr">' . e($r['t']) . '</td><td>' . number_format($r['a']) . '</td><td>' . ($r['b'] < 0 ? '—' : number_format($r['b'])) . '</td><td>' . ($ok ? '✅' : '❌') . '</td></tr>';
  }
  echo '</table>';
  if (!empty($extraTbl)) {
    echo '<div class="note" style="margin-top:8px;background:#fef9c3">⚠️ جداول زائدة في الوجهة غير موجودة في النسخة الحية (لم تُقارن): <b style="direction:ltr;display:inline-block">' . e(implode('، ', array_slice($extraTbl, 0, 15))) . (count($extraTbl) > 15 ? ' …' : '') . '</b></div>';
  }
  echo '</div>';
}
echo '<div class="card"><form method="post" class="row" style="align-items:flex-end"><input type="hidden" name="op" value="dropdb">'
  . '<div><label>مسح قاعدة مؤقتة</label><br><input name="target" value="rs_own_restore_test" style="width:190px"></div>'
  . '<div><br><button style="background:#dc2626;color:#fff;padding:10px 22px">🗑️ مسح</button></div></form>'
  . '<div class="note">بعد التجربة مسح القاعدة المؤقتة عشان ما تستهلكش مساحة.</div></div>';
layout_close();
