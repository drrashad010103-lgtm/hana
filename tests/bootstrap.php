<?php
// tests/bootstrap.php — تحميل مكتبات app بدون تشغيل guard (CLI تخطّاه أصلاً)
error_reporting(E_ALL);
require_once __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/cfg.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/seq.php';
