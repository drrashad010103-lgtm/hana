<?php
// pages/hr_emp.php — تعريف موظف (محرر كامل + بحث)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
$F = ['name', 'manag', 'dep', 'state', 'job', 'branch', 'birth_date', 'insurance_no', 'work_date', 'marital_state', 'nationality', 'sex', 'tel', 'mobile', 'email', 'address', 'notes', 'salary_basic', 'salary_add', 'salary_other', 'house', 'food', 'travel', 'medical'];
if (($_POST['save'] ?? '') !== '') {
  $id = (int)($_POST['id'] ?? 0);
  $v = [];
  foreach ($F as $c) {
    $s = trim($_POST['f_' . $c] ?? '');
    $v[] = (in_array($c, ['birth_date', 'work_date'], true) && $s === '') ? null : mb_substr($s, 0, 255);
  }
  if ($id > 0) {
    $db->prepare("UPDATE employees SET " . implode(',', array_map(fn($c) => "`$c`=?", $F)) . " WHERE id=?")->execute([...$v, $id]);
    $msg = '✅ اتعدل';
  } elseif ($v[0] !== '') {
    $mx = seq_next_table('employees');
    $db->prepare("INSERT INTO employees (id," . implode(',', array_map(fn($c) => "`$c`", $F)) . ",`IS_Deleted`) VALUES ($mx," . implode(',', array_fill(0, count($F), '?')) . ",0)")->execute($v);
    $msg = '✅ اتضاف (' . $mx . ')';
    $_GET['id'] = $mx;
  }
}
$id = (int)($_GET['id'] ?? 0);
$q = trim($_GET['q'] ?? '');
$tab = $_GET['tab'] ?? 'data';
$deps = $db->query("SELECT id, name FROM departments ORDER BY id")->fetchAll();
$cur = $id > 0 ? $db->query("SELECT * FROM employees WHERE id=$id")->fetch() : null;
$L = ['name' => 'اسم الموظف', 'manag' => 'الادارة', 'dep' => 'القسم', 'state' => 'الحالة', 'job' => 'الوظيفة', 'branch' => 'الفرع', 'birth_date' => 'تاريخ الميلاد', 'insurance_no' => 'رقم التأمينات', 'work_date' => 'تاريخ التعيين', 'marital_state' => 'الحالة الاجتماعية', 'nationality' => 'الجنسية', 'sex' => 'النوع', 'tel' => 'رقم الهاتف', 'mobile' => 'رقم الموبايل', 'email' => 'البريد الإلكتروني', 'address' => 'العنوان', 'notes' => 'ملاحظات', 'salary_basic' => 'أساسي الراتب', 'salary_add' => 'سكن', 'salary_other' => 'إضافي', 'house' => 'بدل سكن', 'food' => 'طعام', 'travel' => 'سفر', 'medical' => 'طبي'];
layout_open('تعريف موظف', '');
echo '<div class="card"><a href="?tab=data' . ($id ? '&id=' . $id : '') . '">بيانات الموظفين</a> | <a href="?tab=search">البحث</a></div>';
if ($tab === 'search') {
  $rows = $q !== '' ? $db->query("SELECT id, name, job, mobile FROM employees WHERE name LIKE " . $db->quote("%$q%") . " ORDER BY id LIMIT 100")->fetchAll() : [];
  echo '<div class="card"><form class="row"><input type="hidden" name="tab" value="search"><div><label>بحث بالاسم</label><br><input name="q" style="width:240px" value="' . e($q) . '"></div><div><br><button>بحث</button></div></form>';
  foreach ($rows as $r) echo '<div><a href="?id=' . $r['id'] . '">' . $r['id'] . ' - ' . e($r['name']) . ' (' . e($r['job'] ?? '') . ')</a></div>';
  echo '</div>';
} else {
  echo '<div class="card"><h3>بيانات الموظف الأساسية' . ($cur ? ' — ' . e($cur['name']) : ' (جديد)') . '</h3><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">';
  foreach ($F as $c) {
    $val = $cur[$c] ?? '';
    if ($c === 'manag' || $c === 'dep') {
      echo '<div><label>' . $L[$c] . '</label><br><select name="f_' . $c . '"><option value="0">—</option>';
      foreach ($deps as $d) echo '<option value="' . $d['id'] . '"' . ($val == $d['id'] ? ' selected' : '') . '>' . e($d['name']) . '</option>';
      echo '</select></div>';
    } elseif ($c === 'sex') echo '<div><label>النوع</label><br><select name="f_sex"><option value="ذكر"' . ($val === 'ذكر' ? ' selected' : '') . '>ذكر</option><option value="أنثى"' . ($val === 'أنثى' ? ' selected' : '') . '>أنثى</option></select></div>';
    else echo '<div><label>' . $L[$c] . '</label><br><input name="f_' . $c . '" value="' . e($val) . '"' . (str_contains($c, 'date') ? ' type="date"' : '') . '></div>';
  }
  echo '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
}
layout_close();
