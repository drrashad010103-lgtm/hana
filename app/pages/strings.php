<?php
// pages/strings.php — عارض النصوص المستخرجة من قلب البرنامج
require_once __DIR__ . '/../lib/layout.php';
$all = file(__DIR__ . '/../../rs_strings16.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$q = trim($_GET['q'] ?? '');
$cats = ['فاتورة' => 'الفواتير', 'بيع' => 'البيع', 'شراء' => 'الشراء', 'مرتجع' => 'المرتجعات', 'جرد' => 'الجرد', 'مخزن' => 'المخازن', 'صنف' => 'الأصناف', 'عميل' => 'العملاء', 'مورد' => 'الموردون', 'خزن' => 'الخزينة', 'حساب' => 'الحسابات', 'تقرير' => 'التقارير', 'طباعة' => 'الطباعة', 'خصم' => 'الخصومات', 'مندوب' => 'المناديب'];
$hits = [];
if ($q !== '') { foreach ($all as $s) if (mb_strpos($s, $q) !== false) $hits[] = $s; }
layout_open('نصوص البرنامج (' . count($all) . ')', '');
echo '<div class="card"><form class="row"><div><label>ابحث في نصوص البرنامج</label><br><input name="q" style="width:260px" value="' . htmlspecialchars($q) . '" placeholder="مثال: فاتورة"></div><div><br><button>بحث</button></div></form>';
echo '<div class="row" style="margin-top:8px">';
foreach ($cats as $k => $t) echo '<a href="?q=' . urlencode($k) . '" style="background:#e2e8f0;border-radius:8px;padding:6px 12px;text-decoration:none;color:#0f172a">' . $t . '</a>';
echo '</div></div>';
if ($q !== '') {
  echo '<div class="card"><b>' . count($hits) . '</b> نتيجة لـ "' . htmlspecialchars($q) . '":<table>';
  foreach (array_slice($hits, 0, 200) as $s) echo '<tr><td>' . htmlspecialchars($s) . '</td></tr>';
  echo '</table></div>';
}
layout_close();
