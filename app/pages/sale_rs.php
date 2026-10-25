<?php
// pages/buy_rs.php — فاتورة مشتريات نسخة طبق الأصل (تخطيط سطح المكتب الكامل)
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/auth.php';
auth_need();
require_once __DIR__ . '/../lib/cfg.php';
$db = db();
$ret = ($_GET['mode'] ?? '') === 'ret';
$quot = ($_GET['mode'] ?? '') === 'quot';
$pt = $ret ? 2 : 1;
$groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
$units = $db->query("SELECT id, name FROM units ORDER BY id")->fetchAll();
$custs = $db->query("SELECT id, name, mobile, tel, mobile2 FROM customers WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY name LIMIT 500")->fetchAll();
$smans = $db->query("SELECT id, name FROM salesmen WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY id")->fetchAll();
$bnks = $db->query("SELECT id, name FROM banks WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY id")->fetchAll();
$susp = $db->query("SELECT id, DATE(date) d, tot_net FROM inv WHERE branch=99 AND IS_Buy=0 AND proc_type=$pt AND IS_Deleted=0 AND IS_Temp=1 ORDER BY id DESC LIMIT 20")->fetchAll();
$next = (int)$db->query("SELECT IFNULL(MAX(id),0)+1 n FROM inv")->fetch()['n'];
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $ret ? 'مرتجع مبيعات' : ($quot ? 'عرض سعر خدمات' : 'فاتورة مبيعات') ?> — النسخة الحرة</title>
<style>@media(max-width:760px){.main{flex-direction:column}.pad{width:100%!important;grid-template-columns:repeat(6,1fr)}.center{min-width:0}table.grid{display:block;overflow-x:auto;white-space:nowrap}.bottom{flex-direction:column}.tots{width:100%!important}.topbar{flex-wrap:wrap}.topbar input{width:140px!important}.toolbar{flex-wrap:wrap}.payrow{flex-wrap:wrap}.bluebar .r{flex-direction:column;align-items:stretch}.bluebar .r>span{width:100%!important;min-width:0!important}.bluebar .r>span>div{width:100%}.bluebar input,.bluebar select{max-width:100%;width:100%!important}.bluebar #custSearch{width:100%!important}#loyaltyBox{display:inline-flex!important}}</style>
<style>
*{box-sizing:border-box}body{font-family:Tahoma,Arial;margin:0;background:#e8edf3;font-size:13px}
.topbar{background:#f1f5f9;padding:6px 10px;display:flex;gap:10px;align-items:center;border-bottom:1px solid #cbd5e1}
.topbar h2{margin:0;font-size:16px}
.topbar input{padding:6px 10px;border:1px solid #94a3b8;border-radius:4px;width:260px;font-family:inherit}
.main{display:flex;gap:6px;padding:6px}
.pad{width:150px;flex-shrink:0;background:#fff;border:1px solid #94a3b8;border-radius:4px;padding:4px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:3px}
.pad button{font-size:15px;font-weight:800;padding:8px 0;border:1px solid #64748b;border-radius:4px;background:#f8fafc;cursor:pointer;font-family:inherit}
.pad button.c{background:#0f172a;color:#fff}
.pbox{grid-column:1/-1;border:1px solid #94a3b8;border-radius:3px;padding:3px;font-size:11px;color:#b91c1c}
.pbox b{display:block;color:#000;font-size:13px}
.center{flex:1;min-width:0}
.bluebar{background:<?= ($ret || $quot) ? '#f9a8d4' : '#4ade80' ?>;border:1px solid #16a34a;border-radius:4px;padding:6px;margin-bottom:6px}
.bluebar .r{display:flex;gap:6px;align-items:end;flex-wrap:wrap;margin-bottom:4px}
.bluebar label{font-size:11px;font-weight:700;color:#052e16}
.bluebar input,.bluebar select{padding:5px;border:1px solid #16a34a;border-radius:3px;background:#fefce8;font-family:inherit;font-size:13px}
.tbtn{padding:6px 10px;border:1px solid #475569;border-radius:4px;background:#f1f5f9;cursor:pointer;font-family:inherit;font-size:12px}
table.grid{width:100%;border-collapse:collapse;background:#fff;font-size:12px}
table.grid th{background:#f1f5f9;border:1px solid #94a3b8;padding:5px;font-size:11px}
table.grid td{border:1px solid #cbd5e1;padding:3px 5px;text-align:center}
table.grid input{width:100%;border:none;text-align:center;font-family:inherit;font-size:12px;background:transparent}
.bottom{display:flex;gap:6px;padding:0 6px 6px}
.tots{width:150px;flex-shrink:0;display:flex;flex-direction:column;gap:3px}
.tots .trow{display:flex;gap:4px;align-items:center}
.tots label{font-size:10px;width:86px;color:#334155}
.tots input{flex:1;padding:4px;border:1px solid #94a3b8;border-radius:3px;font-family:inherit;font-size:12px;text-align:center;min-width:0}
.tots input.net{background:#4ade80;font-weight:800}
.mid{flex:1}
.payrow{display:flex;gap:8px;align-items:end;background:#fff;border:1px solid #94a3b8;border-radius:4px;padding:8px}
.payrow label{font-size:11px}
.payrow input{padding:6px;border:1px solid #94a3b8;border-radius:3px;width:90px;font-family:inherit;text-align:center}
#btnPay{background:#16a34a;color:#fff;font-weight:800;font-size:16px;padding:10px 22px;border:none;border-radius:4px;cursor:pointer;font-family:inherit}
#btnHold{background:#e2e8f0;font-weight:700;padding:10px 16px;border:1px solid #94a3b8;border-radius:4px;cursor:pointer;font-family:inherit}
.toolbar{display:flex;gap:4px;align-items:center;background:#f1f5f9;border-top:1px solid #94a3b8;padding:5px 8px}
.toolbar button{padding:6px 10px;border:1px solid #64748b;border-radius:4px;background:#fff;cursor:pointer;font-family:inherit;font-size:14px}
#msg{color:#b91c1c;font-weight:700}
@media print{.pad,.toolbar,.tbtn,#msg,.no-print{display:none!important}}
</style></head>
<body>
<div class="topbar"><h2><?= $ret ? '↩️ مرتجع مبيعات' : ($quot ? '📋 عرض سعر خدمات' : 'فاتورة مبيعات') ?></h2><input id="srch" list="srchList" autocomplete="off" placeholder="البحث (F7)"><datalist id="srchList"></datalist><span id="msg"></span><span id="share"></span></div>
<div class="main">
<div class="pad no-print">
<button onclick="pNum('1')">1</button><button onclick="pNum('2')">2</button><button onclick="pNum('3')">3</button>
<button onclick="pNum('4')">4</button><button onclick="pNum('5')">5</button><button onclick="pNum('6')">6</button>
<button onclick="pNum('7')">7</button><button onclick="pNum('8')">8</button><button onclick="pNum('9')">9</button>
<button onclick="pNum('-')">-</button><button onclick="pNum('0')">0</button><button onclick="pNum('.')">.</button>
<button class="c" onclick="pClear()">C</button>
<div class="pbox" id="pBuyBox"><span style="display:inline-flex;align-items:center;gap:3px">سعر الشراء<a href="javascript:void(0)" id="buyEye" onclick="toggleBuyPrice()" aria-label="إخفاء أو إظهار سعر الشراء" style="text-decoration:none;font-size:14px;line-height:1">👁️</a></span><b id="pBuy">0</b></div>
<div class="pbox">سعر البيع<b id="pSale">0</b></div>
<div class="pbox">سعر الجملة<b id="pWhole">0</b></div>
</div>
<div class="center">
<div class="bluebar">
<div class="r">
<span><label>كود</label><br><input id="fCode" inputmode="numeric" style="width:80px;background:#fef08a;font-weight:800" onchange="pickCode(this.value)"></span>
<span><label>الصنف</label><br><input id="fItem" readonly style="width:200px"></span>
<span class="no-print"><br><button class="tbtn" onclick="addLine()">⬇</button> <button class="tbtn" onclick="openItemCard()" title="إضافة صنف جديد" style="background:#7c3aed;color:#fff;font-weight:800">＋ صنف</button></span>
</div>
<div class="r">
<span style="flex:1"><label>العميل — بحث بالكود/الموبايل</label><br><div style="display:flex;gap:4px"><input id="custSearch" oninput="searchCust(this.value)" onkeydown="if(event.key==='Enter'){event.preventDefault();pickFirstCust()}" placeholder="كود أو موبايل أو اسم..." style="width:150px;background:#fef08a;font-weight:700"><select id="fCust" style="flex:1"><option value="0">عميل نقدي</option><?php foreach ($custs as $c): ?><option value="<?=$c['id']?>" data-tel="<?=e($c['mobile'] ?? '')?><?=e($c['tel'] ?? '')?>"><?=($c['id'])?> - <?=e($c['name'])?></option><?php endforeach; ?></select></div></span>
<span class="no-print"><br><button class="tbtn" onclick="custAdd()" title="عميل جديد سريع">＋</button></span>
<span id="loyaltyBox" style="display:none;background:linear-gradient(135deg,#fef9c3,#fde68a);border:2px solid #eab308;border-radius:10px;padding:4px 10px;align-items:center;gap:6px;flex-wrap:wrap"><label style="font-weight:800;color:#854d0e;font-size:12px;white-space:nowrap">⭐ نقاطي: <b id="loyPts" style="color:#7c3aed;font-size:15px">0</b> نقطة = <b id="loyCash" style="color:#16a34a;font-size:15px">0</b> ج</label><input id="loyUse" type="number" min="0" value="0" style="width:60px;background:#fff;border-color:#eab308;font-weight:700;text-align:center" placeholder="عدد"><button class="tbtn" onclick="applyLoyalty()" style="background:#eab308;color:#fff;font-weight:800;border:none;padding:5px 12px" title="خصم النقاط من الفاتورة">⭐ استبدال</button></span>
<span><label>البائع</label><br><select id="fSman" style="width:110px"><option value="0">—</option><?php foreach ($smans as $s): ?><option value="<?=$s['id']?>"><?=e($s['name'])?></option><?php endforeach; ?></select></span>
<span><label>الدفع</label><br><select id="fPay" style="width:90px" onchange="document.getElementById('fAccW').style.display=this.value==1?'none':'"><?php $pi = [1 => '💵', 2 => '💳', 3 => '📱', 4 => '⚡']; foreach (cfg_pay_methods() as $p): ?><option value="<?=$p['id']?>"><?= ($pi[(int)$p['id']] ?? '⚙️') . ' ' . e($p['name']) ?></option><?php endforeach; ?></select></span>
<span id="fAccW" style="display:none"><label>الحساب</label><br><select id="fAcc" style="width:120px"><option value="0">—</option><?php foreach ($bnks as $b): ?><option value="<?=$b['id']?>"><?=e($b['name'])?></option><?php endforeach; ?></select></span>
<span class="no-print"><br><button class="tbtn" id="dlvToggle" onclick="toggleDlv()" title="توصيل / حجز — بيانات المستلم والعنوان" style="background:#0ea5e9;color:#fff;font-weight:800;border-color:#0284c7">🚚 توصيل/حجز</button></span>
</div>
<div id="dlvPanel" style="display:none;background:linear-gradient(135deg,#e0f2fe,#bae6fd);border:2px solid #0284c7;border-radius:8px;padding:6px 8px;margin-top:4px">
<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">
<span><label style="font-size:11px;font-weight:700;color:#075985">📦 اسم المستلم</label><br><input id="dlvName" style="width:150px;background:#fff;border-color:#0284c7;font-weight:700" placeholder="اسم المستلم"></span>
<span><label style="font-size:11px;font-weight:700;color:#075985">📱 تليفون</label><br><input id="dlvTel" style="width:110px;background:#fff;border-color:#0284c7" placeholder="01xxxxxxxxx"></span>
<span><label style="font-size:11px;font-weight:700;color:#075985">📱 تليفون 2</label><br><input id="dlvTel2" style="width:110px;background:#fff;border-color:#0284c7" placeholder="اختياري"></span>
<span style="flex:1;min-width:180px"><label style="font-size:11px;font-weight:700;color:#075985">📍 عنوان التوصيل</label><br><input id="dlvAdd" style="width:100%;background:#fff;border-color:#0284c7" placeholder="العنوان بالتفصيل"></span>
</div>
<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-top:4px">
<span><label style="font-size:11px;font-weight:700;color:#075985">📅 تاريخ التوصيل/الحجز</label><br><input id="dlvDate" type="date" style="background:#fff;border-color:#0284c7"></span>
<span><label style="font-size:11px;font-weight:700;color:#075985">📤 تاريخ الذهاب</label><br><input id="dlvGoes" type="date" style="background:#fff;border-color:#0284c7"></span>
<span><label style="font-size:11px;font-weight:700;color:#075985">📥 تاريخ العودة</label><br><input id="dlvCome" type="date" style="background:#fff;border-color:#0284c7"></span>
<span style="flex:1;min-width:160px"><label style="font-size:11px;font-weight:700;color:#075985">📝 ملاحظات التوصيل</label><br><input id="dlvNotes" style="width:100%;background:#fff;border-color:#0284c7" placeholder="أي تفاصيل إضافية..."></span>
<span><button class="tbtn" onclick="clearDlv()" style="background:#fef2f2;border-color:#f87171;color:#b91c1c;font-weight:700">مسح</button></span>
</div>
</div>
<div class="r">
<span><label>الكمية</label><br><input id="lQty" type="number" value="1" style="width:70px"></span>
<span><label>السعر</label><br><input id="lPrice" type="number" value="0" style="width:80px"></span>
<span><label>الإجمالي</label><br><input id="lTot" readonly style="width:90px"></span>
<span><label>خصم%</label><br><input id="lDiscP" type="number" value="0" style="width:55px;background:#fecaca"></span>
<span><label>خصم</label><br><input id="lDisc" type="number" value="0" style="width:70px"></span>
<span><label>رصيد المخزن = <b id="stockLbl">0</b></label></span>
</div>
</div>
<table class="grid"><tr><th>م</th><th>الصنف</th><th>كمية</th><th>الكمية الإجمالية</th><th>سعر</th><th>إجمالي</th><th>خصم</th><th>خصم%</th><th>إجمالي بعد الخصم</th><th>الصافي</th><th class="no-print"></th></tr><tbody id="rows"></tbody></table>
</div>
</div>
<div class="bottom">
<div class="tots">
<div class="trow"><label>توزيع خصم %</label><input id="distP" type="number" value="0"></div>
<div class="trow"><label>- إجمالي خصم</label><input id="hdisc" type="number" value="0"></div>
<div class="trow"><label>إجمالي ض %</label><input value="0" readonly></div>
<div class="trow"><label>+الضريبة</label><input value="0" readonly></div>
<div class="trow"><label>إجمالي المبيعات</label><input id="tSales" readonly></div>
<div class="trow"><label>إجمالي بعد الخصم</label><input id="tAfter" readonly class="net"></div>
<div class="trow"><label>الصافي</label><input id="tNet" readonly class="net"></div>
<div class="trow"><label>استلام / متبقي</label><input id="paid" type="number" value="0"><input id="tRest" readonly></div>
<div class="trow no-print" style="margin-top:4px"><button type="button" onclick="showProfit()" style="flex:1;padding:7px 4px;border:1px solid #f59e0b;border-radius:4px;background:#fef3c7;color:#92400e;font-family:inherit;font-weight:800;cursor:pointer">أرباح الفاتورة</button></div>
<div class="trow no-print" id="profitRow" style="display:none"><label>إجمالي الأرباح</label><input id="tProfit" readonly style="background:#fef3c7;font-weight:800"></div>
</div>
<div class="mid"><div class="payrow">
<span><label>رقم الفاتورة</label><br><input id="invNo" value="<?=$next?>" onchange="loadInv()" style="font-weight:800"></span>
<span><label>استلام</label><br><input id="paid2" type="number" value="0" oninput="document.getElementById('paid').value=this.value;render()"></span>
<span><label>متبقي</label><br><input id="tRest2" readonly></span>
<span style="margin-right:auto;display:flex;gap:6px">
<button id="btnPay" onclick="saveInv(0)">سداد فاتورة (F9)</button>
<button id="btnHold" onclick="saveInv(1)">تعليق فاتورة</button>
<button class="tbtn" onclick="newInv()">🆕</button>
<button class="tbtn" onclick="delInv()">🗑️</button>
<button class="tbtn" onclick="waShare()" title="مشاركة الفاتورة الحالية واتساب" style="background:#25D366;color:#fff;border-color:#128C7E">📲 واتساب</button>
<select id="suspList" onchange="if(this.value){document.getElementById('invNo').value=this.value;loadInv();this.value=''}"><option value="">معلقة (<?=count($susp)?>)</option><?php foreach ($susp as $s): ?><option value="<?=$s['id']?>"><?=$s['id']?> = <?=$s['tot_net']?></option><?php endforeach; ?></select>
<button class="tbtn" onclick="document.getElementById('findPanel').style.display=document.getElementById('findPanel').style.display==='none'?'block':'none'">🔍 بحث الفواتير</button>
<span><label>🎟️ كوبون</label><br><input id="fCoup" style="width:90px" placeholder="الكود"><button class="tbtn" onclick="applyCoupon()">تطبيق</button></span>
</span>
</div>
<div id="findPanel" class="no-print" style="display:none;background:#fff;border:1px solid #94a3b8;border-radius:6px;padding:8px;margin-top:6px">
<div class="row">
<div><label>من</label><br><input id="fFrom" type="date" value="<?=date('Y-m-d')?>"></div>
<div><label>إلى</label><br><input id="fTo" type="date" value="<?=date('Y-m-d')?>"></div>
<div><br><button onclick="findInv()">عرض فواتير اليوم/الفترة</button></div>
</div>
<div id="findRes" style="max-height:220px;overflow:auto;margin-top:6px"></div>
</div></div>
</div>
<div class="toolbar no-print">
<button onclick="navInv('first')">⏮</button><button onclick="navInv('prev')">◀</button><button onclick="navInv('next')">▶</button><button onclick="navInv('last')">⏭</button>
<button onclick="printCur('A4')">🖨️ طباعة A4</button><button onclick="printCur('80')">🧾 ريسيت 80</button>
<button onclick="delInv()">🗑️ حذف</button>
<button onclick="saveInv(0)">💾 حفظ</button>
<button onclick="newInv()">➕ جديد</button>
<span style="margin-right:auto" class="small">الكميات | ملاحظات</span>
</div>
<script>
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

const $ = id => document.getElementById(id);
const PT = <?= $pt ?>;
const QUOT = <?= $quot ? '1' : '0' ?>;
let cart = [], editing = null, cur = null, padTarget = null, readonlyInv = false, selectedLine = -1, buyPriceVal = 0;
['lQty','lPrice','lDisc'].forEach(id => $(id).addEventListener('input', calcEntry));
['lQty','lPrice','lDisc','lDiscP'].forEach(id => $(id).addEventListener('focus', () => padTarget = $(id)));
function pNum(k) { if (!padTarget) padTarget = $('lQty'); padTarget.value = (padTarget.value || '') + k; padTarget.dispatchEvent(new Event('input')); padTarget.focus(); }
function pClear() { if (padTarget) { padTarget.value = ''; padTarget.dispatchEvent(new Event('input')); } }
function calcEntry() {
  const q = parseFloat($('lQty').value) || 0, p = parseFloat($('lPrice').value) || 0;
  let d = parseFloat($('lDisc').value) || 0;
  const dp = parseFloat($('lDiscP').value) || 0;
  if (dp > 0) { d = q * p * dp / 100; $('lDisc').value = d.toFixed(2); }
  $('lTot').value = (q * p - d).toFixed(2);
}
$('lDiscP').addEventListener('input', calcEntry);
let srchT = null;
$('srch').addEventListener('input', () => {
  clearTimeout(srchT);
  srchT = setTimeout(async () => {
    const v = $('srch').value.trim();
    if (v.length < 2) return;
    const r = await (await fetch('api_item.php?q=' + encodeURIComponent(v))).json();
    $('srchList').innerHTML = (r.rows || []).map(x => `<option value="${x.id} - ${esc(x.name)}">`).join('');
  }, 250);
});
$('srch').addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    const v = $('srch').value.trim().split(' - ')[0].trim();
    if (/^\d+$/.test(v)) pickCode(parseInt(v));
  }
});
async function pickCode(code) {
  code = parseInt(code) || 0;
  if (!code) return;
  const r = await (await fetch('api_item_full.php?id=' + code)).json();
  if (!r.ok) { $('msg').textContent = 'صنف غير موجود'; return; }
  cur = r.item;
  $('fCode').value = cur.id; $('fItem').value = cur.name;
  setBuyPrice(cur.purch); $('pSale').textContent = cur.sale; $('pWhole').textContent = cur.whole;
  $('stockLbl').textContent = cur.balance;
  const off = (cur.onoffer == 1 && (cur.offer || 0) > 0);
  $('lPrice').value = off ? cur.offer : cur.sale;
  $('lPrice').style.background = off ? '#bbf7d0' : '';
  $('msg').textContent = off ? ('🏷️ عرض: ' + cur.sale + ' ← ' + cur.offer) : '';
  $('lQty').value = 1; padTarget = $('lQty'); $('lQty').focus(); $('lQty').select();
  calcEntry();
}
function addLine() {
  if (!cur) return;
  const q = parseFloat($('lQty').value) || 0;
  if (q <= 0) return;
  const p = parseFloat($('lPrice').value) || 0;
  const d = parseFloat($('lDisc').value) || 0;
  const dp = parseFloat($('lDiscP').value) || 0;
  const ex = cart.find(x => x.id == cur.id && x.price == p);
  if (ex) ex.qty += q;
  else cart.push({ id: cur.id, name: cur.name, sale: cur.sale, purch: cur.purch || 0, qty: q, price: p, disc: d, discp: dp });
  $('srch').value = ''; padTarget = $('lQty');
  cur = null; selectedLine = -1;
  $('fCode').value = ''; $('fItem').value = '';
  $('stockLbl').textContent = '0';
  setBuyPrice(0); $('pSale').textContent = '0'; $('pWhole').textContent = '0';
  $('lQty').value = 1; $('lPrice').value = 0; $('lDisc').value = 0; $('lDiscP').value = 0; $('lTot').value = '';
  render(); $('srch').focus();
}
function totals() {
  let g = 0, dl = 0;
  cart.forEach(x => { g += x.qty * x.price; dl += x.disc; });
  const hd = parseFloat($('hdisc').value) || 0;
  const dp = parseFloat($('distP').value) || 0;
  let hdd = hd;
  if (dp > 0) { hdd = (g - dl) * dp / 100; $('hdisc').value = hdd.toFixed(2); }
  return { g, after: g - dl - Math.min(hdd, g - dl) };
}
function profitValue() {
  const t = totals();
  const cost = cart.reduce((s, x) => s + x.qty * (parseFloat(x.purch) || 0), 0);
  return (PT === 2 ? -1 : 1) * (t.after - cost);
}
function updateProfit() {
  if (!$('profitRow') || $('profitRow').style.display === 'none') return;
  const p = profitValue();
  const el = $('tProfit');
  el.value = p.toFixed(2);
  el.style.color = p < 0 ? '#b91c1c' : '#065f46';
}
function showProfit() {
  if (!cart.length) { $('msg').textContent = 'الفاتورة فارغة'; return; }
  const row = $('profitRow');
  if (row.style.display !== 'none') { row.style.display = 'none'; return; }
  row.style.display = 'flex';
  updateProfit();
}
function buyPriceHidden() {
  try { return localStorage.getItem('rs_hide_buy') === '1'; } catch (e) { return false; }
}
function setBuyPrice(v) {
  buyPriceVal = parseFloat(v) || 0;
  const el = $('pBuy');
  if (el) el.textContent = buyPriceHidden() ? '••••' : buyPriceVal;
}
function applyBuyPriceVis() {
  const hide = buyPriceHidden();
  const btn = $('buyEye');
  if (btn) {
    btn.style.opacity = hide ? '.45' : '1';
    btn.style.filter = hide ? 'grayscale(1)' : '';
  }
  setBuyPrice(buyPriceVal);
}
function toggleBuyPrice() {
  const hide = !buyPriceHidden();
  try { localStorage.setItem('rs_hide_buy', hide ? '1' : '0'); } catch (e) {}
  applyBuyPriceVis();
}
applyBuyPriceVis();
function selectLine(i) {
  const x = cart[i];
  if (!x) return;
  selectedLine = i;
  setBuyPrice(x.purch);
  render();
}
function removeLine(i) {
  cart.splice(i, 1);
  if (selectedLine === i) { selectedLine = -1; setBuyPrice(0); }
  else if (selectedLine > i) selectedLine--;
  render();
}
function render() {
  const t = totals();
  if (selectedLine >= cart.length) selectedLine = -1;
  $('rows').innerHTML = cart.map((x, i) => {
    const g = x.qty * x.price, net = g - x.disc;
    return `<tr style="${selectedLine === i ? 'background:#fef3c7' : ''}"><td>${i + 1}</td><td style="cursor:pointer" onclick="selectLine(${i})" title="عرض سعر شراء هذا الصنف">${esc(x.name)}</td>
      <td><input type="number" value="${x.qty}" style="width:60px" onchange="cart[${i}].qty=parseFloat(this.value)||0;render()"></td>
      <td>${x.qty}</td>
      <td><input type="number" value="${x.price}" style="width:70px" onchange="cart[${i}].price=parseFloat(this.value)||0;render()"></td>
      <td>${g.toFixed(2)}</td>
      <td><input type="number" value="${x.disc}" style="width:60px" onchange="cart[${i}].disc=parseFloat(this.value)||0;render()"></td>
      <td>${x.discp}</td><td>${net.toFixed(2)}</td><td><b>${net.toFixed(2)}</b></td><td class="no-print"><button onclick="removeLine(${i})">✕</button></td></tr>`;
  }).join('');
  $('tSales').value = t.g.toFixed(2);
  $('tAfter').value = t.after.toFixed(2);
  $('tNet').value = t.after.toFixed(2);
  const pd = parseFloat($('paid').value) || 0;
  $('tRest').value = (t.after - pd).toFixed(2);
  $('paid2').value = pd; $('tRest2').value = (t.after - pd).toFixed(2);
  updateProfit();
}
['hdisc','distP','paid'].forEach(id => $(id).addEventListener('input', render));
document.addEventListener('keydown', e => {
  if (e.key === 'F7') { e.preventDefault(); $('srch').focus(); $('srch').select(); }
  if (e.key === 'F9') { e.preventDefault(); saveInv(0); }
  if (e.key === 'Enter' && ['lQty','lPrice','lDisc','lDiscP'].includes(document.activeElement.id)) { e.preventDefault(); addLine(); }
});
function curId() { return editing || parseInt($('invNo').value) || 0; }
function printCur(f) {
  const id = curId();
  if (!id || $('invNo').value === 'جديد') { $('msg').textContent = 'احفظ الفاتورة الأول'; return; }
  window.open('print_inv.php?id=' + id + '&f=' + f + '&auto=1', '_blank');
}
function waShare() {
  const id = editing || parseInt($('invNo').value) || 0;
  if (!id || $('invNo').value === 'جديد') { $('msg').textContent = 'احفظ الفاتورة الأول'; return; }
  window.open('whatsapp.php?inv=' + id, '_blank');
}
async function custAdd() {
  const nm = prompt('اسم العميل الجديد:');
  if (!nm) return;
  const mob = prompt('الموبايل (اختياري):') || '';
  const fd = new FormData(); fd.append('name', nm); fd.append('mobile', mob);
  const r = await (await fetch('api_cust_quick.php', { method: 'POST', body: fd })).json();
  if (r.ok) { const o = document.createElement('option'); o.value = r.id; o.textContent = r.id + ' - ' + r.name; o.dataset.tel = mob; $('fCust').appendChild(o); $('fCust').value = r.id; $('fCust').dispatchEvent(new Event('change')); $('msg').style.color = '#16a34a'; $('msg').textContent = '✅ عميل ' + r.name + ' (كود ' + r.id + ')'; if ($('custSearch')) $('custSearch').value = ''; }
  else $('msg').textContent = '❌ ' + (r.error || 'فشل');
}
let couponId = 0;
async function applyCoupon() {
  const c = $('fCoup').value.trim();
  if (!c) return;
  const r = await (await fetch('api_coupon.php?code=' + encodeURIComponent(c))).json();
  if (r.ok) { couponId = r.id; $('distP').value = r.pct; render(); $('msg').style.color = '#16a34a'; $('msg').textContent = '🎟️ كوبون ' + r.pct + '%'; }
  else { couponId = 0; $('msg').style.color = '#b91c1c'; $('msg').textContent = '❌ كوبون غلط أو منتهي'; }
}
function newInv() { cart = []; editing = null; readonlyInv = false; selectedLine = -1; setBuyPrice(0); couponId = 0; if ($('fCoup')) $('fCoup').value = ''; if ($('share')) $('share').innerHTML = ''; $('invNo').value = 'جديد'; $('hdisc').value = 0; $('distP').value = 0; $('paid').value = 0; $('paid2').value = 0; $('msg').textContent = ''; $('fCust').value = 0; $('fPay').value = 1; $('fAcc').value = 0; $('fAccW').style.display = 'none'; loyaltyPts = 0; loyaltyUsed = 0; if ($('loyaltyBox')) $('loyaltyBox').style.display = 'none'; clearDlv(); render(); }
// —— توصيل / حجز ——
function toggleDlv() { const p = $('dlvPanel'); p.style.display = p.style.display === 'none' ? 'block' : 'none'; if (p.style.display === 'block' && !$('dlvName').value && $('fCust').value > 0) { const o = $('fCust').selectedOptions[0]; if (o) { $('dlvName').value = o.textContent.replace(/^\d+\s*-\s*/, ''); const t = o.dataset.tel || ''; if (t && !$('dlvTel').value) $('dlvTel').value = t.slice(0, 11); } } }
function clearDlv() { ['dlvName','dlvTel','dlvTel2','dlvAdd','dlvDate','dlvGoes','dlvCome','dlvNotes'].forEach(id => { if ($(id)) $(id).value = ''; }); const p = $('dlvPanel'); if (p) p.style.display = 'none'; }
function dlvData() { const v = id => ($(id) ? $(id).value.trim() : ''); const any = v('dlvName') || v('dlvTel') || v('dlvTel2') || v('dlvAdd') || v('dlvDate') || v('dlvGoes') || v('dlvCome') || v('dlvNotes'); return { delivery: any ? 1 : 0, dlvName: v('dlvName'), dlvTel: v('dlvTel'), dlvTel2: v('dlvTel2'), dlvAdd: v('dlvAdd'), dlvDate: v('dlvDate'), dlvGoes: v('dlvGoes'), dlvCome: v('dlvCome'), dlvNotes: v('dlvNotes') }; }
async function saveInv(temp) {
  if (readonlyInv) { $('msg').style.color = '#b91c1c'; $('msg').textContent = 'فاتورة مستوردة — للعرض فقط'; return; }
  if (!cart.length) { $('msg').textContent = 'الفاتورة فارغة'; return; }
  if (QUOT) temp = 1;
  const body = { id: editing, temp, pt: PT, lines: cart, hdisc: parseFloat($('hdisc').value) || 0, paid: parseFloat($('paid').value) || 0, cust: parseInt($('fCust').value) || 0, sman: parseInt($('fSman').value) || 0, pay: parseInt($('fPay').value) || 1, acc: parseInt($('fAcc').value) || 0, coupon: couponId, loyaltyUse: loyaltyUsed, ...dlvData() };
  const r = await (await fetch('api_sale_save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
  if (r.ok) { $('msg').style.color = '#16a34a'; $('msg').textContent = (temp ? '⏸ معلقة ' : '✅ سداد ') + r.id + (r.loyaltyEarn ? ' ⭐+' + r.loyaltyEarn + ' نقطة' : ''); $('share').innerHTML = ` <a target="_blank" href="whatsapp.php?inv=${r.id}" style="background:#25D366;color:#fff;padding:4px 12px;border-radius:6px;text-decoration:none">📲 واتساب</a>`; $('invNo').value = r.id; editing = r.id; fetchLoyalty(); if (!temp) newInv(); else location.reload(); }
  else { $('msg').style.color = '#b91c1c'; $('msg').textContent = '❌ ' + (r.error || 'فشل'); }
}
async function loadInv() {
  const id = parseInt($('invNo').value) || 0;
  if (!id) return;
  const r = await (await fetch('api_sale_load.php?id=' + id + '&pt=' + PT)).json();
  if (!r.ok) { $('msg').textContent = '❌ ' + (r.error || 'غير موجودة'); return; }
  readonlyInv = !!r.readonly; editing = id; cart = (r.lines || []).map(x => ({ id: x.id, name: x.name, purch: x.purch || 0, qty: x.qty, price: x.price, disc: x.disc || 0, discp: 0, note: '', factor: 1 }));
  $('hdisc').value = r.hdisc; $('paid').value = r.paid; $('paid2').value = r.paid;
  if (r.cust && !$('fCust').querySelector(`option[value="${r.cust}"]`)) { const o = document.createElement('option'); o.value = r.cust; o.textContent = '#' + r.cust; $('fCust').appendChild(o); }
  if (r.pay && !$('fPay').querySelector(`option[value="${r.pay}"]`)) { const o = document.createElement('option'); o.value = r.pay; o.textContent = 'طريقة ' + r.pay; $('fPay').appendChild(o); }
  $('fCust').value = r.cust || 0; $('fSman').value = r.sman || 0; $('fPay').value = r.pay || 1; $('fAcc').value = r.acc || 0; $('fAccW').style.display = ($('fPay').value == 1) ? 'none' : '';
  if (r.delivery) { $('dlvPanel').style.display = 'block'; $('dlvName').value = r.dlvName || ''; $('dlvTel').value = r.dlvTel || ''; $('dlvTel2').value = r.dlvTel2 || ''; $('dlvAdd').value = r.dlvAdd || ''; $('dlvDate').value = r.dlvDate || ''; $('dlvGoes').value = r.dlvGoes || ''; $('dlvCome').value = r.dlvCome || ''; $('dlvNotes').value = r.dlvNotes || ''; } else clearDlv();
  fetchLoyalty(); selectedLine = -1; setBuyPrice(0);
  render(); $('msg').textContent = (readonlyInv ? '📥 فاتورة مستوردة ' : '📂 فاتورة ') + id + (readonlyInv ? ' — للعرض فقط' : '');
}
async function navInv(dir) {
  const curId = editing || parseInt($('invNo').value) || 0;
  const r = await (await fetch('api_sale_nav.php?dir=' + dir + '&cur=' + curId + '&pt=' + PT)).json();
  if (r.ok) { $('invNo').value = r.id; loadInv(); }
}
async function findInv() {
  const f = $('fFrom').value, t = $('fTo').value;
  const r = await (await fetch('api_sale_find.php?from=' + f + '&to=' + t + '&pt=' + PT)).json();
  $('findRes').innerHTML = (r.rows || []).length
    ? `<table class="grid"><tr><th>فاتورة</th><th>التاريخ</th><th>الصافي</th><th>المدفوع</th><th>المصدر</th><th>الحالة</th></tr>` +
      r.rows.map(x => `<tr><td><a href="javascript:void(0)" onclick="document.getElementById('invNo').value=${x.id};loadInv()">${x.id}</a></td><td>${x.d}</td><td>${x.net}</td><td>${x.paid}</td><td>${x.branch === 1 ? 'مستورد' : 'محلي'}</td><td>${x.temp ? 'معلقة' : 'مسددة'}</td></tr>`).join('') + `</table>`
    : 'لا فواتير في الفترة';
}
async function delInv() {
  if (readonlyInv) { $('msg').style.color = '#b91c1c'; $('msg').textContent = 'فاتورة مستوردة — للعرض فقط'; return; }
  if (!editing || !confirm('حذف الفاتورة ' + editing + '؟')) return;
  const r = await (await fetch('api_sale_save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ del: editing, pt: PT }) })).json();
  if (r.ok) newInv(); else $('msg').textContent = '❌ ' + (r.error || 'فشل');
}
function searchCust(q) {
  q = q.trim();
  const sel = $('fCust');
  const isNum = /^\d+$/.test(q);
  for (let i = 0; i < sel.options.length; i++) {
    const o = sel.options[i];
    if (i === 0) { o.hidden = false; continue; }
    const txt = o.textContent.toLowerCase();
    const tel = (o.dataset.tel || '').toLowerCase();
    const optId = o.value;
    if (q === '') { o.hidden = false; continue; }
    if (isNum) {
      o.hidden = optId !== q && !tel.includes(q);
    } else {
      o.hidden = !txt.includes(q.toLowerCase());
    }
  }
  if (q) { for (let i = 1; i < sel.options.length; i++) { if (!sel.options[i].hidden) { sel.selectedIndex = i; break; } } }
}
function pickFirstCust() {
  const sel = $('fCust');
  for (let i = 1; i < sel.options.length; i++) { if (!sel.options[i].hidden) { sel.selectedIndex = i; sel.dispatchEvent(new Event('change')); break; } }
}
let loyaltyPts = 0, loyaltyUsed = 0;
async function fetchLoyalty() {
  const cust = parseInt($('fCust').value) || 0;
  if (!cust) { $('loyaltyBox').style.display = 'none'; loyaltyPts = 0; loyaltyUsed = 0; return; }
  const r = await (await fetch('api_loyalty.php?act=get&cust=' + cust)).json();
  $('loyaltyBox').style.display = 'inline-flex';
  $('loyPts').textContent = r.points || 0;
  $('loyCash').textContent = r.cash || 0;
  $('loyUse').max = r.points || 0;
  loyaltyPts = r.points || 0;
}
function applyLoyalty() {
  const use = parseInt($('loyUse').value) || 0;
  if (use > loyaltyPts) { $('loyUse').value = loyaltyPts; return; }
  loyaltyUsed = use;
  const cash = (use * 0.05).toFixed(2);
  $('hdisc').value = cash;
  render();
  $('msg').style.color = '#7c3aed';
  $('msg').textContent = '⭐ خصم ' + use + ' نقطة = ' + cash + ' ج';
}
document.addEventListener('change', e => { if (e.target.id === 'fCust') { fetchLoyalty(); $('loyUse').value = 0; loyaltyUsed = 0; } });
// —— كرت صنف (تعريف أصناف: إضافة + بحث وتعديل) ——
let icEditId = 0; // 0 = جديد، >0 = تعديل
let icImgData = '';
function icImgReset(){
  icImgData = '';
  const f = $('icImgFile'); if (f) f.value = '';
  const p = $('icImgPrev'); if (p) { p.style.display = 'none'; p.src = ''; }
  const m = $('icImgMsg'); if (m) m.textContent = '';
}
function icResizeImg(file, max, q) {
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
async function icImgChange(inp) {
  const f = inp.files && inp.files[0]; if (!f) return;
  try {
    $('icImgMsg').textContent = '⏳ جاري التجهيز...';
    icImgData = await icResizeImg(f, 900, 0.82);
    $('icImgPrev').src = icImgData; $('icImgPrev').style.display = 'inline-block';
    $('icImgMsg').textContent = '✅ جاهزة (' + Math.round(icImgData.length / 1365) + ' ك.ب)';
  } catch (e) { icImgData = ''; $('icImgMsg').textContent = '❌ ' + e.message; }
}
function icSwitchTab(tab) {
  $('icTabNew').className = tab==='new' ? 'ic-tab ic-tab-on' : 'ic-tab';
  $('icTabEdit').className = tab==='edit' ? 'ic-tab ic-tab-on' : 'ic-tab';
  $('icSearchBox').style.display = tab==='edit' ? 'block' : 'none';
  $('icFormTitle').textContent = tab==='edit' ? '✏️ تعديل بيانات صنف' : '📦 إضافة صنف جديد';
  if (tab==='edit') { $('icSearchQ').focus(); }
}
async function openItemCard() {
  $('itemCard').style.display = 'flex';
  icEditId = 0;
  icSwitchTab('new');
  $('icId').readOnly = true; $('icId').style.background = '#ffe';
  const r = await (await fetch('api_item_add.php?act=next')).json();
  $('icId').value = r.next || 0;
  $('icBar').value = String($('icId').value).padStart(5, '0');
  $('icName').value = ''; $('icEn').value = ''; $('icSale').value = ''; $('icCost').value = '';
  $('icWhole').value = ''; $('icDisc').value = ''; $('icTax').value = ''; $('icLim').value = ''; $('icMinp').value = '';
  $('icNotes').value = ''; $('icMsg').textContent = '';
  $('icGrp').selectedIndex = 0; $('icUnit').selectedIndex = 0; $('icType').value = 1;
  $('icSearchQ').value = ''; $('icSearchRes').innerHTML = '';
  $('icBal').textContent = '';
  icImgReset(); $('icEo').checked = true;
  $('icName').focus();
}
function closeItemCard() { $('itemCard').style.display = 'none'; }
async function icSearchItems() {
  const q = $('icSearchQ').value.trim();
  if (!q) { $('icSearchRes').innerHTML = ''; return; }
  const r = await (await fetch('api_item_add.php?act=search&q=' + encodeURIComponent(q))).json();
  if (!r.ok || !r.rows.length) { $('icSearchRes').innerHTML = '<div style="padding:10px;color:#999;text-align:center">لا يوجد صنف مطابق</div>'; return; }
  $('icSearchRes').innerHTML = '<table style="width:100%;border-collapse:collapse;font-size:12px">' +
    '<tr style="background:#e5e7eb"><th style="padding:4px;border:1px solid #ccc">كود</th><th style="padding:4px;border:1px solid #ccc">الصنف</th><th style="padding:4px;border:1px solid #ccc">المجموعة</th><th style="padding:4px;border:1px solid #ccc">بيع</th><th style="padding:4px;border:1px solid #ccc">شراء</th><th style="padding:4px;border:1px solid #ccc">الرصيد</th><th style="padding:4px;border:1px solid #ccc"></th></tr>' +
    r.rows.map(x => '<tr style="cursor:pointer" onmouseover="this.style.background=\'#fef9c3\'" onmouseout="this.style.background=\'#fff\'">' +
      '<td style="padding:4px;border:1px solid #ddd;text-align:center;font-weight:700">' + x.id + '</td>' +
      '<td style="padding:4px;border:1px solid #ddd">' + x.symbol + '</td>' +
      '<td style="padding:4px;border:1px solid #ddd;text-align:center">' + (x.gname||'-') + '</td>' +
      '<td style="padding:4px;border:1px solid #ddd;text-align:center;color:#1e40af;font-weight:700">' + x.sale_price + '</td>' +
      '<td style="padding:4px;border:1px solid #ddd;text-align:center;color:#166534;font-weight:700">' + x.purch_price + '</td>' +
      '<td style="padding:4px;border:1px solid #ddd;text-align:center;font-weight:700;color:' + (x.bal<=0?'#dc2626':(x.bal<=1?'#d97706':'#16a34a')) + '">' + x.bal + '</td>' +
      '<td style="padding:4px;border:1px solid #ddd;text-align:center"><button onclick="icLoadItem(' + x.id + ')" style="padding:2px 10px;background:#7c3aed;color:#fff;border:none;border-radius:3px;cursor:pointer;font-family:inherit;font-weight:700">تعديل</button></td></tr>').join('') + '</table>';
}
async function icLoadItem(id) {
  const r = await (await fetch('api_item_add.php?act=get&id=' + id)).json();
  if (!r.ok || !r.item) { $('icMsg').textContent = '❌ لم يُعثر على الصنف'; return; }
  const it = r.item;
  icEditId = it.id;
  $('icId').value = it.id; $('icBar').value = it.barcode || '';
  $('icName').value = it.symbol || ''; $('icEn').value = it.nameEN || '';
  $('icSale').value = it.sale_price || 0; $('icCost').value = it.purch_price || 0;
  $('icWhole').value = it.whole_price || 0; $('icDisc').value = it.discount || 0; $('icTax').value = it.tax || 0;
  $('icLim').value = it.limit || 0; $('icMinp').value = it.minprice || 0;
  $('icNotes').value = it.notes || '';
  $('icGrp').value = it.group_id || 0; $('icUnit').value = it.unit || 1; $('icType').value = it.type || 1;
  $('icBal').textContent = it.bal || 0;
  $('icFormTitle').textContent = '✏️ تعديل: ' + it.symbol + ' (كود ' + it.id + ')';
  $('icMsg').textContent = '';
  icImgReset();
  if (it.imge) { $('icImgMsg').textContent = 'الصورة الحالية: ' + it.imge; }
}
async function saveNewItem() {
  const nm = $('icName').value.trim();
  if (!nm) { $('icMsg').textContent = '❌ اسم الصنف مطلوب'; return; }
  const body = {
    id: icEditId, name: nm, en: $('icEn').value.trim(),
    sale: parseFloat($('icSale').value) || 0, cost: parseFloat($('icCost').value) || 0,
    whole: parseFloat($('icWhole').value) || 0, disc: parseFloat($('icDisc').value) || 0, tax: parseFloat($('icTax').value) || 0,
    lim: parseFloat($('icLim').value) || 0, minp: parseFloat($('icMinp').value) || 0,
    barcode: $('icBar').value.trim(), grp: parseInt($('icGrp').value) || 0,
    unit: parseInt($('icUnit').value) || 1, type: parseInt($('icType').value) || 1,
    notes: $('icNotes').value.trim(),
    img: icImgData, eo: ($('icEo').checked ? 1 : 0)
  };
  $('icMsg').textContent = '⏳ جاري الحفظ...';
  const r = await (await fetch('api_item_add.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
  if (r.ok) {
    let m = r.edited ? ('✅ اتعدل — كود: ' + r.id) : ('✅ اتسجل — كود: ' + r.id);
    if (r.img && !r.img.ok) m += ' | ⚠️ صورة: ' + (r.img.error || 'فشل');
    if (body.eo) {
      if (r.eo && r.eo.ok) m += r.eo.skipped ? ' | ℹ️ مرفوع بالفعل' : ' | 🚀 اترفع على الموقع';
      else m += ' | ⚠️ الرفع فشل: ' + ((r.eo && r.eo.error) || r.eo_error || 'خطأ');
    }
    $('icMsg').textContent = m;
    const wait = (body.eo && !(r.eo && r.eo.skipped)) ? 2200 : 900;
    if (!icEditId) {
      $('fCode').value = r.id; $('fItem').value = nm;
      $('lPrice').value = body.sale; $('lQty').value = 1;
      $('stockLbl').textContent = '0';
      setBuyPrice(body.cost); $('pSale').textContent = body.sale; $('pWhole').textContent = body.whole || body.sale;
      setTimeout(() => { closeItemCard(); $('lQty').focus(); $('lQty').select(); }, wait);
    } else {
      setTimeout(() => { closeItemCard(); }, wait);
    }
  } else {
    $('icMsg').textContent = '❌ ' + (r.error || 'خطأ في الحفظ');
  }
}
render();
</script>
<!-- بوب-اب كرت صنف (تعريف أصناف) -->
<div id="itemCard" style="display:none;position:fixed;inset:0;background:#0008;z-index:999;align-items:center;justify-content:center">
<div style="background:#f0f0f0;border:2px solid #555;border-radius:4px;width:760px;max-width:96vw;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px #0008;font-family:Tahoma,Arial;font-size:13px">
<!-- شريط عنوان -->
<div style="background:linear-gradient(180deg,#e8e8e8,#c8c8c8);border-bottom:1px solid #999;padding:6px 10px;display:flex;justify-content:space-between;align-items:center">
<b style="font-size:14px">📦 تعريف الأصناف</b>
<button onclick="closeItemCard()" style="background:#dc2626;color:#fff;border:1px solid #991b1b;border-radius:3px;padding:3px 12px;cursor:pointer;font-weight:700;font-family:inherit">✕</button>
</div>
<!-- تبويبات -->
<div style="display:flex;gap:0;border-bottom:2px solid #999;background:#ddd">
<button id="icTabNew" class="ic-tab ic-tab-on" onclick="icSwitchTab('new')">➕ إضافة جديدة</button>
<button id="icTabEdit" class="ic-tab" onclick="icSwitchTab('edit')">🔍 بحث وتعديل</button>
</div>
<style>.ic-tab{padding:8px 24px;border:none;background:#ddd;border-bottom:3px solid transparent;cursor:pointer;font-family:inherit;font-size:14px;font-weight:700;color:#555;transition:all .15s}.ic-tab-on{background:#f0f0f0;border-bottom:3px solid #7c3aed;color:#7c3aed}</style>
<!-- بحث وتعديل -->
<div id="icSearchBox" style="display:none;padding:10px 12px 0">
<div style="display:flex;gap:8px;align-items:center">
<input id="icSearchQ" oninput="if(this.value.length>=2||this.value.length===0)icSearchItems()" onkeydown="if(event.key==='Enter'){event.preventDefault();icSearchItems()}" placeholder="🔍 بحث بالكود أو الاسم أو الباركود..." style="flex:1;padding:7px;border:2px solid #7c3aed;border-radius:4px;font-size:14px;font-family:inherit">
<button onclick="icSearchItems()" style="padding:7px 18px;background:#7c3aed;color:#fff;border:none;border-radius:4px;cursor:pointer;font-family:inherit;font-weight:700">بحث</button>
</div>
<div id="icSearchRes" style="max-height:200px;overflow-y:auto;margin-top:8px"></div>
</div>
<!-- الجسم -->
<div style="padding:10px 12px">
<div id="icFormTitle" style="font-weight:800;font-size:14px;color:#374151;margin-bottom:8px">📦 إضافة صنف جديد</div>
<!-- الصف العلوي: رقم + باركود + رصيد -->
<div style="display:flex;gap:10px;margin-bottom:8px;align-items:end;flex-wrap:wrap">
<div><label style="font-weight:700">رقم الصنف</label><br><input id="icId" readonly style="width:90px;padding:5px;border:1px solid #999;background:#ffe;font-weight:800;font-size:15px;text-align:center"></div>
<div><label style="font-weight:700">الباركود</label><br><input id="icBar" style="width:120px;padding:5px;border:1px solid #999;font-weight:700"></div>
<div><button onclick="$('icBar').value=String($('icId').value).padStart(5,'0')" style="padding:4px 10px;border:1px solid #888;background:#e8e8e8;border-radius:3px;cursor:pointer;font-family:inherit">توليد</button></div>
<div id="icBalW" style="display:none"><label style="font-weight:700">الرصيد الحالي</label><br><b id="icBal" style="padding:5px;display:inline-block;border:1px solid #999;background:#ecfdf5;font-size:15px;min-width:60px;text-align:center"></b></div>
</div>
<!-- الصف الأساسي -->
<div style="display:flex;gap:10px;margin-bottom:8px;flex-wrap:wrap">
<div style="flex:2"><label style="font-weight:700;color:#000">الصنف *</label><br><input id="icName" style="width:100%;padding:6px;border:1px solid #999;font-size:14px;font-weight:700"></div>
<div style="flex:1"><label style="font-weight:700">الصنف (EN)</label><br><input id="icEn" dir="ltr" style="width:100%;padding:6px;border:1px solid #999"></div>
</div>
<!-- المجموعة + النوع -->
<div style="display:flex;gap:10px;margin-bottom:8px;flex-wrap:wrap">
<div><label style="font-weight:700">مجموعة الصنف</label><br><select id="icGrp" style="width:160px;padding:5px;border:1px solid #999"><?php foreach ($groups as $g): ?><option value="<?=$g['id']?>"><?=e($g['name'])?></option><?php endforeach; ?></select></div>
<div><label style="font-weight:700">الوحدة الافتراضية</label><br><select id="icUnit" style="width:120px;padding:5px;border:1px solid #999"><?php foreach ($units as $u): ?><option value="<?=$u['id']?>"><?=e($u['name'])?></option><?php endforeach; ?></select></div>
<div><label style="font-weight:700">نوع الصنف</label><br><select id="icType" style="width:100px;padding:5px;border:1px solid #999"><option value="1">مخزني</option><option value="2">خدمي</option></select></div>
</div>
<!-- الأسعار -->
<div style="background:#fff;border:1px solid #999;border-radius:4px;padding:10px;margin-bottom:8px">
<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
<div><label style="font-weight:700;color:#166534">سعر الشراء</label><br><input id="icCost" type="number" step="0.01" value="0" style="width:90px;padding:5px;border:1px solid #999"></div>
<div><label style="font-weight:700;color:#991b1b">بيع %</label><br><input id="icDisc" type="number" step="0.01" value="0" style="width:60px;padding:5px;border:1px solid #999;background:#fecaca"></div>
<div><label style="font-weight:700;color:#991b1b">ضريبة %</label><br><input id="icTax" type="number" step="0.01" value="0" style="width:60px;padding:5px;border:1px solid #999"></div>
<div><label style="font-weight:700;color:#1e40af">سعر البيع</label><br><input id="icSale" type="number" step="0.01" value="0" style="width:90px;padding:5px;border:1px solid #999"></div>
<div><label style="font-weight:700">سعر الجملة</label><br><input id="icWhole" type="number" step="0.01" value="0" style="width:90px;padding:5px;border:1px solid #999"></div>
<div><label style="font-weight:700">الحد الأدنى للكمية</label><br><input id="icLim" type="number" step="0.01" value="0" style="width:80px;padding:5px;border:1px solid #999"></div>
<div><label style="font-weight:700">أقل سعر بيع</label><br><input id="icMinp" type="number" step="0.01" value="0" style="width:80px;padding:5px;border:1px solid #999"></div>
</div>
</div>
<!-- ملاحظة -->
<div style="margin-bottom:8px"><label style="font-weight:700">ملاحظة</label><br><textarea id="icNotes" rows="2" style="width:100%;padding:5px;border:1px solid #999;border-radius:3px;font-family:inherit"></textarea></div>
<!-- صورة + رفع على الموقع -->
<div style="background:#f5f3ff;border:1px solid #d8b4fe;border-radius:4px;padding:8px;margin-bottom:8px">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <b>🖼️ صورة الصنف</b>
    <input type="file" id="icImgFile" accept="image/jpeg,image/png,image/webp" onchange="icImgChange(this)" style="font-family:inherit">
    <span id="icImgMsg" style="color:#6b21a8;font-weight:700;font-size:12px"></span>
    <img id="icImgPrev" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #ccc;display:none">
  </div>
  <label style="display:flex;align-items:center;gap:6px;margin-top:6px;font-weight:800;color:#166534">
    <input type="checkbox" id="icEo" checked style="width:16px;height:16px"> رفع الصنف على إيزي أوردر بعد الحفظ
  </label>
</div>
<!-- رسالة + أزرار -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px">
<div id="icMsg" style="font-size:13px;font-weight:700"></div>
<div style="display:flex;gap:8px">
<button onclick="closeItemCard()" style="padding:8px 20px;border:1px solid #888;background:#e8e8e8;border-radius:4px;cursor:pointer;font-family:inherit;font-weight:700">إلغاء</button>
<button id="icSaveBtn" onclick="saveNewItem()" style="padding:8px 24px;border:1px solid #15803d;background:linear-gradient(180deg,#22c55e,#16a34a);color:#fff;border-radius:4px;cursor:pointer;font-family:inherit;font-weight:800;font-size:14px">💾 حفظ الصنف</button>
</div>
</div>
</div>
</div>
</div>
</body></html>
