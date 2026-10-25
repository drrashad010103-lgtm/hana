<?php
// pages/data_basic.php — البيانات الأساسية (نفس أيقونات البرنامج)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$icons = [
  ['🏢', 'بيانات الشركة', 'company.php'], ['🌿', 'تعريف الفروع', 'branches.php'],
  ['📒', 'قيود افتتاحية', 'schema.php?t=restrictions'], ['🗄️', 'تعريف الخزن', 'defs.php?t=safes'],
  ['📦', 'تعريف المخازن', 'defs.php?t=stocks'], ['🏦', 'تعريف البنوك', 'defs.php?t=banks'],
  ['💸', 'تعريف مصروفات', 'defs.php?t=accounts_index'], ['📂', 'مجموعات رئيسية', 'defs.php?t=groups'],
  ['🗂️', 'مجموعات الأصناف', 'defs.php?t=groups'], ['🏷️', 'تعريف الأصناف', 'items.php'],
  ['📏', 'الوحدات', 'defs.php?t=units'], ['🗺️', 'بيانات المناطق', 'defs.php?t=cities'],
];
layout_open('البيانات الأساسية', '');
echo '<div class="card"><div class="row">';
foreach ($icons as $c) echo '<a href="' . $c[2] . '" style="text-align:center;text-decoration:none;color:#0f172a;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 10px;width:130px"><div style="font-size:34px">' . $c[0] . '</div><div style="font-size:13px;font-weight:700">' . $c[1] . '</div></a>';
echo '</div></div>';
layout_close();
