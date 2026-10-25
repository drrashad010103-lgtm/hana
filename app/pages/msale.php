<?php
// pages/msale.php — فاتورة موبايل خفيفة (كاشير سريع / دليفري) — فرع 99
require __DIR__ . '/../lib/db.php';
$db = db();
$me = $_SESSION['u'] ?? '';
$cp = $cd = 1;
try {
  $st = $db->prepare("SELECT edit_price, edit_disc FROM users WHERE username=? LIMIT 1");
  $st->execute([$me]);
  if ($r = $st->fetch()) { $cp = (int)$r['edit_price']; $cd = (int)$r['edit_disc']; }
} catch (Throwable $t) {}
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>فاتورة موبايل — النسخة الحرة</title>
<style>
*{box-sizing:border-box}body{font-family:Tahoma,Arial;margin:0;background:#f1f5f9;padding-bottom:110px}
header{background:#0f766e;color:#fff;padding:12px 14px;display:flex;gap:8px;align-items:center;position:sticky;top:0;z-index:5}
header a{color:#fff;text-decoration:none;background:#ffffff2e;padding:7px 12px;border-radius:8px;font-size:13px}
.src{padding:10px}.src input{width:100%;font-size:18px;padding:14px;border:2px solid #0f766e;border-radius:12px;font-family:inherit}
#res{padding:0 10px;display:flex;flex-direction:column;gap:6px}
.ritem{background:#fff;border-radius:10px;padding:12px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 3px #0002}
.ritem button{background:#16a34a;color:#fff;border:none;border-radius:10px;font-size:22px;width:52px;height:52px;cursor:pointer;font-family:inherit}
#cart{padding:10px;display:flex;flex-direction:column;gap:8px}
.crow{background:#fff;border-radius:10px;padding:10px;box-shadow:0 1px 3px #0002}
.crow .nm{font-weight:800;font-size:15px}
.step{display:flex;gap:8px;align-items:center;margin-top:8px}
.step button{width:48px;height:48px;font-size:24px;border:none;border-radius:10px;background:#e2e8f0;cursor:pointer;font-family:inherit}
.step button.plus{background:#16a34a;color:#fff}
.step input{width:70px;text-align:center;font-size:20px;padding:8px;border:1px solid #cbd5e1;border-radius:8px;font-family:inherit}
.prow{display:flex;gap:6px;margin-top:6px}.prow input{flex:1;font-size:15px;padding:8px;border:1px solid #cbd5e1;border-radius:8px;font-family:inherit}
.del{background:#dc2626!important;color:#fff}
#bar{position:fixed;bottom:0;right:0;left:0;background:#0f172a;color:#fff;padding:12px;display:flex;gap:8px;align-items:center}
#bar .tot{flex:1;font-size:20px;font-weight:800}
#bar button{border:none;border-radius:10px;padding:14px 22px;font-size:17px;font-weight:800;cursor:pointer;font-family:inherit}
#save{background:#16a34a;color:#fff}#newb{background:#334155;color:#fff}
#msg{text-align:center;padding:8px;font-weight:800}
.disc{padding:0 10px}.disc input{width:100%;font-size:16px;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-family:inherit}
</style></head>
<body>
<header><b>📱 فاتورة موبايل</b><span style="margin-right:auto"></span><a href="main.php">القوائم</a><a href="sale_rs.php">الكاملة</a></header>
<div class="src"><input id="q" placeholder="🔍 اسم أو كود الصنف..." autocomplete="off"></div>
<div id="res"></div>
<div id="cart"></div>
<div class="disc"><input id="hdisc" type="number" value="0" min="0" placeholder="خصم الفاتورة" <?= $cd ? '' : 'disabled' ?>></div>
<div id="msg"></div>
<div id="bar"><div class="tot">الإجمالي: <span id="tot">0</span></div><button id="newb" onclick="cart=[];render();msg('🧾 فاتورة جديدة')">🆕</button><button id="save" onclick="save()">💾 حفظ</button></div>
<script>
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

const CANP = <?= $cp ? 1 : 0 ?>;
let cart = [], st = null;
const $ = id => document.getElementById(id);
$('q').addEventListener('input', () => {
  clearTimeout(st);
  st = setTimeout(async () => {
    const v = $('q').value.trim();
    if (v.length < 1) { $('res').innerHTML = ''; return; }
    const r = await (await fetch('api_item.php?q=' + encodeURIComponent(v))).json();
    $('res').innerHTML = (r.rows || []).slice(0, 8).map(x =>
      `<div class="ritem"><div><b>${esc(x.name)}</b><br><small>${x.id}</small></div><button onclick="add(${x.id})">+</button></div>`).join('');
  }, 250);
});
async function add(id) {
  const r = await (await fetch('api_item_full.php?id=' + id)).json();
  if (!r.ok) return;
  const ex = cart.find(x => x.id == id);
  if (ex) ex.qty++;
  else cart.push({ id: r.item.id, name: r.item.name, qty: 1, price: r.item.sale, disc: 0 });
  $('q').value = ''; $('res').innerHTML = '';
  render();
}
function render() {
  let g = 0, dl = +($('hdisc').value || 0);
  $('cart').innerHTML = cart.map((x, i) => {
    const t = x.qty * x.price;
    g += t;
    return `<div class="crow"><div class="nm">${esc(x.name)}</div>
      <div class="step"><button onclick="ch(${i},-1)">−</button><input value="${x.qty}" onchange="setQ(${i},this.value)"><button class="plus" onclick="ch(${i},1)">+</button>
      <button class="del" onclick="cart.splice(${i},1);render()">✕</button></div>
      <div class="prow"><input type="number" value="${x.price}" ${CANP ? '' : 'disabled'} onchange="cart[${i}].price=parseFloat(this.value)||0;render()"> × <b>${t.toFixed(2)}</b></div></div>`;
  }).join('');
  const net = Math.max(0, g - Math.min(dl, g));
  $('tot').textContent = net.toFixed(2);
}
function ch(i, d) { cart[i].qty = Math.max(0.01, (cart[i].qty || 0) + d); render(); }
function setQ(i, v) { cart[i].qty = Math.max(0.01, parseFloat(v) || 0); render(); }
function msg(t) { $('msg').textContent = t; }
$('hdisc').addEventListener('input', render);
async function save() {
  if (!cart.length) { msg('السلة فارغة'); return; }
  const body = { temp: 0, pt: 1, lines: cart.map(x => ({ id: x.id, qty: x.qty, price: x.price, disc: 0 })), hdisc: parseFloat($('hdisc').value) || 0, paid: parseFloat($('tot').textContent) || 0 };
  const r = await (await fetch('api_sale_save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();
  if (r.ok) { msg('✅ اتحفظت فاتورة ' + r.id + ' = ' + r.total); cart = []; $('hdisc').value = 0; render(); }
  else msg('❌ ' + (r.error || 'فشل'));
}
render();
</script>
</body></html>
