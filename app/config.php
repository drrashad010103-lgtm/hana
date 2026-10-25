<?php
// النسخة الحرة — الإعداد (يقرأ/يكتب rs_own فقط، لا يلمس RS_RS إطلاقاً)
// الأسرار خارج المشروع كلياً — ملف واحد في ProgramData (ACL: المستخدم + SYSTEM + Administrators)
// غيابه أو نقصه = توقف واضح بالخطأ، لا صمت ولا عودة لمسارات داخل المشروع
$sec = @include 'C:\\ProgramData\\RS_Own\\secrets.php';
if (!is_array($sec) || ($sec['db_pass'] ?? '') === '' || ($sec['easyorders_key'] ?? '') === '') {
  if (PHP_SAPI === 'cli') fwrite(STDERR, "إعداد الأسرار مفقود أو ناقص — C:\\ProgramData\\RS_Own\\secrets.php\n");
  else { http_response_code(500); header('Content-Type: text/plain; charset=utf-8'); }
  exit('إعداد الأسرار مفقود أو ناقص — راجع C:\\ProgramData\\RS_Own\\secrets.php');
}
// اسم القاعدة: db.name أولاً (تبديل لوحة التحكم)، ثم RS_DB يتغلب — بيئة الاختبار أولاً
// حتى لا يحول ملف db.name حزمة الاختبارات إلى قاعدة حقيقية
$dbn = 'rs_own';
$dbo = trim((string)@file_get_contents(__DIR__ . '/../db.name'));
if ($dbo !== '' && preg_match('/^rs_[A-Za-z0-9_]{1,40}$/', $dbo)) $dbn = $dbo;
$dbnTest = getenv('RS_DB');
if (is_string($dbnTest) && preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbnTest)) $dbn = $dbnTest;
return [
  'db' => ['host' => '127.0.0.1', 'name' => $dbn, 'user' => (string)$sec['db_user'], 'pass' => (string)$sec['db_pass'], 'charset' => 'utf8mb4'],
  'admin_user' => 'admin',
  'admin_pass_hash' => '', // يُضبط من صفحة الإعداد
  'easyorders' => ['key' => (string)($sec['easyorders_key'] ?? '')],
];
