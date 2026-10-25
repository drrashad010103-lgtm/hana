<?php
// pages/vouchershop.php — قسائم المشتريات (Gift Vouchers)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/shop.php';
$db = db();
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $r = voucher_save([
    'name' => $_POST['name'] ?? '', 'code' => $_POST['code'] ?? '',
    'value' => $_POST['val'] ?? 0, 'date_create' => $_POST['dc'] ?? '',
    'date_end' => $_POST['de'] ?? '', 'vaild' => !empty($_POST['vaild']),
  ], (int)($_POST['id'] ?? 0));
  $msg = !empty($r['ok']) ? '✅ اتحفظ' : '❌ ' . ($r['error'] ?? 'خطأ');
}
if (($_POST['del'] ?? '') !== '') { voucher_delete((int)$_POST['id']); $msg = '🗑️ اتمسح'; }
$rows = $db->query("SELECT * FROM voucher WHERE is_deleted=0 OR is_deleted IS NULL ORDER BY id DESC LIMIT 100")->fetchAll();
$cur = isset($_GET['id']) ? $db->query("SELECT * FROM voucher WHERE id=" . (int)$_GET['id'])->fetch() : null;
layout_open('قسائم المشتريات (Gift Vouchers)', '');
echo '<div class="card"><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">'
  . '<div><label>الرقم</label><br><input value="' . ($cur['id'] ?? 'جديد') . '" readonly style="width:70px"></div>'
  . '<div><label>اسم القسيمة</label><br><input name="name" value="' . e($cur['name'] ?? '') . '" style="width:200px"></div>'
  . '<div><label>الكود</label><br><input name="code" value="' . e($cur['code'] ?? '') . '" style="width:120px"></div>'
  . '<div><label>القيمة (ج)</label><br><input name="val" type="number" step="1" value="' . e($cur['value'] ?? 0) . '" style="width:100px"></div>'
  . '<div><label>تاريخ الإصدار</label><br><input name="dc" type="datetime-local" value="' . e(($cur['date_create'] ?? '') ? date('Y-m-d\TH:i', strtotime($cur['date_create'])) : date('Y-m-d\TH:i')) . '"></div>'
  . '<div><label>تاريخ الانتهاء</label><br><input name="de" type="datetime-local" value="' . e(($cur['date_end'] ?? '') ? date('Y-m-d\TH:i', strtotime($cur['date_end'])) : '') . '"></div>'
  . '<div><label><input type="checkbox" name="vaild" value="1" ' . (!isset($cur) || !empty($cur['vaild']) ? 'checked' : '') . '> صالحة</label></div>'
  . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
echo '<div class="card"><table><tr><th>الكود</th><th>الاسم</th><th>القيمة</th><th>تاريخ الإصدار</th><th>تاريخ الانتهاء</th><th>مرات الاستخدام</th><th>صالحة</th><th></th></tr>';
foreach ($rows as $r) {
  $expired = $r['date_end'] && strtotime($r['date_end']) < time();
  echo '<tr><td><b>' . e($r['code']) . '</b></td><td>' . e($r['name']) . '</td><td>' . number_format($r['value'] ?? 0) . '</td>'
    . '<td>' . e($r['date_create'] ?? '') . '</td><td>' . e($r['date_end'] ?? '') . '</td><td>' . (int)($r['used_count'] ?? 0) . '</td>'
    . '<td>' . (!empty($r['vaild']) && !$expired ? '✅' : ($expired ? '⏰' : '—')) . '</td>'
    . '<td><a href="?id=' . $r['id'] . '">فتح</a> <form method="post" style="display:inline" onsubmit="return confirm(\'حذف القسيمة؟\')"><input type="hidden" name="id" value="' . $r['id'] . '"><button name="del" value="1">🗑️</button></form></td></tr>';
}
echo '</table><div class="note">💾 القسيمة = مبلغ ثابت يُخصم من الفاتورة (مش بالنسبة المئوية). تقدر تحدد تاريخ انتهاء فترجع غير صالفة تلقائياً.</div></div>';
layout_close();
