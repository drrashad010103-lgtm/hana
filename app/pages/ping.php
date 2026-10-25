<?php
// نبض الإبقاء على الجلسة نشطة (يستدعى من main.php كل شوية حتى لا تنقطع الجلسة)
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
echo 'ok';
