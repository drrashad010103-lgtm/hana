<?php
// pages/dash.php — الداشبورد الحية مضمنة في النسخة الحرة
require_once __DIR__ . '/../lib/layout.php';
layout_open('داشبورد المحل', '');
echo '<div class="card"><div class="note">داشبورد المحل الحية — نفس الأرقام والتقارير (تفتح داخل النسخة). لو لم تظهر: <a href="http://127.0.0.1:8765/" target="_blank">افتحها في تبويب جديد</a></div>'
  . '<iframe src="http://127.0.0.1:8765/?hide=esale,epurch,gard" style="width:100%;height:calc(100vh - 160px);border:1px solid #cbd5e1;border-radius:10px;background:#fff"></iframe></div>';
layout_close();
