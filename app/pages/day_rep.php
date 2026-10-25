<?php
// pages/day_rep.php + shift_rep.php — تقرير اليوميات / الورديات
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$which = basename($_SERVER['SCRIPT_NAME'], '.php');
$isDay = $which === 'day_rep';
$f = dt($_GET['from'] ?? '', '2026-10-15');
$t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
$tbl = $isDay ? 'days' : 'wardya';
$ncol = $isDay ? 'day_no' : 'wardya_no';
$rows = $db->query("SELECT * FROM `$tbl` WHERE DATE(date_open)>='$f' AND DATE(date_open)<='$t2' ORDER BY date_open DESC LIMIT 200")->fetchAll();
$tt = 0;
foreach ($rows as $r) $tt += (float)($r['net'] ?? 0);
layout_open($isDay ? 'تقرير اليوميات' : 'تقرير الورديات', '');
echo '<div class="card"><form class="row"><div><label>من التاريخ</label><br><input type="date" name="from" value="' . e($f) . '"></div><div><label>إلى التاريخ</label><br><input type="date" name="to" value="' . e($t2) . '"></div><div><br><button>عرض</button> <button type="button" onclick="window.print()">طباعة</button></div></form>'
  . '<table><tr><th>الفرع</th><th>ID</th><th>رقم ' . ($isDay ? 'اليوم' : 'الوردية') . '</th><th>تاريخ فتح</th><th>تاريخ الإغفال</th><th>فتح بواسطة</th><th>إغفال بواسطة</th><th>صافي ' . ($isDay ? 'اليوم' : 'الوردية') . '</th></tr>';
foreach ($rows as $r) echo '<tr><td>' . ($r['branch'] ?? '') . '</td><td>' . $r['id'] . '</td><td>' . ($r[$ncol] ?? '') . '</td><td>' . e($r['date_open'] ?? '') . '</td><td>' . e($r['date_close'] ?? 'مفتوح') . '</td><td>' . ($r['user_open'] ?? '') . '</td><td>' . ($r['user_close'] ?? '') . '</td><td><b>' . number_format($r['net'] ?? 0) . '</b></td></tr>';
echo '</table><div class="note">صافي ' . ($isDay ? 'اليوميات' : 'الورديات') . ': <b>' . number_format($tt) . '</b></div></div>';
layout_close();
