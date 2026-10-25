<?php
// pages/branches.php — تعريف الفروع (إضافة/تعديل/حذف/تنقل) طبق الأصل
require __DIR__ . '/../lib/db.php';
$db = db();
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $id = (int)($_POST['id'] ?? 0);
  $f = [mb_substr(trim($_POST['nm'] ?? ''), 0, 128), mb_substr(trim($_POST['tel'] ?? ''), 0, 32), mb_substr(trim($_POST['mob'] ?? ''), 0, 32), mb_substr(trim($_POST['fax'] ?? ''), 0, 32), mb_substr(trim($_POST['mail'] ?? ''), 0, 128), mb_substr(trim($_POST['addr'] ?? ''), 0, 255), mb_substr(trim($_POST['notes'] ?? ''), 0, 255), !empty($_POST['main']) ? 1 : 0];
  if ($id > 0) {
    $db->prepare("UPDATE branches SET name=?, Tel=?, Mobile=?, Fax=?, Email=?, Address=?, notes=?, is_main=? WHERE id=?")->execute([...$f, $id]);
    $msg = '✅ اتعدل';
  } else {
    $mx = seq_next_table('branches');
    $db->prepare("INSERT INTO branches (id, name, Tel, Mobile, Fax, Email, Address, notes, is_main) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$mx, ...$f]);
    $msg = '✅ اتضاف فرع ' . $mx;
  }
  if (!empty($_POST['main'])) $db->exec("UPDATE branches SET is_main=0 WHERE id<>$mx AND $id=0");
}
if (($_POST['del'] ?? '') !== '' && (int)$_POST['id'] > 0) {
  $db->prepare("UPDATE branches SET IS_Deleted=1 WHERE id=?")->execute([(int)$_POST['id']]);
  $msg = '🗑️ اتحذف';
}
$rows = $db->query("SELECT * FROM branches WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY id")->fetchAll();
$cur = null;
if (isset($_GET['id'])) foreach ($rows as $r) if ($r['id'] == (int)$_GET['id']) $cur = $r;
if (!$cur && $rows) $cur = $rows[0];
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>تعريف الفروع</title><link rel="stylesheet" href="../assets/app.css"></head>
<body><aside style="display:none"></aside><main style="max-width:none">
<h2>تعريف الفروع — ادخل بيانات الفروع</h2>
<div class="card"><form method="post"><input type="hidden" name="id" value="<?= $cur ? $cur['id'] : 0 ?>">
<div class="row">
<div><label>اسم الفرع</label><br><input name="nm" style="width:300px" value="<?= e($cur['name'] ?? '') ?>"></div>
<div><label>تليفون</label><br><input name="tel" value="<?= e($cur['Tel'] ?? '') ?>"></div>
<div><label>موبايل</label><br><input name="mob" value="<?= e($cur['Mobile'] ?? '') ?>"></div>
<div><label>فاكس</label><br><input name="fax" value="<?= e($cur['Fax'] ?? '') ?>"></div>
<div><label>البريد الإلكتروني</label><br><input name="mail" value="<?= e($cur['Email'] ?? '') ?>"></div>
</div><div class="row" style="margin-top:8px">
<div><label>العنوان</label><br><input name="addr" style="width:300px" value="<?= e($cur['Address'] ?? '') ?>"></div>
<div><label>ملاحظات</label><br><input name="notes" style="width:300px" value="<?= e($cur['notes'] ?? '') ?>"></div>
<div><label><input type="checkbox" name="main" value="1" <?= !empty($cur['is_main']) ? 'checked' : '' ?>> الفرع رئيسية (يظهر في التقارير)</label></div>
<div><br><span><?= $msg ?></span></div>
</div></form></div>
<div class="card"><h3>بيانات الفروع</h3><table><tr><th>اسم الفرع</th><th>تليفون</th><th>موبايل</th><th>فاكس</th><th>بريد إلكتروني</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr<?= $cur && $r['id'] == $cur['id'] ? ' style="background:#ccfbf1"' : '' ?>><td><b><?= e($r['name']) ?></b><?= !empty($r['is_main']) ? ' ⭐' : '' ?></td><td><?= e($r['Tel'] ?? '') ?></td><td><?= e($r['Mobile'] ?? '') ?></td><td><?= e($r['Fax'] ?? '') ?></td><td><?= e($r['Email'] ?? '') ?></td><td><a href="?id=<?= $r['id'] ?>">فتح</a></td></tr><?php endforeach; ?>
</table></div>
<div class="card"><div class="row">
<form method="post" style="display:inline"><input type="hidden" name="id" value="<?= $cur ? $cur['id'] : 0 ?>"><button name="save" value="1">💾 حفظ</button></form>
<form method="post" style="display:inline" onsubmit="return confirm('حذف الفرع؟')"><input type="hidden" name="id" value="<?= $cur ? $cur['id'] : 0 ?>"><button class="danger" name="del" value="1">🗑️ حذف</button></form>
<a href="branches.php"><button class="ghost">➕ جديد</button></a>
<button class="ghost" onclick="window.print()">🖨️ طباعة</button>
</div></div>
</main></body></html>
