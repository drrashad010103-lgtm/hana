<?php
// pages/hr_sales.php — تعريف بائعين/دليفري (mode=seller|delivery) + بحث
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$mode = ($_GET['mode'] ?? 'seller') === 'delivery' ? 'delivery' : 'seller';
$msg = '';
if ($mode === 'delivery') {
  if (($_POST['save'] ?? '') !== '') {
    $id = (int)($_POST['id'] ?? 0);
    $nm = mb_substr(trim($_POST['nm'] ?? ''), 0, 128);
    if ($id > 0) $db->prepare("UPDATE delivery_emps SET name=?, tel=?, address=? WHERE id=?")->execute([$nm, mb_substr(trim($_POST['tel'] ?? ''), 0, 32), mb_substr(trim($_POST['addr'] ?? ''), 0, 255), $id]);
    elseif ($nm !== '') { $db->prepare("INSERT INTO delivery_emps (name, tel, address) VALUES (?,?,?)")->execute([$nm, mb_substr(trim($_POST['tel'] ?? ''), 0, 32), mb_substr(trim($_POST['addr'] ?? ''), 0, 255)]); $msg = '✅ اتضاف'; }
    else $msg = '❌ اكتب الاسم';
  }
  $id = (int)($_GET['id'] ?? 0);
  $cur = $id > 0 ? $db->query("SELECT * FROM delivery_emps WHERE id=$id")->fetch() : null;
  $rows = $db->query("SELECT id, name, tel FROM delivery_emps WHERE is_deleted=0 OR is_deleted IS NULL ORDER BY id")->fetchAll();
  layout_open('تعريف دليفري', '');
  echo '<div class="card"><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">'
    . '<div><label>دليفري موظف <input type="radio" checked></label></div>'
    . '<div><label>اسم الدليفري</label><br><input name="nm" style="width:220px" value="' . e($cur['name'] ?? '') . '"></div>'
    . '<div><label>رقم الهاتف</label><br><input name="tel" value="' . e($cur['tel'] ?? '') . '"></div>'
    . '<div><label>ملاحظات (العنوان)</label><br><input name="addr" style="width:220px" value="' . e($cur['address'] ?? '') . '"></div>'
    . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
  echo '<div class="card"><table><tr><th>م</th><th>الاسم</th><th>الهاتف</th></tr>';
  foreach ($rows as $r) echo '<tr><td>' . $r['id'] . '</td><td><a href="?mode=delivery&id=' . $r['id'] . '">' . e($r['name']) . '</a></td><td>' . e($r['tel'] ?? '') . '</td></tr>';
  echo '</table></div>';
} else {
  if (($_POST['save'] ?? '') !== '') {
    $id = (int)($_POST['id'] ?? 0);
    $f = [mb_substr(trim($_POST['nm'] ?? ''), 0, 128), (float)($_POST['comm'] ?? 0), mb_substr(trim($_POST['mail'] ?? ''), 0, 128), mb_substr(trim($_POST['tel'] ?? ''), 0, 32), mb_substr(trim($_POST['mob'] ?? ''), 0, 32), mb_substr(trim($_POST['notes'] ?? ''), 0, 255), !empty($_POST['isemp']) ? 1 : 0];
    if ($id > 0) { $db->prepare("UPDATE salesmen SET name=?, comm=?, email=?, tel=?, mobile=?, notes=?, IS_EMP=? WHERE id=?")->execute([...$f, $id]); $msg = '✅ اتعدل'; }
    elseif ($f[0] !== '') { $mx = seq_next_table('salesmen'); $db->prepare("INSERT INTO salesmen (id, name, comm, email, tel, mobile, notes, IS_EMP, IS_Deleted) VALUES (?,?,?,?,?,?,?,?,0)")->execute([$mx, ...$f]); $msg = '✅ اتضاف'; }
  }
  $id = (int)($_GET['id'] ?? 0);
  $q = trim($_GET['q'] ?? '');
  $tab = $_GET['tab'] ?? 'data';
  $cur = $id > 0 ? $db->query("SELECT * FROM salesmen WHERE id=$id")->fetch() : null;
  layout_open('تعريف بائعين', '');
  echo '<div class="card"><a href="?tab=data' . ($id ? '&id=' . $id : '') . '">بيانات البائعين</a> | <a href="?tab=search">البحث</a></div>';
  if ($tab === 'search') {
    $rows = $q !== '' ? $db->query("SELECT id, name, comm FROM salesmen WHERE name LIKE " . $db->quote("%$q%") . " ORDER BY id LIMIT 50")->fetchAll() : [];
    echo '<div class="card"><form class="row"><input type="hidden" name="tab" value="search"><div><label>بحث</label><br><input name="q" value="' . e($q) . '" style="width:220px"></div><div><br><button>بحث</button></div></form>';
    foreach ($rows as $r) echo '<div><a href="?id=' . $r['id'] . '">' . $r['id'] . ' - ' . e($r['name']) . ' (' . $r['comm'] . '%)</a></div>';
    echo '</div>';
  } else {
    echo '<div class="card"><form method="post"><input type="hidden" name="id" value="' . ($cur['id'] ?? 0) . '"><div class="row">'
      . '<div><label>بائع بالعمولة <input type="radio" name="isemp" value="0" ' . (empty($cur['IS_EMP']) ? 'checked' : '') . '></label> <label>بائع موظف <input type="radio" name="isemp" value="1" ' . (!empty($cur['IS_EMP']) ? 'checked' : '') . '></label></div>'
      . '<div><label>اسم البائع</label><br><input name="nm" style="width:220px" value="' . e($cur['name'] ?? '') . '"></div>'
      . '<div><label>العمولة %</label><br><input name="comm" type="number" step="0.01" value="' . e($cur['comm'] ?? 0) . '" style="width:80px"></div>'
      . '<div><label>البريد الإلكتروني</label><br><input name="mail" value="' . e($cur['email'] ?? '') . '"></div>'
      . '<div><label>رقم الهاتف</label><br><input name="tel" value="' . e($cur['tel'] ?? '') . '"></div>'
      . '<div><label>رقم محمول</label><br><input name="mob" value="' . e($cur['mobile'] ?? '') . '"></div>'
      . '<div><label>ملاحظات</label><br><input name="notes" style="width:220px" value="' . e($cur['notes'] ?? '') . '"></div>'
      . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form></div>';
  }
}
layout_close();
