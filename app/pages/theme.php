<?php
// pages/theme.php — إعدادات المستخدم للشاشات (خط/لون/خلفية)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  foreach (['ui_font', 'ui_size', 'ui_bold', 'ui_fg', 'ui_bg', 'ui_on'] as $k) {
    $v = mb_substr(trim($_POST[$k] ?? ''), 0, 32);
    $db->prepare("INSERT INTO app_settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$k, $v]);
  }
  $msg = '✅ اتحفظت — حدّث أي صفحة لتطبيقها';
}
$g = function ($k, $d = '') use ($db) {
  $st = $db->prepare("SELECT v FROM app_settings WHERE k=?");
  $st->execute([$k]);
  $r = $st->fetch();
  return e($r ? $r['v'] : $d);
};
layout_open('إعدادات الشاشات', '');
echo '<div class="card"><h3>اختر إعدادات الخط واللون</h3><form method="post"><div class="row">'
   . '<div><label>نوع الخط</label><br><select name="ui_font"><option' . ($g('ui_font', 'Cairo') === 'Cairo' ? ' selected' : '') . '>Cairo</option><option' . ($g('ui_font') === 'Tahoma' ? ' selected' : '') . '>Tahoma</option><option' . ($g('ui_font') === 'Arial' ? ' selected' : '') . '>Arial</option></select></div>'
  . '<div><label>حجم الخط</label><br><input name="ui_size" type="number" value="' . $g('ui_size', '14') . '" style="width:70px"></div>'
  . '<div><label>نمط الخط</label><br><label><input type="radio" name="ui_bold" value="0"' . ($g('ui_bold', '0') === '0' ? ' checked' : '') . '> عادي</label> <label><input type="radio" name="ui_bold" value="1"' . ($g('ui_bold') === '1' ? ' checked' : '') . '> عريض</label></div>'
  . '<div><label>لون الخط</label><br><input name="ui_fg" type="color" value="' . $g('ui_fg', '#0f172a') . '"></div>'
  . '<div><label>خلفية الشاشة</label><br><input name="ui_bg" type="color" value="' . $g('ui_bg', '#f1f5f9') . '"></div>'
  . '<div><label><input type="checkbox" name="ui_on" value="1"' . ($g('ui_on') ? ' checked' : '') . '> تفعيل الإعدادات</label></div>'
  . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form>'
  . '<div class="note">تجربة الإعداد على الشاشة: <span style="font-family:' . $g('ui_font', 'Tahoma') . ';font-size:' . $g('ui_size', '14') . 'px;font-weight:' . ($g('ui_bold') === '1' ? 'bold' : 'normal') . ';color:' . $g('ui_fg', '#0f172a') . '">فاتورة مبيعات 123 — ' . e(cfg_shop_name()) . ' 🌿</span></div></div>';
layout_close();
