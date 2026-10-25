<?php
// pages/buy.php — فاتورة مشتريات طبق الأصل (الشريط الأزرق + الصلاحية + سعر بيع)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
$susp = $db->query("SELECT id, DATE(date) d, tot_net FROM inv WHERE branch=99 AND IS_Buy=1 AND proc_type=1 AND IS_Deleted=0 AND IS_Temp=1 ORDER BY id DESC LIMIT 20")->fetchAll();
$next = (int)$db->query("SELECT IFNULL(MAX(id),0)+1 n FROM inv")->fetch()['n'];
layout_open('فاتورة مشتريات', 'buy');
?>
<style>
.inv-head{background:#93c5fd;border-radius:10px;padding:10px 12px;margin-bottom:10px}
.inv-head .row{gap:8px}.inv-head label{color:#172554;font-weight:700}
.inv-head input,.inv-head select{background:#eff6ff;border:1px solid #2563eb}
.grid-wrap{overflow-x:auto;background:#fff;border-radius:10px}
table.grid{width:100%;border-collapse:collapse;font-size:13px}
table.grid th{background:#f8fafc;border:1px solid #e2e8f0;padding:6px}
table.grid td{border:1px solid #e2e8f0;padding:4px 6px;text-align:center}
.foot{background:#fff;border-radius:10px;padding:10px;margin-top:10px}
.net{background:#93c5fd;font-weight:800;font-size:18px}
.btn-pay{background:#16a34a;font-size:18px;padding:12px 26px}
.btn-hold{background:#e2e8f0;color:#0f172a}
</style>
<div class="inv-head"><div class="row">
<div><label>البحث</label><br><input id="srch" list="srchList" autocomplete="off" style="width:200px" placeholder="اسم / كود (F7)"><datalist id="srchList"></datalist></div>
<div><label>كود</label><br><input id="fCode" inputmode="numeric" style="width:90px" onchange="pickCode(this.value)"></div>
<div><label>المجموعة</label><br><select id="fGroup"><?php foreach ($groups as $g): ?><option value="<?=$g['id']?>"><?=e($g['name'])?></option><?php endforeach; ?></select></div>
<div><label>الصنف</label><br><input id="fItem" readonly style="width:180px"></div>
<div><label>رصيد المخزن = <span id="stockLbl">0</span></label><br><input id="fNote" placeholder="ملاحظة" style="width:150px"></div>
<div><label>الصلاحية</label><br><input id="lExp" type="date" style="width:140px"></div>
</div><div class="row" style="margin-top:8px">
<div><label>الكمية</label><br><input id="lQty" type="number" value="1" style="width:80px"></div>
<div><label>التكلفة</label><br><input id="lPrice" type="number" value="0" style="width:90px"></div>
<div><label>الإجمالي</label><br><input id="lTot" readonly style="width:90px"></div>
<div><label>خصم</label><br><input id="lDisc" type="number" value="0" style="width:70px"></div>
<div><br><button onclick="addLine()">⬇ إضافة</button> <span id="pInfo" class="small"></span></div>
<div style="margin-right:auto"><label>رقم الفاتورة</label><br><input id="invNo" style="width:100px" value="<?=$next?>" onchange="loadInv()"></div>
</div></div>
<div class="grid-wrap"><table class="grid"><tr><th>م</th><th>الصنف</th><th>كمية</th><th>تكلفة</th><th>إجمالي</th><th>خصم</th><th>إجمالي بعد الخصم</th><th>سعر بيع</th><th>صلاحية</th><th>ملاحظات</th><th></th></tr><tbody id="rows"></tbody></table></div>
<div class="foot"><div class="row">
<div><label>إجمالي المشتريات</label><br><input id="tSales" readonly style="width:110px"></div>
<div><label>- إجمالي خصم</label><br><input id="hdisc" type="number" value="0" style="width:90px"></div>
<div><label>إجمالي بعد الخصم</label><br><input id="tAfter" readonly style="width:110px"></div>
<div><label>الصافي</label><br><input id="tNet" readonly class="net" style="width:120px"></div>
<div><label>المدفوع</label><br><input id="paid" type="number" value="0" style="width:100px"></div>
<div><label>متبقي</label><br><input id="tRest" readonly style="width:100px"></div>
</div><div class="row" style="margin-top:10px">
<button class="btn-pay" onclick="saveInv(0)">سداد فاتورة (F9)</button>
<button class="btn-hold" onclick="saveInv(1)">تعليق فاتورة</button>
<button class="ghost" onclick="newInv()">🆕 جديد</button>
<button class="danger" onclick="delInv()">🗑️ حذف</button>
<select id="suspList" onchange="if(this.value){document.getElementById('invNo').value=this.value;loadInv();this.value=''}"><option value="">— فواتير معلقة (<?=count($susp)?>) —</option><?php foreach ($susp as $s): ?><option value="<?=$s['id']?>"><?=$s['id']?> (<?=$s['d']?>) = <?=$s['tot_net']?></option><?php endforeach; ?></select>
<span id="msg"></span>
</div></div>
<script>
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

const $ = id => document.getElementById(id);
let cart = [], editing = null, cur = null;
function calcEntry() {
  const q = parseFloat($('lQty').value) || 0, p = parseFloat($('lPrice').value) || 0, d = parseFloat($('lDisc').value) || 0;
  $('lTot').value = (q * p - d).toFixed(2);
}
['lQty','lPrice','lDisc'].forEach(id => $(id).addEventListener('input', calcEntry));
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
  const r = await (await fetch('api_buy_item.php?id=' + code)).json();
  if (!r.ok) { $('msg').textContent = 'صنف غير موجود'; return; }
  cur = r.item;
  $('fCode').value = cur.id; $('fItem').value = cur.name;
  $('fGroup').value = cur.group_id;
  $('stockLbl').textContent = cur.balance;
  $('pInfo').textContent = `شراء ${cur.purch} / بيع ${cur.sale}`;
  $('lPrice').value = cur.purch; $('lQty').value = 1; $('lQty').focus(); $('lQty').select();
  calcEntry();
}
function addLine() {
  if (!cur) return;
  const q = parseFloat($('lQty').value) || 0;
  if (q <= 0) return;
  const p = parseFloat($('lPrice').value) || 0;
  const d = parseFloat($('lDisc').value) || 0;
  const ex = cart.find(x => x.id == cur.id && x.price == p);
  if (ex) ex.qty += q;
  else cart.push({ id: cur.id, name: cur.name, sale: cur.sale, qty: q, price: p, disc: d, exp: $('lExp').value, note: $('fNote').value });
  $('srch').value = ''; $('fNote').value = ''; $('srch').focus();
  render();
}
function totals() {
  let g = 0, dl = 0;
  cart.forEach(x => { g += x.qty * x.price; dl += x.disc; });
  const hd = parseFloat($('hdisc').value) || 0;
  return { g, after: g - dl - Math.min(hd, g - dl) };
}
function render() {
  const t = totals();
  $('rows').innerHTML = cart.map((x, i) => {
    const g = x.qty * x.price, net = g - x.disc;
    return `<tr><td>${i + 1}</td><td>${esc(x.name)}</td><td><input type="number" value="${x.qty}" style="width:60px" onchange="cart[${i}].qty=parseFloat(this.value)||0;render()"></td><td><input type="number" value="${x.price}" style="width:80px" onchange="cart[${i}].price=parseFloat(this.value)||0;render()"></td><td>${g.toFixed(2)}</td><td><input type="number" value="${x.disc}" style="width:60px" onchange="cart[${i}].disc=parseFloat(this.value)||0;render()"></td><td>${net.toFixed(2)}</td><td>${x.sale}</td><td style="font-size:12px">${x.exp||''}</td><td style="font-size:12px">${esc(x.note||'')}</td><td><button class="danger" onclick="cart.splice(${i},1);render()">✕</button></td></tr>`;
  }).join('');
  $('tSales').value = t.g.toFixed(2);
  $('tAfter').value = t.after.toFixed(2);
  $('tNet').value = t.after.toFixed(2);
  $('tRest').value = (t.after - (parseFloat($('paid').value) || 0)).toFixed(2);
}
['hdisc','paid'].forEach(id => $(id).addEventListener('input', render));
document.addEventListener('keydown', e => {
  if (e.key === 'F7') { e.preventDefault(); $('srch').focus(); $('srch').select(); }
  if (e.key === 'F9') { e.preventDefault(); saveInv(0); }
});
function newInv() { cart = []; editing = null; $('invNo').value = 'جديد'; $('hdisc').value = 0; $('paid').value = 0; $('msg').textContent = ''; render(); }
async function saveInv(temp) {
  if (!cart.length) { $('msg').textContent = 'الفاتورة فارغة'; return; }
  const body = { id: editing, temp, lines: cart, hdisc: parseFloat($('hdisc').value) || 0, paid: parseFloat($('paid').value) || 0 };
  const r = await (await fetch('api_buy_save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
  if (r.ok) { $('msg').style.color = '#16a34a'; $('msg').textContent = (temp ? '⏸ معلقة ' : '✅ سداد ') + r.id; $('invNo').value = r.id; editing = r.id; if (!temp) newInv(); else location.reload(); }
  else { $('msg').style.color = '#dc2626'; $('msg').textContent = '❌ ' + (r.error || 'فشل'); }
}
async function loadInv() {
  const id = parseInt($('invNo').value) || 0;
  if (!id) return;
  const r = await (await fetch('api_buy_load.php?id=' + id)).json();
  if (!r.ok) { $('msg').textContent = '❌ ' + (r.error || 'غير موجودة'); return; }
  editing = id; cart = r.lines;
  $('hdisc').value = r.hdisc; $('paid').value = r.paid;
  render(); $('msg').textContent = '📂 فاتورة ' + id;
}
async function delInv() {
  if (!editing || !confirm('حذف الفاتورة ' + editing + '؟')) return;
  const r = await (await fetch('api_buy_save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ del: editing }) })).json();
  if (r.ok) newInv(); else $('msg').textContent = '❌ ' + (r.error || 'فشل');
}
render();
</script>
<?php layout_close(); ?>
