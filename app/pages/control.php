<?php
// pages/control.php — لوحة التحكم الشاملة (تبويب واحد): قاعدة/أصناف/عملاء/دفع/ولاء/يوزرات — كل حاجة قابلة للإضافة والتعديل والحذف
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/auth.php';
$db = db();
$__admin = cfg_is_admin();
$__uid = (int)($_SESSION['uid'] ?? 0);
$__isAdmin = $__admin;
// وصول: مالك أو m_admin (الخريطة بتزوّد برضه — دي طبقة عرض)
$__permOk = $__admin;
if (!$__permOk) {
  try {
    $n = 0;
    $st = $db->prepare("SELECT perm, val FROM user_perms WHERE user_id=?");
    $st->execute([$__uid]);
    foreach ($st->fetchAll() as $__r) { $n++; if ((int)$__r['val'] === 1 && $__r['perm'] === 'm_admin') $__permOk = true; }
    if ($n === 0) $__permOk = false;
  } catch (Throwable $t) {}
}
if (!$__permOk) { echo '<div class="card">🔒 الصفحة دي محتاجة صلاحية الإدارة — كلم المالك.</div>'; layout_close(); exit; }
$__canP = !empty(cfg_user_flags($__uid)['edit_price']);
$groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll(); /* بيانات الإدخال */
$units = $db->query("SELECT id, name FROM units ORDER BY id")->fetchAll();
$curDb = trim((string)@file_get_contents(__DIR__ . '/../../db.name'));
if ($curDb === '') { $c = require __DIR__ . '/../config.php'; $curDb = (string)$c['db']['name']; }
$usersRs = $db->query("SELECT id, username, edit_inv, edit_disc, edit_price, edit_date, IS_Deleted FROM users ORDER BY id")->fetchAll();
$pmMap = [];
foreach ($db->query("SELECT user_id, perm FROM user_perms WHERE val=1")->fetchAll() as $__r) $pmMap[(int)$__r['user_id']][] = $__r['perm'];
$lovern = cfg_get('loy_earn', '1') ?: '1';
$lovcash = cfg_get('loy_cash_rate', '0.05') ?: '0.05';
layout_open('🛠️ لوحة التحكم الشاملة', '');
echo '<div class="note">الكل هنا بتبويب واحد فوق — الأصناف، العملاء، طرق الدفع، الولاء، قاعدة البيانات، واليوزرات. عدّل واحفظ عمّية.</div><style>
.tcv{display:flex;gap:8px;flex-wrap:wrap;padding:8px 0;position:sticky;top:6px;z-index:30}
.tcv button{padding:10px 18px;border:1.5px solid rgba(255,255,255,.75);border-radius:14px;background:rgba(255,255,255,.42);backdrop-filter:blur(12px) saturate(1.6);-webkit-backdrop-filter:blur(12px) saturate(1.6);cursor:pointer;font-family:inherit;font-weight:800;color:#1e1b4b;box-shadow:0 6px 22px rgba(107,33,168,.14),inset 0 1px 0 rgba(255,255,255,.8);transition:all .22s}
.tcv button:hover{transform:translateY(-2px);background:rgba(255,255,255,.62);box-shadow:0 10px 30px rgba(107,33,168,.20),inset 0 1px 0 rgba(255,255,255,.9)}
.tcv button.on{background:linear-gradient(135deg,#6b21a8,#a855f7);color:#fff;border-color:rgba(168,85,247,.6);box-shadow:0 8px 28px rgba(168,85,247,.38),inset 0 1px 0 rgba(255,255,255,.35)}
.tin{padding:8px;border:1px solid #cbd5e1;border-radius:8px;font-family:inherit}
.rowx{display:flex;gap:8px;flex-wrap:wrap;align-items:end}
.rowx > div{min-width:0}
table.ctrl{width:100%;border-collapse:collapse;font-size:13px;background:#fff}
table.ctrl th,table.ctrl td{border:1px solid #d1d5db;padding:4px 8px;text-align:center}
table.ctrl td.l{text-align:right}
#w01{background:#f0fdf4;border-color:#22c55e33;color:#166534;width:100%;text-align:center;font-weight:800;padding:8px;border-radius:10px;display:none}
</style>
<div id="w01"></div>';
?>
<div class="tcv" id="tabs">
  <button data-s="items" class="on">🏷️ الأصناف</button>
  <button data-s="cust">👥 العملاء</button>
  <button data-s="pay">💳 طرق الدفع</button>
  <button data-s="loy">⭐ الولاء</button>
  <?php if ($__isAdmin): ?><button data-s="db">🗄️ قاعدة البيانات</button><?php endif; ?>
  <button data-s="usr">🔑 اليوزرات</button>
</div>
<div id="sItems" class="sc"><div class="card">
  <h3>🏷️ الأصناف — إضافة / تعديل / حذف</h3>
  <div class="rowx"><div style="flex:1"><label>بحث (كود / اسم / باركود)</label><br><input id="iq" class="tin" style="width:100%" placeholder="ابدأ أكتب..."></div><div><br><button class="tin" style="background:#7c3aed;color:#fff;font-weight:800" onclick="itemNew()">➕ صنف جديد</button></div></div>
  <div id="iRes" style="margin:8px 0"></div>
  <div id="iForm" style="display:none">
    <hr>
    <div class="rowx">
      <div><label>كود</label><br><input id="fId" class="tin" style="width:70px;background:#fef08a;font-weight:800" readonly tabindex="-1"></div>
      <div style="flex:1"><label>الصنف *</label><br><input id="fNm" class="tin" style="width:100%"></div>
      <div><label>EN</label><br><input id="fEn" class="tin" dir="ltr"></div>
      <div><label>باركود</label><br><input id="fBc" class="tin"></div>
      <div><label>المجموعة</label><br><select id="fGrp" class="tin"><?php foreach ($groups as $gg): ?><option value="<?=$gg['id']?>"><?=e($gg['name'])?></option><?php endforeach; ?></select></div>
      <div><label>الوحدة</label><br><select id="fUnit" class="tin"><?php foreach ($units as $uu): ?><option value="<?=$uu['id']?>"><?=e($uu['name'])?></option><?php endforeach; ?></select></div>
      <div><label>النوع</label><br><select id="fType" class="tin"><option value="1">مخزني</option><option value="2">خدمي</option></select></div>
    </div>
    <div class="rowx" style="margin-top:8px">
      <div><label>شراء</label><br><input id="fCost" type="number" step="0.01" class="tin" style="width:90px" <?=$__canP ? '' : 'disabled'?>></div>
      <div><label>بيع</label><br><input id="fSale" type="number" step="0.01" class="tin" style="width:90px" <?=$__canP ? '' : 'disabled'?>></div>
      <div><label>جملة</label><br><input id="fWhole" type="number" step="0.01" class="tin" style="width:90px" <?=$__canP ? '' : 'disabled'?>></div>
      <div><label>خصم%</label><br><input id="fDisc" type="number" step="0.01" class="tin" style="width:70px" <?=$__canP ? '' : 'disabled'?>></div>
      <div><label>ضريبة%</label><br><input id="fTax" type="number" step="0.01" class="tin" style="width:70px"></div>
      <div><label>حد أدنى</label><br><input id="fLim" type="number" step="0.01" class="tin" style="width:80px"></div>
      <div><label>أقل بيع</label><br><input id="fMinp" type="number" step="0.01" class="tin" style="width:80px" <?=$__canP ? '' : 'disabled'?>></div>
      <div style="flex:1"><label>ملاحظة</label><br><input id="fNotes" class="tin" style="width:100%"></div>
      <div><br><button class="tin" style="background:#16a34a;color:#fff;font-weight:800" onclick="itemSave()">💾 حفظ</button> <button class="tin" onclick="itemHide()">إلغاء</button> <span id="iMsg" style="font-weight:800;color:#b91c1c"></span></div>
    </div>
    <div style="background:#f5f3ff;border:1px solid #d8b4fe;border-radius:4px;padding:8px;margin-top:8px">
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <b>🖼️ صورة الصنف</b>
        <input type="file" id="iImgFile" accept="image/jpeg,image/png,image/webp" onchange="iImgChange(this)" style="font-family:inherit">
        <span id="iImgMsg" style="color:#6b21a8;font-weight:700;font-size:12px"></span>
        <img id="iImgPrev" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #ccc;display:none">
      </div>
      <label style="display:flex;align-items:center;gap:6px;margin-top:6px;font-weight:800;color:#166534">
        <input type="checkbox" id="iEo" checked style="width:16px;height:16px"> رفع الصنف على إيزي أوردر بعد الحفظ
      </label>
    </div>
  </div>
</div></div>

<div id="sCust" class="sc" style="display:none"><div class="card">
  <h3>👥 العملاء والموردين — إضافة / تعديل / حذف</h3>
  <div class="rowx"><div style="flex:1"><label>بحث (كود / اسم / موبايل)</label><br><input id="cq" class="tin" style="width:100%" placeholder="ابدأ أكتب..."></div><div><br><button class="tin" style="background:#7c3aed;color:#fff;font-weight:800" onclick="custNew()">➕ جديد</button></div></div>
  <div id="cRes" style="margin:8px 0"></div>
  <div id="cForm" style="display:none">
    <hr>
    <div class="rowx">
      <div><label>كود</label><br><input id="cId" class="tin" style="width:70px;background:#fef08a;font-weight:800" readonly tabindex="-1"></div>
      <div style="flex:1"><label>الاسم *</label><br><input id="cNm" class="tin" style="width:100%"></div>
      <div><label>موبايل</label><br><input id="cMob" class="tin"></div>
      <div><label>النوع</label><br><select id="cAct" class="tin"><option value="1">عميل</option><option value="2">مورد</option></select></div>
      <div><label>رصيد افتتاحي</label><br><input id="cOpen" type="number" step="0.01" class="tin" style="width:100px"></div>
      <div><label>حد مديونية</label><br><input id="cDep" type="number" step="0.01" class="tin" style="width:100px"></div>
      <div style="flex:1"><label>ملاحظة</label><br><input id="cNote" class="tin" style="width:100%"></div>
      <div><br><button class="tin" style="background:#16a34a;color:#fff;font-weight:800" onclick="custSave()">💾 حفظ</button> <button class="tin" onclick="custHide()">إلغاء</button> <span id="cMsg" style="font-weight:800;color:#b91c1c"></span></div>
    </div>
  </div>
</div></div>

<div id="sPay" class="sc" style="display:none"><div class="card">
  <h3>💳 طرق الدفع — الإعداد يتأثر في الفواتير والتقارير والطباعة</h3>
  <table class="ctrl" id="payTbl"><tr><th>م</th><th>الاسم</th><th>شغالة</th><th>حفظ</th><th>حذف</th></tr></table>
  <div class="rowx" style="margin-top:8px"><div><label>طريقة جديدة</label><br><input id="piNm" class="tin" placeholder="مثال: فودافون كاش"></div><div><br><button class="tin" style="background:#16a34a;color:#fff" onclick="payAdd()">➕ إضافة</button></div><div style="flex:1;text-align:right" id="payMsg"></div></div>
  <div class="note">الحذف مشي فقط لو مافيش فواتير مستعملاها — وإلا عطلها بدل الحذف. الكاش لا يُحذف ولا يُعطل.</div>
</div></div>

<div id="sLoy" class="sc" style="display:none"><div class="card">
  <h3>⭐ برنامج الولاء — القواعد والنقاط</h3>
  <div class="rowx">
    <div><label>نقطة لكل (جنيه)</label><br><input id="lEarn" type="number" step="0.01" class="tin" style="width:90px" value="<?=e($lovern)?>"></div>
    <div><label>جنيه لكل (نقطة)</label><br><input id="lCash" type="number" step="0.001" class="tin" style="width:90px" value="<?=e($lovcash)?>"></div>
    <div><br><button class="tin" style="background:#16a34a;color:#fff;font-weight:800" onclick="loyRules()">💾 حفظ القواعد</button> <span id="lMsg" style="font-weight:800;color:#166534"></span></div>
  </div>
  <hr>
  <h3>✏️ تعديل نقاط عميل</h3>
  <div class="rowx">
    <div><label>كود العميل</label><br><input id="lCust" class="tin" style="width:90px"></div>
    <div><label>نقاط (+/-)</label><br><input id="lPts" type="number" class="tin" style="width:90px"></div>
    <div style="flex:1"><label>سبب</label><br><input id="lNote" class="tin" style="width:100%"></div>
    <div><br><button class="tin" style="background:#ea580c;color:#fff;font-weight:800" onclick="loyAdj()">اضافة/خصم</button>
      <?php if ($__isAdmin): ?><button class="tin" style="background:#dc2626;color:#fff" onclick="loyZero()">🧹 تصفير</button><?php endif; ?>
      <span id="lMsg2" style="font-weight:800;color:#b91c1c"></span></div>
  </div>
  <hr>
  <h3>🔍 نقاط العملاء</h3>
  <div class="rowx"><div style="flex:1"><input id="lq" class="tin" style="width:100%" placeholder="بحث بالاسم أو الكود..."></div><div><button class="tin" onclick="loyList()">عرض</button></div></div>
  <?php if ($__isAdmin): ?><button class="tin" style="background:#0f766e;color:#fff;font-weight:800;margin-top:6px" onclick="loyEnroll()">👥 ضم كل العملاء للبرنامج</button><span id="lEnroll" style="margin-right:8px;font-weight:800;color:#075985"></span><?php endif; ?>
  <div id="loyRes" style="margin:8px 0"></div>
</div></div>

<?php if ($__isAdmin): ?>
<div id="sDb" class="sc" style="display:none"><div class="card">
  <h3>🗄️ قاعدة البيانات — الحالية: <b id="dbCur"><?=e($curDb)?></b></h3>
  <div class="rowx">
    <div style="flex:1"><label>القواعد المتاحة (rs_*)</label><br><select id="dbSel" class="tin" style="width:100%"></select></div>
    <div><br><button class="tin" style="background:#0f766e;color:#fff;font-weight:800" onclick="dbUse()">↪️ التبديل</button></div>
  </div>
  <div class="rowx" style="margin-top:8px">
    <div><label>قاعدة محل جديدة</label><br><input id="dbNew" class="tin" placeholder="rs_frsa3a" dir="ltr"></div>
    <div><br>
      <button class="tin" style="background:#7c3aed;color:#fff" onclick="dbCreate(0)">🆕 إنشاء</button>
      <button class="tin" style="background:#b45309;color:#fff" onclick="dbCreate(1)">📋 نسخة كاملة</button>
    </div>
  </div>
  <div id="dbMsg" class="note" style="margin-top:8px"></div>
  <div class="note">⚠️ التبديل يغيّر قاعدة عمل البرنامج كله فوراً — وبعد التبديل سجل خروج وادخل تاني. إنشاء قاعدة = نفس الهيكل + تعريفات (يوزرات/شركة/مجموعات/وحدات/بنوك/إعدادات) والفواتير والأصناف تبدأ فاضية.</div>
</div></div>
<?php endif; ?>

<div id="sUsr" class="sc" style="display:none"><div class="card">
  <h3>🔑 اليوزرات والصلاحيات</h3>
  <div class="rowx"><div><label>اسم</label><br><input id="uNm" class="tin"></div><div><label>باسورد</label><br><input id="uPw" class="tin" type="password"></div><div><br><button class="tin" style="background:#16a34a;color:#fff" onclick="usrAdd()">➕ إضافة</button></div><div id="usrMsg" style="font-weight:800"></div></div>
  <div id="usrRes" style="margin-top:10px;overflow-x:auto"></div>
  <div class="note">بلا موديولات محددة = صلاحيات كاملة (وضع قديم). «موقوف» يمنع الدخول مؤقتاً. المالك admin لا يُعدل.</div>
</div></div>

<script>
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(act, body, method) {
  const r = await fetch('api_control.php?act=' + act, method === false
    ? { method: 'GET' }
    : (body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : { method: 'GET' }));
  try { return await r.json(); } catch (t) { return { ok: false, error: 'فشل الاتصال (' + r.status + ')' }; }
}
const AC = async (act, body) => api(act, body);
const AI = async (q) => (await fetch('api_item_add.php' + q)).json();
function note(t, good) { const w = $('w01'); w.style.display = 'block'; w.style.background = good ? '#f0fdf4' : '#fef2f2'; w.style.color = good ? '#166534' : '#b91c1c'; w.textContent = t; clearTimeout(w.__t); w.__t = setTimeout(() => w.style.display = 'none', 4000); }
document.querySelectorAll('#tabs button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#tabs button').forEach(x => x.classList.toggle('on', x === b));
  document.querySelectorAll('.sc').forEach(s => s.style.display = 'none');
  const s = { items: 'sItems', cust: 'sCust', pay: 'sPay', loy: 'sLoy', db: 'sDb', usr: 'sUsr' }[b.dataset.s];
  if ($(s)) { $(s).style.display = 'block'; LOADS[b.dataset.s] && LOADS[b.dataset.s](); }
});
const LOADS = {};

// ===== الأصناف =====
let iqT = null;
$('iq').addEventListener('input', () => { clearTimeout(iqT); iqT = setTimeout(itemSearch, 250); });
async function itemSearch() {
  const q = $('iq').value.trim();
  if (!q) { $('iRes').innerHTML = ''; return; }
  const r = await AI('?act=search&q=' + encodeURIComponent(q));
  $('iRes').innerHTML = '<table class="ctrl"><tr><th>كود</th><th class="l">الصنف</th><th>بيع</th><th>رصيد</th><th>م</th></tr>' + (r.rows || []).map(x =>
    '<tr><td>' + x.id + '</td><td class="l"><b>' + esc(x.symbol) + '</b></td><td>' + (x.sale_price ?? 0) + '</td><td>' + (x.bal ?? 0) + '</td>' +
    '<td><button class="tin" onclick="itemGet(' + x.id + ')">✏️</button> <button class="tin" style="background:#dc2626;color:#fff" onclick="itemDel(' + x.id + ',this)">🗑️</button></td></tr>').join('') + '</table>';
}
async function itemNew() {
  const r = await AI('?act=next');
  itemHide();
  $('iForm').style.display = 'block';
  $('fId').value = '(' + (r.next || '؟') + ' القادم)';
  $('fNm').focus();
}
function itemHide() { $('iForm').style.display = 'none'; for (const id of ['fEn','fBc','fCost','fSale','fWhole','fDisc','fTax','fLim','fMinp','fNotes']) $(id).value = ''; $('fId').value = ''; $('fNm').value = ''; $('iMsg').textContent = ''; iImgReset(); }
let iImgData = '';
function iImgReset() {
  iImgData = '';
  const f = $('iImgFile'); if (f) f.value = '';
  const p = $('iImgPrev'); if (p) { p.style.display = 'none'; p.src = ''; }
  const m = $('iImgMsg'); if (m) m.textContent = '';
}
function iResizeImg(file, max, q) {
  return new Promise((res, rej) => {
    const img = new Image(); const u = URL.createObjectURL(file);
    img.onload = () => { try {
      let w = img.width, h = img.height;
      if (w > max || h > max) { const k = max / Math.max(w, h); w = Math.round(w * k); h = Math.round(h * k); }
      const c = document.createElement('canvas'); c.width = w; c.height = h;
      const g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, w, h); g.drawImage(img, 0, 0, w, h);
      res(c.toDataURL('image/jpeg', q));
    } catch (e) { rej(e); } finally { URL.revokeObjectURL(u); } };
    img.onerror = () => { URL.revokeObjectURL(u); rej(new Error('ملف الصورة غير مدعوم')); };
    img.src = u;
  });
}
async function iImgChange(inp) {
  const f = inp.files && inp.files[0]; if (!f) return;
  try {
    $('iImgMsg').textContent = '⏳ جاري التجهيز...';
    iImgData = await iResizeImg(f, 900, 0.82);
    $('iImgPrev').src = iImgData; $('iImgPrev').style.display = 'inline-block';
    $('iImgMsg').textContent = '✅ جاهزة (' + Math.round(iImgData.length / 1365) + ' ك.ب)';
  } catch (e) { iImgData = ''; $('iImgMsg').textContent = '❌ ' + e.message; }
}
async function itemGet(id) {
  const r = await AI('?act=get&id=' + id);
  if (!r.ok || !r.item) { note('الصنف غير موجود'); return; }
  const it = r.item;
  $('iForm').style.display = 'block';
  $('fId').value = it.id; $('fNm').value = it.symbol; $('fEn').value = it.nameEN ?? ''; $('fBc').value = it.barcode !== '0' ? it.barcode : '';
  $('fGrp').value = it.group_id || 0; $('fUnit').value = it.unit || 1; $('fType').value = it.type || 1;
  $('fCost').value = it.purch_price ?? 0; $('fSale').value = it.sale_price ?? 0; $('fWhole').value = it.whole_price ?? 0;
  $('fDisc').value = it.discount ?? 0; $('fTax').value = it.tax ?? 0; $('fLim').value = it.limit ?? 0; $('fMinp').value = it.minprice ?? 0; $('fNotes').value = it.notes ?? '';
  $('iForm').scrollIntoView({ behavior: 'smooth' });
  $('iRes').innerHTML = '';
  iImgReset();
  if (it.imge) $('iImgMsg').textContent = 'الصورة الحالية: ' + it.imge;
}
async function itemSave() {
  const nm = $('fNm').value.trim();
  if (!nm) { $('iMsg').textContent = 'اسم الصنف مطلوب'; return; }
  const body = { id: $('fId').value && !$('fId').value.startsWith('(') ? +$('fId').value : 0, name: nm, en: $('fEn').value, barcode: $('fBc').value || '0', grp: +$('fGrp').value, unit: +$('fUnit').value, type: +$('fType').value, cost: +$('fCost').value || 0, sale: +$('fSale').value || 0, whole: $('fWhole').value === '' ? (+$('fSale').value || 0) : +$('fWhole').value, disc: +$('fDisc').value || 0, tax: +$('fTax').value || 0, lim: +$('fLim').value || 0, minp: +$('fMinp').value || 0, notes: $('fNotes').value, img: iImgData, eo: ($('iEo').checked ? 1 : 0) };
  $('iMsg').textContent = '⏳ جاري الحفظ...';
  const r = await fetch('api_item_add.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }).then(x => x.json());
  if (r.ok) {
    let m = '✅ اتحفظ ' + r.id;
    if (r.img && !r.img.ok) m += ' | ⚠️ صورة: ' + (r.img.error || 'فشل');
    if (body.eo) {
      if (r.eo && r.eo.ok) m += r.eo.skipped ? ' | مرفوع بالفعل' : ' | 🚀 اترفع على الموقع';
      else m += ' | ⚠️ الرفع فشل: ' + ((r.eo && r.eo.error) || r.eo_error || 'خطأ');
    }
    $('iMsg').textContent = m;
    note(m, r.eo && !r.eo.ok ? 0 : 1);
    $('iq').value = nm; iImgReset(); itemSearch();
    if (!body.eo || (r.eo && r.eo.ok)) itemHide();
  }
  else note('❌ ' + (r.error || 'فشل'));
}
async function itemDel(id) {
  if (!confirm('حذف الصنف ' + id + '؟ — الرصيد والحركات القديمة بتفضل موجودين في التقارير القديمة')) return;
  const r = await AI('?act=del&id=' + id);
  if (r.ok) { note('✅ اتحذف ' + id, 1); itemSearch(); } else note('❌ ' + (r.error || 'فشل'));
}
LOADS.items = itemSearch;

// ===== العملاء =====
let cqT = null;
$('cq').addEventListener('input', () => { clearTimeout(cqT); cqT = setTimeout(custSearch, 250); });
async function custSearch() {
  const q = $('cq').value.trim();
  const r = await AC('cust_list', { q });
  $('cRes').innerHTML = '<table class="ctrl"><tr><th>كود</th><th class="l">الاسم</th><th>موبايل</th><th>النوع</th><th>م</th></tr>' + (r.rows || []).map(x =>
    '<tr><td>' + x.id + '</td><td class="l"><b>' + esc(x.name) + '</b></td><td>' + esc(x.mobile ?? '') + '</td><td>' + (+x.act === 2 ? 'مورد' : 'عميل') + '</td>' +
    '<td><button class="tin" onclick="custGet(' + x.id + ')">✏️</button> <button class="tin" style="background:#dc2626;color:#fff" onclick="custDelx(' + x.id + ')">🗑️</button></td></tr>').join('') + '</table>';
}
function custHide() { $('cForm').style.display = 'none'; for (const id of ['cId','cNm','cMob','cNote']) $(id).value = ''; $('cOpen').value = 0; $('cDep').value = 0; $('cAct').value = 1; $('cMsg').textContent = ''; }
function custNew() { custHide(); $('cForm').style.display = 'block'; $('cNm').focus(); }
async function custGet(id) {
  const r = await AC('cust_get', { id });
  if (!r.ok || !r.row) { note('غير موجود'); return; }
  const x = r.row;
  $('cForm').style.display = 'block';
  $('cId').value = x.id; $('cNm').value = x.name; $('cMob').value = x.mobile ?? ''; $('cAct').value = +x.act === 2 ? 2 : 1;
  $('cOpen').value = x.discount_balance ?? 0; $('cDep').value = x.maxdepit ?? 0; $('cNote').value = x.notes ?? '';
  $('cRes').innerHTML = '';
  $('cForm').scrollIntoView({ behavior: 'smooth' });
}
async function custSave() {
  const body = { id: +$('cId').value || 0, name: $('cNm').value.trim(), mobile: $('cMob').value, act: +$('cAct').value, notes: $('cNote').value, discount_balance: +$('cOpen').value || 0, maxdepit: +$('cDep').value || 0 };
  if (!body.name) { $('cMsg').textContent = 'الاسم مطلوب'; return; }
  const r = await AC('cust_save', body);
  if (r.ok) { note('✅ اتحفظ ' + r.id, 1); custHide(); custSearch(); } else note('❌ ' + (r.error || 'فشل'));
}
async function custDelx(id) {
  if (!confirm('حذف العميل ' + id + '؟ — لو له فواتير هيترفض')) return;
  const r = await AC('cust_del', { id });
  if (r.ok) { note('✅ اتحذف', 1); custSearch(); } else note('❌ ' + (r.error || 'فشل'));
}
LOADS.cust = custSearch;

// ===== طرق الدفع =====
async function payList() {
  const r = await AC('pay_list');
  $('payTbl').innerHTML = '<tr><th>م</th><th>الاسم</th><th>شغالة</th><th>حفظ</th><th>حذف</th></tr>' + (r.rows || []).map(x =>
    '<tr><td>' + x.id + '</td><td><input class="tin" id="pn' + x.id + '" value="' + esc(x.name) + '"></td>' +
    '<td><input type="checkbox" id="pa' + x.id + '"' + (+x.active ? ' checked' : '') + '></td>' +
    '<td><button class="tin" style="background:#16a34a;color:#fff" onclick="paySave(' + x.id + ')">💾</button></td>' +
    '<td><button class="tin" style="background:#dc2626;color:#fff" onclick="payDel(' + x.id + ')">🗑️</button></td></tr>').join('') + '</table>';
}
async function paySave(id) {
  const r = await AC('pay_save', { id, name: $('pn' + id).value.trim(), active: $('pa' + id).checked });
  $('payMsg').textContent = r.ok ? '✅ اتحفظ' : '❌ ' + (r.error || 'فشل');
  if (r.ok) payList();
}
async function payAdd() {
  const r = await AC('pay_add', { name: $('piNm').value.trim() });
  $('payMsg').textContent = r.ok ? '✅ اتضافت' : '❌ ' + (r.error || 'فشل');
  if (r.ok) { $('piNm').value = ''; payList(); }
}
async function payDel(id) {
  if (!confirm('حذف الطريقة ' + id + '؟')) return;
  const r = await AC('pay_del', { id });
  $('payMsg').textContent = r.ok ? '✅ اتحذف' : '❌ ' + (r.error || 'فشل');
  if (r.ok) payList();
}
LOADS.pay = payList;

// ===== الولاء =====
async function loyRules() {
  const r = await AC('loy_rules', { earn: $('lEarn').value, cash: $('lCash').value });
  $('lMsg').textContent = r.ok ? '✅ اتحفظ' : '❌ ' + (r.error || 'فشل');
  if (r.ok) note('⭐ القواعد اتحفظت — هتطبق من الفاتورة الجاية', 1);
}
async function loyAdj() {
  const r = await AC('loy_adj', { cust: +$('lCust').value || 0, pts: +$('lPts').value || 0, note: $('lNote').value });
  $('lMsg2').textContent = r.ok ? '✅ اتعديل' : '❌ ' + (r.error || 'فشل');
  if (r.ok) loyList();
}
async function loyZero() {
  const c = +$('lCust').value || 0;
  if (!c) { $('lMsg2').textContent = 'اكتب كود العميل الأول'; return; }
  if (!confirm('تصفير نقاط العميل ' + c + '؟')) return;
  const r = await AC('loy_zero', { cust: c });
  $('lMsg2').textContent = r.ok ? '✅ اتصفر (كان ' + r.old + ')' : '❌ ' + (r.error || 'فشل');
  if (r.ok) loyList();
}
async function loyEnroll() {
  if (!confirm('ضم كل العملاء الحاليين لبرنامج الولاء؟ — النقاط القديمة زي ما هي والباقي يبدؤوا صفر')) return;
  const r = await AC('loy_enroll', {});
  $('lEnroll').textContent = r.ok ? '✅ العضوية: ' + r.total + ' عميل' : '';
  if (r.ok) loyList(); else note('❌ ' + (r.error || 'فشل'));
}
async function loyList() {
  const r = await AC('loy_list', { q: $('lq').value.trim() });
  $('loyRes').innerHTML = '<table class="ctrl"><tr><th>كود</th><th class="l">العميل</th><th>النقاط</th><th>قيمتها</th><th>مكتسبة</th><th>مستبدلة</th></tr>' +
    (r.rows || []).map(x => '<tr><td>' + x.cust_id + '</td><td class="l">' + esc(x.name) + '</td><td><b>' + x.points + '</b></td><td>' + (x.points * parseFloat($('lCash').value || 0)).toFixed(2) + ' ج</td><td>' + x.total_earned + '</td><td>' + x.total_redeemed + '</td></tr>').join('') + '</table>' +
    '<h4>📜 آخر الحركات</h4><table class="ctrl"><tr><th class="l">العميل</th><th>نقاط</th><th>نوع</th><th>فاتورة</th><th class="l">ملاحظة</th></tr>' +
    (r.logs || []).map(g => '<tr><td class="l">' + esc(g.name) + '</td><td>' + g.points + '</td><td>' + ({ earn: 'كسب', redeem: 'استبدال', adjust: 'تعديل' }[g.type] || g.type) + '</td><td>' + (g.inv_id ?? '—') + '</td><td class="l">' + esc(g.note ?? '') + '</td></tr>').join('') + '</table>';
}
$('lq').addEventListener('input', () => { clearTimeout($('lq').__t); $('lq').__t = setTimeout(loyList, 300); });
LOADS.loy = loyList;

// ===== قاعدة البيانات =====
async function dbList() {
  const r = await AC('db_list');
  const sel = $('dbSel');
  sel.innerHTML = (r.rows || []).map(x => '<option value="' + esc(x.name) + '"' + (x.cur ? ' selected' : '') + '>' + esc(x.name) + (x.inv >= 0 ? ' (فواتير: ' + x.inv + ')' : '') + '</option>').join('');
  $('dbCur').textContent = (r.cur || $('dbCur').textContent);
}
async function dbUse() {
  const n = $('dbSel').value;
  if (!confirm('تبديل قاعدة البرنامج لـ «' + n + '»؟ بعد التبديل سجل خروج وادخل تاني')) return;
  const r = await AC('db_use', { name: n });
  $('dbMsg').innerHTML = r.ok ? '✅ بقت: <b>' + esc(n) + '</b> — عمل refresh (F5) وسجل خروج ثم دخول' + (r.note ? '<br>' + esc(r.note) : '') : '❌ ' + (r.error || 'فشل');
  if (r.ok) { $('dbCur').textContent = n; dbList(); }
}
async function dbCreate(full) {
  const n = $('dbNew').value.trim();
  if (!n) { $('dbMsg').textContent = 'اكتب اسم قاعدة يبدأ rs_'; return; }
  const what = full ? ' (هيكل + تعريفات + الأصناف والعملاء الفعليين وطرق الدفع — من غير فواتير ولا أرصدة)' : ' (هيكل فاضي + تعريفات)';
  if (!confirm('إنشاء قاعدة محل جديدة «' + n + '»؟' + what)) return;
  const r = await AC('db_create', { name: n, full: full ? 1 : 0 });
  $('dbMsg').innerHTML = r.ok ? '✅ اتإنشأت <b>' + esc(n) + '</b> (طاولات: ' + r.tables + ') — اختارها من القايمة واضغط التبديل وقت ما تحب' + (r.note ? '<br>📌 ' + esc(r.note) : '') : '❌ ' + (r.error || 'فشل');
  if (r.ok) dbList();
}
LOADS.db = dbList;

// ===== اليوزرات =====
async function usrList() {
  const r = await AC('usr_list', null, false);
  const MODS = r.mods || [];
  $('usrRes').innerHTML = '<table class="ctrl"><tr><th>م</th><th>اليوزر</th><th>تعديل<br>فواتير</th><th>خصم</th><th>سعر</th><th>تاريخ</th><th>موقوف</th><th>باسورد<br>جديدة</th>' + MODS.map(m => '<th>' + esc(m.replace('m_', '')) + '</th>').join('') + '<th>حفظ</th><th>تصفير</th></tr>' +
    (r.rows || []).map(x => {
      const i = x.id, lock = i === 1 ? ' disabled' : '';
      return '<tr' + (x.IS_Deleted ? ' style="background:#fee2e2"' : '') + '><td>' + i + '</td><td class="l"><b>' + esc(x.username) + '</b></td>' +
        ['edit_inv', 'edit_disc', 'edit_price', 'edit_date'].map(k => '<td><input type="checkbox" id="u' + i + k + '"' + (+x[k] ? ' checked' : '') + lock + '></td>').join('') +
        '<td><input type="checkbox" id="u' + i + 'off"' + (x.IS_Deleted ? ' checked' : '') + lock + '></td>' +
        '<td><input class="tin" id="u' + i + 'pw" style="width:80px" placeholder="جديدة"' + lock + '></td>' +
        MODS.map(m => '<td><input type="checkbox" id="u' + i + m + '"' + (x.modn === 7 || (x.mods || []).includes(m) ? ' checked' : '') + lock + '></td>').join('') +
        '<td><button class="tin" style="background:#16a34a;color:#fff" onclick="usrSave(' + i + ')"' + lock + '>💾</button></td>' +
        '<td>' + (i > 1 ? '<button class="tin" onclick="usrReset(' + i + ')">🔄</button>' : '') + '</td></tr>';
    }).join('') + '</table>';
  window.__MODS = MODS;
}
async function usrSave(id) {
  const body = { id, ei: $('u' + id + 'edit_inv').checked, ed: $('u' + id + 'edit_disc').checked, ep: $('u' + id + 'edit_price').checked, edt: $('u' + id + 'edit_date').checked, off: $('u' + id + 'off').checked, pwn: $('u' + id + 'pw').value, mods: (window.__MODS || []).filter(m => $('u' + id + m).checked) };
  const r = await AC('usr_save', body);
  $('usrMsg').textContent = r.ok ? '✅ اتحفظ ' + id : '❌ ' + (r.error || 'فشل');
  if (r.ok) { $('u' + id + 'pw').value = ''; usrList(); }
}
async function usrReset(id) { if (!confirm('تصفير الصلاحيات المخصصة لـ ' + id + ' (يرجع كامل)؟')) return; const r = await AC('usr_reset', { id }); if (r.ok) usrList(); }
async function usrAdd() {
  const r = await AC('usr_add', { u: $('uNm').value.trim(), p: $('uPw').value });
  $('usrMsg').textContent = r.ok ? '✅ اتضاف' : '❌ ' + (r.error || 'فشل');
  if (r.ok) { $('uNm').value = $('uPw').value = ''; usrList(); }
}
LOADS.usr = usrList;

itemSearch();
</script>
<?php layout_close();
