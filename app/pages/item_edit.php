<?php
// pages/item_edit.php — تعريف الأصناف (محرر كامل: إدخال + بحث + مخزون أول مدة + وحدات)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/items.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$msg = '';
$__canPrice = !empty(cfg_user_flags((int)($_SESSION['uid'] ?? 0))['edit_price']);
if (($_POST['save'] ?? '') !== '') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0 && !$__canPrice) {
    $msg = '❌ لا صلاحية تعديل صنف/سعر — كلم الإدارة';
  } else {
    $in = [
      'name' => $_POST['nm'] ?? '', 'en' => $_POST['en'] ?? '', 'barcode' => $_POST['bc'] ?? '',
      'grp' => $_POST['grp'] ?? 0, 'unit' => $_POST['unit'] ?? 1, 'type' => $_POST['type'] ?? 1,
      'cost' => $_POST['purch'] ?? 0, 'sale' => $_POST['sale'] ?? 0, 'whole' => $_POST['whole'] ?? 0,
      'disc' => $_POST['disc'] ?? 0, 'tax' => $_POST['tax'] ?? 0, 'lim' => $_POST['lim'] ?? 0,
      'minp' => $_POST['minp'] ?? 0, 'notes' => $_POST['notes'] ?? '',
      'img' => $_POST['img'] ?? '', 'eo' => !empty($_POST['eo']),
    ];
    try {
      $r = item_save($in, $id);
      if (empty($r['ok'])) {
        $msg = '❌ ' . (string)($r['error'] ?? 'خطأ');
      } else {
        $id = (int)$r['id'];
        $msg = ($r['edited'] ? '✅ اتعدل ' : '✅ اتضاف صنف ') . $id;
        if (!empty($r['img'])) $msg .= $r['img']['ok'] ? ' | 🖼️ الصورة اتحفظت' : ' | ⚠️ صورة: ' . $r['img']['error'];
        if (!empty($r['eo'])) {
          $msg .= $r['eo']['ok']
            ? (!empty($r['eo']['existing']) ? ' | 🖼️ الصورة اتحدثت على الموقع' : ' | 🚀 اترفع على الموقع')
            : ' | ⚠️ الرفع فشل: ' . $r['eo']['error'];
        }
      }
    } catch (Throwable $t) {
      $msg = '❌ ' . $t->getMessage();
    }
  }
  $_GET['id'] = $id;
}
$id = (int)($_GET['id'] ?? 0);
$groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
$units = $db->query("SELECT id, name FROM units ORDER BY id")->fetchAll();
$it = $id > 0 ? $db->query("SELECT * FROM currencies WHERE id=$id")->fetch() : null;
$hasImg = $it && preg_match('~^uploads/~', (string)($it['imge'] ?? ''));
$bal = 0;
if ($it) $bal = (float)($db->query("SELECT IFNULL((SELECT bal FROM item_bal WHERE id=$id),0)")->fetchColumn());
$open = $it ? $db->query("SELECT i.date d, s.val q, s.exchange_price p FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id WHERE s.currency_from=$id AND i.proc_type=3 AND i.IS_Deleted=0 ORDER BY i.date DESC LIMIT 10")->fetchAll() : [];
$subu = $it ? $db->query("SELECT u.unit, un.name nm, u.perc, u.sale, u.barcode FROM curr_units u LEFT JOIN units un ON un.id=u.unit WHERE u.curr=$id")->fetchAll() : [];
layout_open('تعريف الأصناف', '');
echo '<div class="card"><div class="row"><div><h3>' . ($it ? 'تعديل: ' . e($it['symbol']) . ' (' . $id . ')' : 'صنف جديد') . '</h3></div><div style="margin-right:auto"><a href="items.php">🔍 بحث</a></div></div>'
  . '<form method="post"><input type="hidden" name="id" value="' . $id . '"><div class="row">'
  . '<div><label>الصنف</label><br><input name="nm" style="width:220px" value="' . e($it['symbol'] ?? '') . '"></div>'
  . '<div><label>الصنف (EN)</label><br><input name="en" style="width:160px" value="' . e($it['nameEN'] ?? '') . '" dir="ltr"></div>'
  . '<div><label>الباركود</label><br><input name="bc" value="' . e($it['barcode'] ?? '') . '"></div>'
  . '<div><label>مجموعة الصنف</label><br><select name="grp">';
foreach ($groups as $g) echo '<option value="' . $g['id'] . '"' . (($it && $it['group_id'] == $g['id']) ? ' selected' : '') . '>' . e($g['name']) . '</option>';
echo '</select></div>'
  . '<div><label>نوع الصنف</label><br><select name="type"><option value="1"' . (($it['type'] ?? 1) == 1 ? ' selected' : '') . '>مخزني</option><option value="2"' . (($it['type'] ?? 1) == 2 ? ' selected' : '') . '>خدمي</option></select></div>'
  . '<div><label>الوحدة الافتراضية</label><br><select name="unit">';
foreach ($units as $u) echo '<option value="' . $u['id'] . '"' . (($it && $it['unit'] == $u['id']) ? ' selected' : '') . '>' . e($u['name']) . '</option>';
echo '</select></div>'
  . '<div><label>سعر الشراء</label><br><input name="purch" type="number" step="0.01" value="' . e($it['purch_price'] ?? 0) . '" style="width:90px"></div>'
  . '<div><label>سعر البيع</label><br><input name="sale" type="number" step="0.01" value="' . e($it['sale_price'] ?? 0) . '" style="width:90px"></div>'
  . '<div><label>سعر الجملة</label><br><input name="whole" type="number" step="0.01" value="' . e($it['whole_price'] ?? 0) . '" style="width:90px"></div>'
  . '<div><label>الخصم %</label><br><input name="disc" type="number" step="0.01" value="' . e($it['discount'] ?? 0) . '" style="width:70px"></div>'
  . '<div><label>الضريبة %</label><br><input name="tax" type="number" step="0.01" value="' . e($it['tax'] ?? 0) . '" style="width:70px"></div>'
  . '<div><label>الحد الأدنى للكمية</label><br><input name="lim" type="number" step="0.01" value="' . e($it['limit'] ?? 0) . '" style="width:80px"></div>'
  . '<div><label>أقل سعر بيع</label><br><input name="minp" type="number" step="0.01" value="' . e($it['minprice'] ?? 0) . '" style="width:80px"></div>'
  . '<div><label>ملاحظة</label><br><input name="notes" style="width:200px" value="' . e($it['notes'] ?? '') . '"></div>'
  . '<div><label>صورة الصنف</label><br><input type="file" id="ieFile" accept="image/jpeg,image/png,image/webp" onchange="ieChange(this)"><br><span id="ieMsg" style="font-weight:700;color:#6b21a8;font-size:12px"></span><img id="iePrev" src="' . ($hasImg ? 'item_img.php?id=' . $id : '') . '" style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #ccc;vertical-align:middle;' . ($hasImg ? '' : 'display:none') . '"></div>'
  . '<div><br><label style="font-weight:800;color:#166534"><input type="checkbox" name="eo" id="ieEo" value="1" checked> رفع على إيزي أوردر</label><br><input type="hidden" name="img" id="ieImgData" value=""></div>'
  . '<div><br><button name="save" value="1">💾 حفظ</button> <span>' . $msg . '</span></div></div></form>';
if ($it) echo '<div class="note">الرصيد الحالي: <b>' . $bal . '</b></div>';
echo '</div>';
if ($it) {
  echo '<div class="card"><h3>مخزون أول المدة</h3><table><tr><th>التاريخ</th><th>الكمية</th><th>السعر</th></tr>';
  foreach ($open as $o) echo '<tr><td>' . e($o['d']) . '</td><td>' . $o['q'] . '</td><td>' . $o['p'] . '</td></tr>';
  echo '</table></div>';
  echo '<div class="card"><h3>الوحدات الفرعية</h3><table><tr><th>الوحدة</th><th>النسبة</th><th>السعر</th><th>باركود</th></tr>';
  foreach ($subu as $u) echo '<tr><td>' . e($u['nm'] ?? $u['unit']) . '</td><td>' . $u['perc'] . '</td><td>' . $u['sale'] . '</td><td>' . e($u['barcode'] ?? '') . '</td></tr>';
  echo '</table></div>';
}
echo '<script>
function ieResize(file,max,q){return new Promise((res,rej)=>{const img=new Image();const u=URL.createObjectURL(file);
img.onload=()=>{try{let w=img.width,h=img.height;if(w>max||h>max){const k=max/Math.max(w,h);w=Math.round(w*k);h=Math.round(h*k);}
const c=document.createElement("canvas");c.width=w;c.height=h;const g=c.getContext("2d");g.fillStyle="#fff";g.fillRect(0,0,w,h);g.drawImage(img,0,0,w,h);
res(c.toDataURL("image/jpeg",q));}catch(e){rej(e);}finally{URL.revokeObjectURL(u);}};
img.onerror=()=>{URL.revokeObjectURL(u);rej(new Error("ملف الصورة غير مدعوم"));};img.src=u;});}
async function ieChange(inp){const f=inp.files&&inp.files[0];if(!f)return;const m=document.getElementById("ieMsg");
try{m.textContent="⏳ جاري التجهيز...";const d=await ieResize(f,900,0.82);
document.getElementById("ieImgData").value=d;const p=document.getElementById("iePrev");p.src=d;p.style.display="inline-block";
m.textContent="✅ جاهزة ("+Math.round(d.length/1365)+" ك.ب)";}catch(e){document.getElementById("ieImgData").value="";m.textContent="❌ "+e.message;}}
</script>';
layout_close();
