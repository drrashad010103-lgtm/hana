<?php
// pages/sale.php — فاتورة بيع طبق الأصل من شاشة RS (الشريط الأخضر + نفس الحقول)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$groups = $db->query("SELECT id, name FROM `groups` ORDER BY id")->fetchAll();
$custs = $db->query("SELECT id, name FROM customers WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY id")->fetchAll();
$men = $db->query("SELECT id, name FROM salesmen WHERE IS_Deleted=0 OR IS_Deleted IS NULL ORDER BY id")->fetchAll();
$susp = $db->query("SELECT id, DATE(date) d, tot_net FROM inv WHERE branch=99 AND IS_Buy=0 AND proc_type=1 AND IS_Deleted=0 AND IS_Temp=1 ORDER BY id DESC LIMIT 20")->fetchAll();
$next = (int)$db->query("SELECT IFNULL(MAX(id),0)+1 n FROM inv")->fetch()['n'];
layout_open('فاتورة مبيعات', 'sale');
?>
<style>
.inv-head{background:#4ade80;border-radius:10px;padding:10px 12px;margin-bottom:10px}
.inv-head .row{gap:8px}
.inv-head label{color:#052e16;font-weight:700}
.inv-head input,.inv-head select{background:#fefce8;border:1px solid #16a34a}
#stockLbl{font-weight:800;color:#052e16}
.grid-wrap{overflow-x:auto;background:#fff;border-radius:10px}
table.grid{width:100%;border-collapse:collapse;font-size:13px}
table.grid th{background:#f8fafc;border:1px solid #e2e8f0;padding:6px}
table.grid td{border:1px solid #e2e8f0;padding:4px 6px;text-align:center}
.sidebox{background:#fff;border-radius:10px;padding:10px;margin-bottom:10px;font-size:14px}
.sidebox .pr{display:flex;justify-content:space-between;border-bottom:1px dashed #e2e8f0;padding:4px 0}
.foot{background:#fff;border-radius:10px;padding:10px}
.foot .row{gap:8px}
.foot input{width:110px}.net{background:#4ade80;font-weight:800;font-size:18px}
.btn-pay{background:#16a34a;font-size:18px;padding:12px 26px}
.btn-hold{background:#e2e8f0;color:#0f172a}
.layout2{display:flex;gap:10px}.grow{flex:1;min-width:0}.side{width:200px;flex-shrink:0}
@media(max-width:900px){.layout2{flex-direction:column}.side{width:100%}}
</style>
<div class="inv-head">
<div class="row">
<div><label>البحث</label><br><input id="srch" list="srchList" autocomplete="off" style="width:220px" placeholder="اسم / كود / باركود (F7)"><datalist id="srchList"></datalist></div>
<div><label>كود</label><br><input id="fCode" inputmode="numeric" style="width:90px" onchange="pickCode(this.value)"></div>
<div><label>المجموعة</label><br><select id="fGroup" onchange="lockGroup()"><?php foreach ($groups as $g): ?><option value="<?=$g['id']?>"><?=e($g['name'])?></option><?php endforeach; ?></select></div>
<div><label>الصنف</label><br><input id="fItem" readonly style="width:200px"></div>
<div><label>الوحدة</label><br><select id="fUnit"></select></div>
<div><label>رصيد مخزن المحل = <span id="stockLbl">0</span> <span id="stockU">قطعة</span></label><br><input id="fNote" placeholder="ملاحظة" style="width:200px"></div>
</div>
<div class="row" style="margin-top:8px">
<div><button class="ghost" onclick="lastSale()" title="آخر بيع للصنف">آخر بيع</button></div>
<div><label>الكمية</label><br><input id="lQty" type="number" value="1" style="width:80px"></div>
<div><label>السعر</label><br><input id="lPrice" type="number" value="0" style="width:90px"></div>
<div><label>الإجمالي</label><br><input id="lTot" readonly style="width:90px"></div>
<div><label>خصم</label><br><input id="lDisc" type="number" value="0" style="width:70px"></div>
<div><label>خصم%</label><br><input id="lDiscP" type="number" value="0" style="width:60px"></div>
<div><br><button onclick="addLine()">⬇ إضافة</button></div>
<div style="margin-right:auto"><label>رقم الفاتورة</label><br><input id="invNo" style="width:100px" value="<?=$next?>" onchange="loadInv()"></div>
</div>
</div>
<div class="layout2"><div class="grow grid-wrap">
<table class="grid"><tr><th>م</th><th>الصنف</th><th>الوحدة</th><th>كمية</th><th>الكمية الإجمالية</th><th>سعر</th><th>إجمالي</th><th>خصم</th><th>خصم%</th><th>إجمالي بعد الخصم</th><th>الصافي</th><th>ملاحظات</th><th></th></tr><tbody id="rows"></tbody></table>
</div><div class="side">
<div class="sidebox"><div class="pr"><span>سعر الشراء</span><b id="pBuy">0</b></div><div class="pr"><span>سعر البيع</span><b id="pSale">0</b></div><div class="pr"><span>سعر الجملة</span><b id="pWhole">0</b></div></div>
<div class="sidebox"><label>العميل</label><select id="cust" style="width:100%"><option value="0">—</option><?php foreach ($custs as $c): ?><option value="<?=$c['id']?>"><?=e($c['name'] ?? $c['id'])?></option><?php endforeach; ?></select>
<label>المندوب</label><select id="sman" style="width:100%"><?php foreach ($men as $m): ?><option value="<?=$m['id']?>"><?=e($m['name'] ?? $m['id'])?></option><?php endforeach; ?></select></div>
</div></div>
<div class="foot" style="margin-top:10px"><div class="row">
<div><label>توزيع خصم %</label><br><input id="distP" type="number" value="0" style="width:70px"></div>
<div><label>- إجمالي خصم</label><br><input id="hdisc" type="number" value="0" style="width:90px"></div>
<div><label>إجمالي المبيعات</label><br><input id="tSales" readonly style="width:110px"></div>
<div><label>إجمالي بعد الخصم</label><br><input id="tAfter" readonly style="width:110px"></div>
<div><label>الصافي</label><br><input id="tNet" readonly class="net" style="width:120px"></div>
<div><label>استلام</label><br><input id="paid" type="number" value="0" style="width:100px"></div>
<div><label>متبقي</label><br><input id="tRest" readonly style="width:100px"></div>
</div><div class="row" style="margin-top:10px">
<button class="btn-pay" onclick="saveInv(0)">سداد فاتورة (F9)</button>
<button class="btn-hold" onclick="saveInv(1)">تعليق فاتورة</button>
<button class="ghost" onclick="newInv()">🆕 جديد</button>
<button class="danger" onclick="delInv()">🗑️ حذف</button>
<select id="suspList" onchange="if(this.value){$('invNo').value=this.value;loadInv();this.value=''}"><option value="">— فواتير معلقة (<?=count($susp)?>) —</option><?php foreach ($susp as $s): ?><option value="<?=$s['id']?>"><?=$s['id']?> (<?=$s['d']?>) = <?=$s['tot_net']?></option><?php endforeach; ?></select>
<span id="msg"></span>
</div></div>
<script>
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

const $ = id => document.getElementById(id);
let cart = [], editing = null, cur = null;
function calcEntry() {
  const q = parseFloat($('lQty').value) || 0, p = parseFloat($('lPrice').value) || 0;
  let d = parseFloat($('lDisc').value) || 0, dp = parseFloat($('lDiscP').value) || 0;
  if (dp > 0) d = q * p * dp / 100;
  $('lTot').value = (q * p - d).toFixed(2);
}
['lQty','lPrice','lDisc','lDiscP'].forEach(id => $(id).addEventListener('input', calcEntry));
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
  $('fGroup').value = cur.group_id;
  $('pBuy').textContent = cur.purch; $('pSale').textContent = cur.sale; $('pWhole').textContent = cur.whole;
  $('stockLbl').textContent = cur.balance;
  $('fUnit').innerHTML = cur.units.map(u => `<option value="${u.factor}" data-nm="${esc(u.name)}">${esc(u.name)}</option>`).join('');
  $('stockU').textContent = cur.unit_name;
  $('lPrice').value = cur.sale; $('lQty').value = 1; $('lQty').focus(); $('lQty').select();
  calcEntry();
}
async function lastSale() {
  if (!cur) return;
  const r = await (await fetch('api_last_sale.php?id=' + cur.id)).json();
  $('msg').textContent = r.ok ? `آخر بيع: ${r.qty} × ${r.price} بتاريخ ${r.date}` : 'لا يوجد بيع سابق';
}
function addLine() {
  if (!cur) return;
  const q = parseFloat($('lQty').value) || 0;
  if (q <= 0) return;
  const p = parseFloat($('lPrice').value) || 0;
  const g = q * p;
  let d = parseFloat($('lDisc').value) || 0;
  const dp = parseFloat($('lDiscP').value) || 0;
  if (dp > 0) d = g * dp / 100;
  const uo = $('fUnit').selectedOptions[0];
  const ex = cart.find(x => x.id == cur.id && x.price == p);
  if (ex) ex.qty += q;
  else cart.push({ id: cur.id, name: cur.name, unit: uo ? uo.dataset.nm : '', factor: parseFloat(uo ? uo.value : 1) || 1, qty: q, price: p, disc: d, discp: dp, note: $('fNote').value });
  $('srch').value = ''; $('fNote').value = ''; $('srch').focus();
  cur = null;
  $('fCode').value = ''; $('fItem').value = '';
  $('stockLbl').textContent = '0';
  $('pBuy').textContent = '0'; $('pSale').textContent = '0'; $('pWhole').textContent = '0';
  $('lQty').value = 1; $('lPrice').value = 0; $('lDisc').value = 0; $('lDiscP').value = 0; $('lTot').value = '';
  render();
}
function totals() {
  let g = 0, dl = 0;
  cart.forEach(x => { g += x.qty * x.price; dl += x.disc; });
  let hd = parseFloat($('hdisc').value) || 0;
  const dp = parseFloat($('distP').value) || 0;
  if (dp > 0) { hd = g * dp / 100; $('hdisc').value = hd.toFixed(2); }
  const after = g - dl - Math.min(hd, g - dl);
  return { g, after };
}
function render() {
  const t = totals();
  $('rows').innerHTML = cart.map((x, i) => {
    const g = x.qty * x.price, net = g - x.disc;
    return `<tr><td>${i + 1}</td><td>${esc(x.name)}</td><td>${x.unit}</td><td>${x.qty}</td><td>${(x.qty * x.factor).toFixed(2)}</td>
      <td>${x.price}</td><td>${g.toFixed(2)}</td><td>${x.disc.toFixed(2)}</td><td>${x.discp}</td>
      <td>${net.toFixed(2)}</td><td><b>${net.toFixed(2)}</b></td><td style="font-size:12px">${esc(x.note || '')}</td>
      <td><button class="danger" onclick="cart.splice(${i},1);render()">✕</button></td></tr>`;
  }).join('');
  $('tSales').value = t.g.toFixed(2);
  $('tAfter').value = t.after.toFixed(2);
  $('tNet').value = t.after.toFixed(2);
  const pd = parseFloat($('paid').value) || 0;
  $('tRest').value = (t.after - pd).toFixed(2);
}
['hdisc','distP','paid'].forEach(id => $(id).addEventListener('input', render));
document.addEventListener('keydown', e => {
  if (e.key === 'F7') { e.preventDefault(); $('srch').focus(); $('srch').select(); }
  if (e.key === 'F9') { e.preventDefault(); saveInv(0); }
  if (e.key === 'Enter' && ['lQty','lPrice','lDisc','lDiscP'].includes(document.activeElement.id)) { e.preventDefault(); addLine(); }
});
function newInv() { cart = []; editing = null; $('invNo').value = 'جديد'; $('hdisc').value = 0; $('distP').value = 0; $('paid').value = 0; $('msg').textContent = ''; render(); }
async function saveInv(temp) {
  if (!cart.length) { $('msg').textContent = 'الفاتورة فارغة'; return; }
  const t = totals();
  const body = { id: editing, temp, lines: cart, hdisc: parseFloat($('hdisc').value) || 0, paid: parseFloat($('paid').value) || 0, cust: parseInt($('cust').value) || 0, sman: parseInt($('sman').value) || 0, note: '' };
  const r = await (await fetch('api_sale_save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
  if (r.ok) { $('msg').style.color = '#16a34a'; $('msg').textContent = (temp ? '⏸ معلقة ' : '✅ سداد ') + r.id; $('invNo').value = r.id; editing = r.id; if (!temp) newInv(); else location.reload(); }
  else { $('msg').style.color = '#dc2626'; $('msg').textContent = '❌ ' + (r.error || 'فشل'); }
}
async function loadInv() {
  const id = parseInt($('invNo').value) || 0;
  if (!id) return;
  const r = await (await fetch('api_sale_load.php?id=' + id)).json();
  if (!r.ok) { $('msg').textContent = '❌ ' + (r.error || 'غير موجودة'); return; }
  editing = id; cart = r.lines.map(x => ({ ...x, unit: '', factor: 1, discp: 0, note: '' }));
  $('hdisc').value = r.hdisc; $('paid').value = r.paid; $('cust').value = r.cust;
  render(); $('msg').textContent = '📂 فاتورة ' + id;
}
async function delInv() {
  if (!editing || !confirm('حذف الفاتورة ' + editing + '؟')) return;
  const r = await (await fetch('api_sale_save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ del: editing }) })).json();
  if (r.ok) newInv(); else $('msg').textContent = '❌ ' + (r.error || 'فشل');
}
render();
</script>
<?php layout_close(); ?>
