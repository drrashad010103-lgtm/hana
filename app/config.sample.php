<?php
// انسخ هذا الملف إلى config.php واملأ بيانات إنفينيتي (cPanel ← MySQL + phpMyAdmin)
// النسخة الحرة — لا تلمس RS_RS إطلاقاً، قاعدة rs_own فقط
return [
  'db' => [
    'host' => 'sqlXXX.infinityfree.com', // هوست القاعدة من لوحة إنفينيتي
    'name' => 'if0_XXXX_rsown',          // اسم القاعدة
    'user' => 'if0_XXXX',                // يوزر القاعدة
    'pass' => 'CHANGE_ME',               // باسورد القاعدة
    'charset' => 'utf8mb4',
  ],
  'admin_user' => 'admin',
];
