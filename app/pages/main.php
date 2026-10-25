<?php
// pages/main.php — الواجهة الرئيسية (القوائم من سجل الموديولات: موديول مقفول/ممنوع = مخفي)
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/cfg.php';
auth_need();
$db = db();
// صلاحيات المستخدم الحالي (بلا صفوف = لا وصول)
$__allow = [];
try {
  $__uid = (int)($_SESSION['uid'] ?? 0);
  if ($__uid === 0) {
    $__st = $db->prepare('SELECT id FROM users WHERE username=? LIMIT 1'); $__st->execute([$_SESSION['u'] ?? '']);
    $__uid = (int)($__st->fetch()['id'] ?? 0);
  }
  if ($__uid > 0) {
    $__allow = array_column($db->query("SELECT perm FROM user_perms WHERE user_id=$__uid AND val=1")->fetchAll(), 'perm');
    if (!$db->query("SELECT COUNT(*) n FROM user_perms WHERE user_id=$__uid")->fetch()['n']) $__allow = [];
  } else $__allow = [];
} catch (Throwable $t) { $__allow = []; }
$__permOk = function (string $base) use ($__allow): bool {
  if (cfg_is_admin()) return true;
  $g = cfg_perm_of($base);
  return $g === null || in_array($g, $__allow, true) || ($g === 'm_item' && (in_array('m_sale', $__allow, true) || in_array('m_buy', $__allow, true)));
};
$tabs = []; $tools = [];
foreach (cfg_tabs() as $tk => $tn) {
  $rows = [];
  foreach (cfg_tools()[$tk] ?? [] as $r) {
    $base = basename(parse_url($r[2], PHP_URL_PATH) ?: $r[2]);
    if (!cfg_mod_allow($base)) continue;    // موديول مقفل من لوحة تحكم المحل
    if (!$__permOk($base)) continue;        // صلاحية المستخدم
    $rows[] = [$r[0], $r[1], $r[2]];
  }
  if ($rows) { $tabs[$tk] = $tn; $tools[$tk] = $rows; }
}
$u = $db->query("SELECT COUNT(*) n FROM currencies WHERE IS_Deleted=0")->fetch()['n'];
$S = cfg_shop();
// شريط الموديولات (نفس شكل الأصل — حالة كل موديول من الإعدادات)
$mods = [];
foreach ([
  ['🧾', 'فاتورة مبيعات', 'sale_rs.php'], ['📦', 'فاتورة مشتريات', 'buy_rs.php'], ['📱', 'فاتورة موبايل', 'msale.php'],
  ['🏷️', 'عروض الأصناف', 'offers.php'], ['🎟️', 'كوبونات الخصم', 'coupons.php'], ['⭐', 'نقاط الولاء', 'loyalty.php'],
  ['💬', 'خدمة واتساب', 'whatsapp.php'], ['🖨️', 'طباعة باركود', 'barcode.php'], ['🧠', 'غرفة الطلبية', 'order.php'], ['🧮', 'جرد سكانر', 'gardscan.php'],
  ['🔮', 'تنبؤ المخزون', 'predict.php'], ['🟢', 'الورديات', 'shifts.php'], ['💰', 'الخزينة', 'money.php?r=in'],
  ['🧾', 'سندات خارجية', 'ext_voucher.php?mode=in'], ['🛠️', 'لوحة تحكم المحل', 'settings.php'],
] as $m) {
  $base = basename(parse_url($m[2], PHP_URL_PATH) ?: $m[2]);
  $mods[] = [$m[1], cfg_mod_allow($base) && $__permOk($base), $m[2]];
}
foreach ([
  ['شئون موظفين ورواتب', 'hr_emp.php'], ['موديول الأقساط', ''], ['موديول الصيانة', ''],
  ['الضريبة', ''], ['ربط الميزان', ''],
] as $m) {
  $base = basename(parse_url($m[1], PHP_URL_PATH) ?: $m[1]);
  $mods[] = [$m[0], $m[1] !== '' && $__permOk($base) ? cfg_mod_allow($base) : false, $m[1]];
}
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#4a1042"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"><meta name="apple-mobile-web-app-title" content="DOCTOR BEAUTY"><link rel="manifest" href="/manifest.json"><link rel="apple-touch-icon" href="../assets/logo-192.png"><title><?=e($S['name'])?> — الواجهة الرئيسية</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>@media(max-width:760px){.menubar{flex-wrap:wrap!important;overflow-x:visible!important;padding:6px 8px!important;gap:6px!important}.menubar .m{flex:1 1 29%;min-width:0;text-align:center;white-space:nowrap;padding:6px 4px!important;font-size:11.5px;box-sizing:border-box}.toolbar{min-height:0!important}.toolbar a{width:86px;padding:7px}.wrap{flex-direction:column}.side{display:none}.logo{padding:18px 10px}.big3 a{width:100%;max-width:340px;padding:20px 10px}#pop{padding:0!important}.status{font-size:11px;gap:8px}}</style>
<style>
*{box-sizing:border-box}body{font-family:'Cairo',Tahoma,Arial,sans-serif;margin:0;background:linear-gradient(180deg,#1c071f 0%,#2b0a2e 100%);color:#fff;min-height:100vh}
.menubar{background:rgba(20,10,30,.85);backdrop-filter:blur(12px);color:#fff;display:flex;gap:8px;padding:12px 16px;flex-wrap:wrap;position:sticky;top:0;z-index:20;border-bottom:1px solid #ffffff25;box-shadow:0 4px 20px #0006,inset 0 -1px 0 #ffffff15}
.menubar .m{position:relative;overflow:hidden;padding:10px 22px;cursor:pointer;font-weight:700;font-size:14.5px;border-radius:16px;transition:all .25s;border:1.5px solid #ffffff30;box-shadow:0 8px 28px #0007,inset 0 1px 0 #ffffff35,inset 0 -1px 0 #0003;color:#fff}
.menubar .m::after{content:"";position:absolute;top:0;right:-60%;width:50%;height:100%;background:linear-gradient(100deg,transparent,#ffffff44,transparent);transform:skewX(-20deg);transition:right .5s}
.menubar .m:hover::after{right:130%}
.menubar .m:nth-child(odd){background:linear-gradient(160deg,#4ade80 0%,#16a34a 45%,#065f46 100%);box-shadow:0 8px 28px #16a34a55,inset 0 1px 0 #ffffff40,inset 0 -1px 0 #0003}
.menubar .m:nth-child(even){background:linear-gradient(160deg,#60a5fa 0%,#3b82f6 45%,#1e3a8a 100%);box-shadow:0 8px 28px #3b82f655,inset 0 1px 0 #ffffff40,inset 0 -1px 0 #0003}
.menubar .m:nth-child(4n+3){background:linear-gradient(160deg,#e879f9 0%,#c026d3 45%,#6b21a8 100%);box-shadow:0 8px 28px #c026d355,inset 0 1px 0 #ffffff40,inset 0 -1px 0 #0003}
.menubar .m:hover{transform:translateY(-3px) scale(1.03);border-color:#ffffff55;box-shadow:0 14px 36px #0009,inset 0 1px 0 #ffffff55}
.menubar .m.on{font-weight:800;transform:translateY(-2px) scale(1.02);border:2px solid #ffffff66;box-shadow:0 12px 36px #000b,inset 0 1px 0 #ffffff66,inset 0 -1px 0 #0004}
.toolbar{background:linear-gradient(180deg,#faf5ff,#f1eafe);border-bottom:1px solid #d8cbf5;display:flex;gap:10px;padding:12px 16px;flex-wrap:wrap;min-height:86px}
.toolbar a{text-align:center;text-decoration:none;color:#1e1b4b;background:#fff;border:1px solid #e6defc;border-top:3px solid #a855f7;border-radius:16px;padding:10px;width:116px;box-shadow:0 2px 10px #4a104215;transition:all .2s}
.toolbar a:hover{transform:translateY(-3px);box-shadow:0 10px 24px #4a104230;border-top-color:#16a34a}
.toolbar a div:first-child{font-size:30px}
.toolbar a div:last-child{font-size:11.5px;font-weight:700}
.wrap{display:flex;gap:0;min-height:calc(100vh - 120px)}
.side{width:236px;background:#fff;color:#0f172a;padding:10px;font-size:13.5px;box-shadow:-4px 0 16px #0003;order:2}.logo{order:1!important}
.side .it{display:flex;gap:8px;align-items:center;padding:7px 9px;border-bottom:1px solid #f1f5f9;border-radius:8px}
.side .it:hover{background:#f8fafc}
.dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;box-shadow:0 0 6px currentColor}.on{background:#16a34a;color:#16a34a}.off{background:#dc2626;color:#dc2626}
.side a{color:#0f766e;text-decoration:none;font-weight:600}.side a:hover{text-decoration:underline}
.logo{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;padding:34px 20px;background:radial-gradient(circle at 50% 25%,#5b21b6 0%,#4a1042 45%,#1c071f 100%)}
.hero-t{font-size:16px;font-weight:800;color:#f9a8d4;letter-spacing:3px}
.big3{display:flex;gap:20px;flex-wrap:wrap;justify-content:center}
.big3 a{position:relative;overflow:hidden;display:flex;flex-direction:column;align-items:center;gap:10px;width:215px;padding:32px 10px;border-radius:22px;text-decoration:none;color:#fff;font-weight:800;font-size:19px;box-shadow:0 16px 48px #0009,inset 0 1px 0 #ffffff40,inset 0 -1px 0 #0004;transition:all .22s;border:1.5px solid #ffffff35;backdrop-filter:blur(12px)}
.big3 a::after{content:"";position:absolute;top:0;right:-60%;width:50%;height:100%;background:linear-gradient(100deg,transparent,#ffffff44,transparent);transform:skewX(-20deg);transition:right .5s}
.big3 a:hover{transform:translateY(-6px) scale(1.04);box-shadow:0 24px 64px #000b,inset 0 1px 0 #ffffff55;border-color:#ffffff55}
.big3 a:hover::after{right:130%}
.big3 a span.ic{font-size:54px;filter:drop-shadow(0 4px 8px #0006)}
.big3 .b1{background:linear-gradient(160deg,#4ade80 0%,#16a34a 40%,#065f46 100%)}
.big3 .b2{background:linear-gradient(160deg,#60a5fa 0%,#3b82f6 40%,#1e3a8a 100%)}
.big3 .b3{background:linear-gradient(160deg,#e879f9 0%,#c026d3 40%,#6b21a8 100%)}
.big3 a small{font-size:12.5px;font-weight:600;opacity:.9}
.status{background:linear-gradient(90deg,#65a30d,#84cc16);color:#0f172a;font-size:12.5px;padding:8px 14px;display:flex;gap:16px;flex-wrap:wrap;font-weight:700}
.status a{text-decoration:underline}
</style></head>
<body>
<div class="menubar" id="tabs">
<?php $first = true; foreach ($tabs as $k => $t): ?><div class="m<?=$first ? ' on' : ''?>" data-tab="<?=$k?>"><?=$t?></div><?php $first = false; endforeach; ?>
</div>
<div class="toolbar" id="toolbar"></div>
<script>
const TOOLS = <?=json_encode($tools, JSON_UNESCAPED_UNICODE)?>;
function showTab(k) {
  document.querySelectorAll('#tabs .m').forEach(el => el.classList.toggle('on', el.dataset.tab === k));
  document.getElementById('toolbar').innerHTML = (TOOLS[k] || []).map(t =>
    `<a href="${t[2]}"><div style="font-size:30px">${t[0]}</div><div>${t[1]}</div></a>`).join('');
}
let hideTimer=null;
function resetHide(){clearTimeout(hideTimer);hideTimer=setTimeout(()=>{document.getElementById('toolbar').style.display='none'},10000)}
document.querySelectorAll('#tabs .m').forEach(el=>el.addEventListener('click',()=>{showTab(el.dataset.tab);document.getElementById('toolbar').style.display='flex';resetHide()}));
document.getElementById('toolbar').addEventListener('mouseenter',()=>clearTimeout(hideTimer));
document.getElementById('toolbar').addEventListener('mouseleave',()=>resetHide());
resetHide();
</script>
<div class="wrap">
<div class="side">
<?php foreach ($mods as $m): ?><div class="it"><span class="dot <?=$m[1] ? 'on' : 'off'?>"></span><?php if ($m[1] && $m[2]): ?><a href="<?=$m[2]?>"><?=$m[0]?></a><?php else: ?><span><?=$m[0]?></span><?php endif; ?></div><?php endforeach; ?>
</div>
<div class="logo">
<img src="<?=e(cfg_logo_src('../'))?>" alt="<?=e($S['name'])?>" style="width:220px;border-radius:26px;box-shadow:0 14px 44px #0009;background:#fff;padding:8px"><div class="hero-t"><?=e(mb_strtoupper($S['name_en'] != '' ? $S['name_en'] : $S['name'], 'UTF-8'))?> 🌿</div>
<div class="big3">
<a class="b1" href="sale_rs.php"><span class="ic">🧾</span>فاتورة بيع</a>
<a class="b2" href="buy_rs.php"><span class="ic">📦</span>فاتورة شراء</a>
<a class="b3" href="<?=($__permOk('dash.php') && cfg_mod_allow('dash.php')) ? 'dash.php' : 'main.php'?>"><span class="ic">📊</span>التقارير<small>الداشبورد الكاملة</small></a>
</div>
</div>
</div>
<div id="pop" style="display:none;position:fixed;inset:0;background:#000a;z-index:50;padding:4vh 15vw">
<div style="background:#fff;color:#0f172a;border-radius:16px;height:100%;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 0 80px #000a">
<div id="popbar" style="display:flex;gap:8px;align-items:center;background:linear-gradient(90deg,#1e1b4b,#4a1042);color:#fff;padding:10px 14px;border-bottom:2px solid #a855f7"><b id="poptitle" style="flex:1">...</b>
<button onclick="popFull()" style="background:#ffffff2e;color:#fff;border:none;border-radius:8px;padding:6px 12px;cursor:pointer">⛶ كامل</button>
<button onclick="popClose()" style="background:#dc2626;color:#fff;border:none;border-radius:8px;padding:6px 14px;cursor:pointer">✕ إغلاق</button></div>
<iframe id="popframe" src="" style="flex:1;border:none;width:100%"></iframe>
</div></div>
<script>
let popUrl = '';
function popOpen(url, title) {
  popUrl = url;
  document.getElementById('poptitle').textContent = title || url;
  const fr = document.getElementById('popframe');
  fr.onload = function() { try { fr.contentDocument.querySelectorAll('aside,.side').forEach(e => e.style.display = 'none'); } catch (e) {} };
  fr.src = url;
  document.getElementById('pop').style.display = 'block';
}
function popClose() { document.getElementById('pop').style.display = 'none'; document.getElementById('popframe').src = 'about:blank'; }
function popFull() { const p = document.getElementById('pop'); p.style.padding = (p.style.padding === '0px' ? '4vh 15vw' : '0px'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') popClose(); });
document.addEventListener('click', e => {
  const a = e.target.closest('a[href]');
  if (!a) return;
  const h = a.getAttribute('href');
  if (!h || !/\.php/.test(h) || a.target === '_blank' || /logout\.php|home\.php|about\.php\?exit=1/.test(h)) return;
  e.preventDefault();
  popOpen(h, a.textContent.trim().slice(0, 40));
});
</script>
<script>
(function () {
  function pulse() { try { fetch('ping.php', { credentials: 'same-origin', cache: 'no-store' }).catch(function(){}); } catch (e) {} }
  setInterval(pulse, 4 * 60 * 1000);
  window.addEventListener('focus', pulse);
  pulse();
})();
</script>
<div class="status"><span>🏪 <?=e($S['name'])?></span><span>المستخدم: <?=e(auth_user() ?? '')?></span><span><a href="control.php" style="color:#0f172a;font-weight:800">🛠️ لوحة التحكم الشاملة</a></span><span><a href="settings.php" style="color:#0f172a">🎛️ لوحة المحل</a></span><span><a href="home.php" style="color:#0f172a">📊 اللوحة</a></span><span><a href="logout.php" style="color:#0f172a">🚪 خروج</a></span><span>الأصناف: <?=number_format($u)?></span><span>النسخة: الحرة (فرع 99)</span><span><?=date('Y-m-d H:i')?></span></div>
</body></html>
