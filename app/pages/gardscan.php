<?php
// pages/gardscan.php — جرد سكانر (حوض count_list — مطابق لجرد الداش بورد المهندس)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$db->exec("CREATE TABLE IF NOT EXISTS count_list (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code INT NOT NULL,
  name TEXT NOT NULL,
  counted DOUBLE NOT NULL DEFAULT 0,
  balance DOUBLE NOT NULL DEFAULT 0,
  exp_date VARCHAR(12) NOT NULL DEFAULT '',
  note TEXT,
  username VARCHAR(64) NOT NULL DEFAULT '',
  posted TINYINT NOT NULL DEFAULT 0,
  created DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
layout_open('جرد سكانر', 'gardscan.php');
?>
<style>
.gwrap{display:grid;gap:10px}
.gcard{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;box-shadow:0 2px 12px #0001}
.grow{display:flex;gap:8px;flex-wrap:wrap;align-items:end}
.gcard input,.gcard select{padding:7px 10px;border:1px solid #cbd5e1;border-radius:8px;font-family:inherit;font-size:15px;background:#fff}
.gcard button{padding:9px 14px;border:none;border-radius:8px;font-family:inherit;font-weight:800;cursor:pointer;background:#0f766e;color:#fff}
.gcard button.a{background:linear-gradient(135deg,#065f46,#10b981);color:#fff}
.gcard button.x{background:#e2e8f0;color:#0f172a}
.gmsg{font-size:13px;font-weight:800;color:#0f766e}
.gerr{font-size:13px;font-weight:800;color:#b91c1c}
table.gt{width:100%;border-collapse:collapse;font-size:13.5px;background:#fff}
table.gt th{background:#ecfdf5;color:#064e3b;padding:8px 6px;border:1px solid #d1fae5;font-size:12.5px}
table.gt td{border:1px solid #e2e8f0;padding:6px 8px;text-align:center}
.diff-ok{color:#16a34a;font-weight:800}.diff-bad{color:#dc2626;font-weight:800}
.gnote{font-size:13px;color:#854d0e;background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:8px 12px;margin-top:8px}
.gdel{background:#fee2e2;color:#b91c1c;border:none;border-radius:6px;padding:4px 8px;cursor:pointer}
@media(max-width:760px){.grow>label{flex:1 1 44%}.grow>label input{width:100%!important}}
</style>
<div class="gwrap">
<div class="gcard"><h3 style="margin:0 0 10px;color:#065f46">🧮 الجرد (نفس حوض السحابة — يظهر على الموبايل فوراً)</h3>
<div class="grow"><label>🔍 بحث بالاسم: <input id="gardName" list="gardNameList" style="width:220px" autocomplete="off" oninput="gardNameGo(this.value)"></label><datalist id="gardNameList"></datalist><span class="gmsg" id="gardNameMsg"></span></div>
<div class="grow">
  <label>الكود: <input id="gardCode" inputmode="numeric" style="width:110px" autofocus></label>
  <label>المعدود: <input id="gardCount" inputmode="decimal" style="width:110px"></label>
  <label>شهر: <input id="gardExpM" type="number" min="1" max="12" style="width:70px"></label>
  <label>سنة: <input id="gardExpY" type="number" min="2024" max="2040" style="width:90px"></label>
  <label>ملاحظة: <input id="gardNote" style="width:170px"></label>
  <button class="a" onclick="gardAdd()">➕ إضافة</button><span class="gmsg" id="gardMsg"></span>
</div>
<div class="grow">
  <button onclick="gardExport()">📋 تصدير الجرد المفتوح كورقة جرد (GARD)</button>
  <button class="x" onclick="gardClear(false)">🗑️ مسح المفتوح</button>
  <button class="x" onclick="gardLoad()">🔄 تحديث</button><span class="gmsg" id="gardExpMsg"></span>
</div>
<div class="gnote">اختر عميل → سكان: الكود + المعدود + Enter (الصلاحية شهر/سنة اختيارية). الأرضية: <b id="cntB">0</b> بند — الفارق = المعدود − الدفتري</div>
<div id="gardTable"></div>
</div>
</div>
<script>
const gardCsrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
let gardNameT = 0;
function esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c] || c)); }
async function gardNameGo(v) {
  clearTimeout(gardNameT);
  gardNameT = setTimeout(async () => {
    const dl = document.getElementById('gardNameList');
    if (!v || v.trim().length < 2) { dl.innerHTML = ''; return; }
    try {
      const r = await fetch('api_gard.php?act=names&q=' + encodeURIComponent(v)).then(x => x.json());
      dl.innerHTML = (r.rows || []).map(x => `<option value="${x.code} - ${esc(x.name)}">`).join('');
      const hit = (r.rows || []).find(x => String(x.code) === String(v).split(' - ')[0].trim());
      if (hit) {
        document.getElementById('gardCode').value = hit.code;
        document.getElementById('gardNameMsg').textContent = hit.code + ' - ' + esc(hit.name);
        document.getElementById('gardCount').focus();
      }
    } catch (e) {}
  }, 250);
}
async function gardAdd() {
  const msg = document.getElementById('gardMsg');
  const code = document.getElementById('gardCode').value.trim();
  const counted = document.getElementById('gardCount').value.trim();
  if (code === '' || counted === '') { msg.className = 'gerr'; msg.textContent = 'اكتب الكود والمعدود'; return; }
  const em = document.getElementById('gardExpM').value.trim(), ey = document.getElementById('gardExpY').value.trim();
  let expd = '';
  if (/^(0?[1-9]|1[0-2])$/.test(em) && /^\d{4}$/.test(ey)) expd = ey + '-' + String(em).padStart(2, '0') + '-01';
  msg.className = 'gmsg'; msg.textContent = 'جاري الإضافة...';
  try {
    const r = await fetch('api_gard.php?act=add', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': gardCsrf }, body: JSON.stringify({ code, counted, exp_date: expd, note: document.getElementById('gardNote').value || '' }) }).then(x => x.json());
    msg.className = r.ok ? 'gmsg' : 'gerr';
    msg.textContent = r.ok ? `✅ ${esc(r.name || '')} اتسجل (يظهر على الموبايل فوراً)` : ('❌ ' + (r.error || 'فشل'));
    if (r.ok) {
      document.getElementById('gardCode').value = '';
      document.getElementById('gardCount').value = '';
      document.getElementById('gardNote').value = '';
      gardLoad();
    }
  } catch (e) { msg.className = 'gerr'; msg.textContent = '❌ تعذر الإضافة'; }
}
async function gardDel(id) {
  if (!confirm('حذف البند من الجرد؟')) return;
  try { await fetch('api_gard.php?act=del', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': gardCsrf }, body: JSON.stringify({ id }) }).then(x => x.json()); gardLoad(); } catch (e) {}
}
async function gardClear(ask) {
  if (!confirm('مسح كل بنود الجرد المفتوحة؟')) return;
  try { await fetch('api_gard.php?act=clear', { method: 'POST', headers: { 'X-CSRF-Token': gardCsrf } }).then(x => x.json()); gardLoad(); } catch (e) {}
}
async function gardExport() {
  const msg = document.getElementById('gardExpMsg');
  if (!confirm('تصدير بنود الجرد المفتوحة كورقة جرد (GARD) في البرنامج؟')) return;
  msg.className = 'gmsg'; msg.textContent = 'جاري التصدير...';
  try {
    const r = await fetch('api_gard.php?act=export', { method: 'POST', headers: { 'X-CSRF-Token': gardCsrf } }).then(x => x.json());
    msg.className = r.ok ? 'gmsg' : 'gerr';
    msg.textContent = r.ok ? ('✅ ' + esc(r.log || '').slice(-160)) : ('❌ ' + (r.error || 'فشل'));
    if (r.ok) gardLoad();
  } catch (e) { msg.className = 'gerr'; msg.textContent = '❌ تعذر التصدير'; }
}
async function gardLoad() {
  let rows = [];
  try { const r = await fetch('api_gard.php?act=list').then(x => x.json()); rows = r.rows || []; } catch (e) {}
  document.getElementById('cntB').textContent = rows.length;
  document.getElementById('gardTable').innerHTML = rows.length
    ? `<table class="gt"><tr><th></th><th>الكود</th><th>الصنف</th><th>المعدود</th><th>الدفتري</th><th>الفرق</th><th>الصلاحية</th><th>ملاحظة</th><th>بواسطة</th></tr>` +
      rows.map(x => {
        const d = ((+x.counted || 0) - (+x.balance || 0)).toFixed(2);
        const cls = Math.abs(+d) < 0.005 ? 'diff-ok' : 'diff-bad';
        return `<tr><td><button class="gdel" onclick="gardDel(${x.id})" title="حذف البند">🗑️</button></td><td>${x.code}</td><td>${esc(x.name)}</td><td><b>${x.counted}</b></td><td>${x.balance}</td><td class="${cls}"><b>${d}</b></td><td>${esc((x.exp_date || '').slice(0, 7))}</td><td>${esc(x.note || '')}</td><td>${esc(x.username || '')}</td></tr>`;
      }).join('') + `</table>`
    : '<div class="gnote">البنود: 0 — الجرد المفتوح فاضي (سكان: كود + معدود)</div>';
}
document.getElementById('gardCode').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); document.getElementById('gardCount').focus(); } });
document.getElementById('gardCount').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); gardAdd(); document.getElementById('gardCode').focus(); } });
gardLoad();
</script>
<?php layout_close(); ?>
