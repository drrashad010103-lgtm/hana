<?php
// pages/easy.php — شاشة تحكم مزامنة EasyOrders
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
layout_open('🔗 مزامنة EasyOrders', '');
?>
<style>
.eo-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:16px}
.eo-card{background:#fff;border:2px solid #e9d5ff;border-radius:14px;padding:16px;text-align:center;box-shadow:0 4px 16px #6b21a810}
.eo-card .num{font-size:32px;font-weight:900;color:#6b21a8}
.eo-card .lbl{font-size:13px;color:#7c3aed;font-weight:700}
.eo-card.ok .num{color:#16a34a}.eo-card.warn .num{color:#eab308}.eo-card.bad .num{color:#dc2626}
.eo-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:all .2s;box-shadow:0 4px 16px #0003}
.eo-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px #0005}
.eo-btn.primary{background:linear-gradient(135deg,#a855f7,#7c3aed);color:#fff}
.eo-btn.green{background:linear-gradient(135deg,#4ade80,#16a34a);color:#fff}
.eo-btn.blue{background:linear-gradient(135deg,#60a5fa,#3b82f6);color:#fff}
.eo-btn.orange{background:linear-gradient(135deg,#fb923c,#ea580c);color:#fff}
.eo-btn:disabled{opacity:.5;pointer-events:none}
#eoLog{background:#0f172a;color:#a5f3fc;font-family:monospace;font-size:13px;border-radius:12px;padding:16px;height:300px;overflow-y:auto;white-space:pre-wrap;line-height:1.7}
.eo-opt{display:flex;gap:16px;flex-wrap:wrap;margin:12px 0;padding:14px;background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px}
.eo-opt label{display:flex;align-items:center;gap:6px;font-weight:700;font-size:14px;color:#4c1d95;cursor:pointer}
.eo-opt input[type=checkbox]{width:18px;height:18px;accent-color:#7c3aed}
.eo-opt input[type=number]{width:80px;padding:6px;border:1px solid #d8b4fe;border-radius:8px;text-align:center}
.eo-tabs{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
.eo-tab{padding:10px 20px;border-radius:12px 12px 0 0;border:2px solid #e9d5ff;border-bottom:none;background:#faf5ff;color:#6b21a8;font-weight:800;font-size:14px;cursor:pointer;font-family:inherit}
.eo-tab.active{background:linear-gradient(135deg,#a855f7,#7c3aed);color:#fff;border-color:#7c3aed}
.nw-filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:10px 0;padding:12px;background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px}
.nw-filters select,.nw-filters input[type=text]{padding:8px;border:1px solid #d8b4fe;border-radius:8px;font-family:inherit;font-size:14px}
.nw-filters select{min-width:150px}
.nw-filters input[type=text]{min-width:220px}
.nw-tbl{width:100%;border-collapse:collapse;font-size:13px}
.nw-tbl th{background:#f3e8ff;color:#4c1d95;padding:8px 6px;border-bottom:2px solid #e9d5ff;position:sticky;top:0;text-align:center;font-size:12px}
.nw-tbl td{padding:6px;border-bottom:1px solid #f3e8ff;text-align:center}
.nw-tbl td.nm{text-align:right;font-weight:700;color:#3b0764}
.nw-tbl tr:hover{background:#fdf4ff}
.nw-tbl tr.done{opacity:.45}
.nw-wrap{max-height:520px;overflow:auto;border:1px solid #e9d5ff;border-radius:10px;background:#fff}
.nw-up{padding:5px 12px;border:none;border-radius:8px;background:linear-gradient(135deg,#4ade80,#16a34a);color:#fff;font-weight:800;font-size:12px;cursor:pointer;font-family:inherit}
.nw-up:disabled{opacity:.4;cursor:default}
.nw-badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:800}
.nw-badge.ok{background:#dcfce7;color:#166534}.nw-badge.no{background:#fee2e2;color:#991b1b}
#nwLog{background:#0f172a;color:#a5f3fc;font-family:monospace;font-size:12px;border-radius:10px;padding:10px;height:120px;overflow-y:auto;white-space:pre-wrap;line-height:1.6;margin-top:10px}
@media(max-width:700px){.nw-tbl th:nth-child(4),.nw-tbl td:nth-child(4),.nw-tbl th:nth-child(6),.nw-tbl td:nth-child(6){display:none}}
</style>

<div class="eo-tabs">
  <button class="eo-tab active" id="tabBtnSync" onclick="eoSwitch('sync')">🚀 مزامنة الأرصدة</button>
  <button class="eo-tab" id="tabBtnNew" onclick="eoSwitch('new')">⬆️ أصناف غير مرفوعة</button>
</div>

<div id="paneSync">
<div class="card">
  <h3>🔗 حالة الربط مع EasyOrders</h3>
  <div class="eo-grid">
    <div class="eo-card"><div class="num" id="stTotal">—</div><div class="lbl">إجمالي الأصناف</div></div>
    <div class="eo-card"><div class="num" id="stMatched">—</div><div class="lbl">مطابقة مع RS</div></div>
    <div class="eo-card ok"><div class="num" id="stOk">0</div><div class="lbl">تمت ✅</div></div>
    <div class="eo-card warn"><div class="num" id="stSkip">0</div><div class="lbl">متخطاة (متطابقة)</div></div>
    <div class="eo-card bad"><div class="num" id="stFail">0</div><div class="lbl">فشل ❌</div></div>
    <div class="eo-card"><div class="num" id="stNF">0</div><div class="lbl">غير موجودة بالموقع</div></div>
  </div>

  <div class="eo-opt">
    <label><input type="checkbox" id="eoApply"> ⚡ تطبيق فعلي (بدونه = عرض فقط)</label>
    <label><input type="checkbox" id="eoMoved"> 🔄 أصناف متحركة فقط (24 ساعة)</label>
    <label><input type="checkbox" id="eoPrices"> 💰 تحديث الأسعار (×1.05)</label>
    <label><input type="checkbox" id="eoCosts"> 📊 تحديث التكاليف</label>
    <label>🔢 حد أقصى: <input type="number" id="eoLimit" value="50" min="1" max="500"></label>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
    <button class="eo-btn blue" id="btnFetch" onclick="eoFetch()">🔍 جلب منتجات الموقع</button>
    <button class="eo-btn primary" id="btnSync" onclick="eoSync()">🚀 تشغيل المزامنة</button>
    <button class="eo-btn green" onclick="eoSync(true)">⚡ تطبيق فوري (متحرك + أسعار)</button>
    <button class="eo-btn orange" onclick="document.getElementById('eoLog').textContent=''">🗑️ مسح السجل</button>
  </div>

  <div id="eoProg" style="display:none;margin-bottom:10px">
    <div style="background:#e9d5ff;border-radius:8px;height:24px;overflow:hidden"><div id="eoBar" style="background:linear-gradient(90deg,#a855f7,#7c3aed);height:100%;width:0%;transition:width .3s;border-radius:8px"></div></div>
    <div id="eoProgTxt" style="font-size:13px;color:#6b21a8;font-weight:700;margin-top:4px"></div>
  </div>

  <div id="eoLog">جاهز — اضغط "جلب منتجات الموقع" أولاً ثم "تشغيل المزامنة".</div>
</div>

<div class="card">
  <h3>ℹ️ شرح الربط</h3>
  <div class="note">
    <b>المصادر:</b> الأرصدة والأسعار تُقرأ من قاعدة <b>rs_own</b> (برنامجك الحر) — لا نمس الأصل.<br>
    <b>الكتابة:</b> PATCH فقط على EasyOrders — لا يمسح أي حقل (الاسم/الوصف/الصورة/الأقسام تبقى كما هي).<br>
    <b>السعر:</b> سعر البيع بالموقع = سعر البيع عندك × 1.05 — والأصل × 1.18 (تقريب جنيه صحيح).<br>
    <b>الصفر:</b> أي صنف برصيد أقل من 1 يتفعّل عليه "منع الشراء عند النفاذ" تلقائياً.<br>
    <b>الربط:</b> كود الصنف في RS = البادئة الرقمية في slug الموقع (مثال: slug "2627-..." = صنف 2627).
  </div>
</div>

</div>

<div id="paneNew" style="display:none">
  <div class="card">
    <h3>⬆️ أصناف غير مرفوعة على الموقع</h3>
    <div class="note" style="margin-bottom:10px">
      الأصناف اللي <b>لسه مش موجودة</b> على إيزي أوردر (مفيش لها ربط). اختار القسم والرصيد، علّم على اللي عايزه، واضغط رفع — أو ارفع صنف واحد بزرار <b>رفع</b> اللي جنبه.<br>
      السعر المقترح = سعر البيع عندك × 1.05 × 1.18 — والخصم = × 1.05 (نفس اتفاقية باقي المنتجات).
    </div>

    <div class="nw-filters">
      <button class="eo-btn blue" style="padding:8px 16px;font-size:13px" onclick="nwLoad()">🔄 تحميل القائمة</button>
      <label style="font-weight:800;font-size:13px;color:#4c1d95">القسم:
        <select id="nwGroup" onchange="nwPage=1;nwRender()"><option value="">الكل</option></select>
      </label>
      <label style="font-weight:800;font-size:13px;color:#4c1d95">الرصيد:
        <select id="nwBal" onchange="nwPage=1;nwRender()">
          <option value="all">الكل</option>
          <option value="has">له رصيد</option>
          <option value="none">بدون رصيد</option>
        </select>
      </label>
      <input type="text" id="nwSearch" placeholder="🔍 بحث بالاسم أو الكود..." oninput="nwPage=1;nwRender()">
      <span id="nwCount" style="font-weight:800;color:#6b21a8;font-size:13px"></span>
    </div>

    <div class="nw-filters" style="background:#fff7ed;border-color:#fed7aa">
      <span style="font-weight:800;font-size:13px;color:#9a3412">🖼️ صورة افتراضية (للأصناف اللي ملهاش صورة):</span>
      <input type="text" id="nwThumb" style="min-width:280px;padding:8px;border:1px solid #d8b4fe;border-radius:8px" placeholder="https://...">
      <button class="eo-btn orange" style="padding:8px 14px;font-size:13px" onclick="nwSaveThumb()">💾 حفظ</button>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;align-items:center">
      <label style="font-weight:800;font-size:13px;color:#4c1d95;display:flex;align-items:center;gap:6px">
        <input type="checkbox" id="nwAllChk" onchange="nwCheckAll(this.checked)" style="width:18px;height:18px;accent-color:#7c3aed"> تحديد الكل المعروض
      </label>
      <button class="eo-btn green" id="nwBulkBtn" style="padding:8px 18px;font-size:13px" onclick="nwBulk()">⬆️ رفع المحدد</button>
      <button class="eo-btn" style="padding:8px 14px;font-size:13px;background:#e5e7eb;color:#374151" onclick="nwLoadMore()" id="nwMoreBtn">عرض المزيد</button>
      <span id="nwProg" style="font-weight:800;color:#7c3aed;font-size:13px"></span>
    </div>
    <input type="file" id="nwFile" accept="image/jpeg,image/png,image/webp" style="display:none">

    <div class="nw-wrap">
      <table class="nw-tbl">
        <thead><tr>
          <th style="width:34px"><input type="checkbox" id="nwHeadChk" onchange="nwCheckAll(this.checked)" style="width:16px;height:16px"></th>
          <th style="width:60px">كود</th>
          <th>اسم الصنف</th>
          <th style="width:130px">القسم</th>
          <th style="width:70px">الرصيد</th>
          <th style="width:90px">سعر الموقع</th>
          <th style="width:100px">الصورة</th>
          <th style="width:80px">رفع</th>
        </tr></thead>
        <tbody id="nwBody"><tr><td colspan="8" style="padding:30px;color:#7c3aed">اضغط «تحميل القائمة» لجلب الأصناف غير المرفوعة.</td></tr></tbody>
      </table>
    </div>
    <div id="nwLog"></div>
  </div>
</div>

<script>
function eoSwitch(which){
  document.getElementById('paneSync').style.display = which==='sync'?'':'none';
  document.getElementById('paneNew').style.display = which==='new'?'':'none';
  document.getElementById('tabBtnSync').classList.toggle('active', which==='sync');
  document.getElementById('tabBtnNew').classList.toggle('active', which==='new');
  if(which==='new'&&!nwAll.length)nwLoad();
}
let nwAll=[],nwThumbUrl='',nwPage=1;
const NW_PAGE=150;
function nwEsc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function nwLog(t){const el=document.getElementById('nwLog');el.textContent+=t+'\n';el.scrollTop=el.scrollHeight}
async function nwLoad(){
  const ev=(typeof event!=='undefined')?event:null;const btn=ev&&ev.target&&ev.target.tagName==='BUTTON'?ev.target:null;if(btn)btn.disabled=true;
  document.getElementById('nwBody').innerHTML='<tr><td colspan="8" style="padding:30px;color:#7c3aed">⏳ جاري التحميل ومطابقة المنتجات مع الموقع...</td></tr>';
  try{
    const r=await(await fetch('api_easy_sync.php?act=new_items')).json();
    if(!r.ok)throw new Error(r.error||'فشل');
    nwAll=r.items||[];nwThumbUrl=r.thumb||'';
    document.getElementById('nwThumb').value=nwThumbUrl;
    const gs=[...new Set(nwAll.map(x=>x.group).filter(Boolean))].sort((a,b)=>a.localeCompare(b,'ar'));
    const sel=document.getElementById('nwGroup');
    sel.innerHTML='<option value="">الكل</option>'+gs.map(g=>`<option value="${nwEsc(g)}">${nwEsc(g)}</option>`).join('');
    nwPage=1;nwRender();
  }catch(e){document.getElementById('nwBody').innerHTML='<tr><td colspan="8" style="padding:20px;color:#dc2626">❌ '+nwEsc(e.message)+'</td></tr>'}
  if(btn)btn.disabled=false;
}
function nwFiltered(){
  const g=document.getElementById('nwGroup').value;
  const b=document.getElementById('nwBal').value;
  const q=document.getElementById('nwSearch').value.trim().toLowerCase();
  return nwAll.filter(x=>{
    if(g&&x.group!==g)return false;
    if(b==='has'&&!(x.bal>0))return false;
    if(b==='none'&&x.bal>0)return false;
    if(q&&!(String(x.name).toLowerCase().includes(q)||String(x.id)===q))return false;
    return true;
  });
}
function nwRender(){
  const list=nwFiltered(),shown=list.slice(0,nwPage*NW_PAGE);
  document.getElementById('nwCount').textContent=`${list.length} صنف (معروض ${shown.length})`;
  document.getElementById('nwMoreBtn').style.display=list.length>shown.length?'':'none';
  document.getElementById('nwBody').innerHTML=shown.map(x=>{
    const site=Math.round(x.sale*1.05*1.18);
    return `<tr id="nwrow${x.id}">
      <td><input type="checkbox" class="nwchk" value="${x.id}" style="width:16px;height:16px"></td>
      <td>${x.id}</td>
      <td class="nm">${nwEsc(x.name)}</td>
      <td>${nwEsc(x.group||'—')}</td>
      <td style="font-weight:800;color:${x.bal>0?'#16a34a':'#dc2626'}">${x.bal>0?x.bal.toFixed(0):'0'}</td>
      <td>${site>0?site+' ج':'—'}</td>
      <td id="nwimg${x.id}" style="white-space:nowrap">${nwImgCell(x)}</td>
      <td><button class="nw-up" onclick="nwUpload(${x.id},this)">⬆ رفع</button></td>
    </tr>`}).join('')||'<tr><td colspan="8" style="padding:30px;color:#16a34a">✅ مفيش أصناف مطابقة للفلتر — كل الأصناف مرفوعة</td></tr>';
}
function nwImgCell(x){
  const thumb=x.img?`<img src="${nwEsc(x.img)}" style="width:34px;height:34px;object-fit:cover;border-radius:6px;vertical-align:middle;border:1px solid #ddd"> `:'';
  const label=x.has_img?(x.hosted?'جاهزة':'محفوظة'):'افتراضية';
  return thumb+`<span class="nw-badge ${x.has_img?'ok':'no'}">${label}</span> `+
    `<button onclick="nwPick(${x.id})" title="إضافة/تغيير صورة الصنف" style="border:1px solid #d8b4fe;background:#faf5ff;border-radius:6px;cursor:pointer;padding:3px 6px;font-size:13px">📷</button>`;
}
let nwImgId=0;
function nwPick(id){nwImgId=id;const f=document.getElementById('nwFile');f.value='';f.click()}
function nwResize(file,max,q){
  return new Promise((res,rej)=>{
    const img=new Image();const url=URL.createObjectURL(file);
    img.onload=()=>{try{
      let w=img.width,h=img.height;
      if(w>max||h>max){const k=max/Math.max(w,h);w=Math.round(w*k);h=Math.round(h*k)}
      const c=document.createElement('canvas');c.width=w;c.height=h;
      const g=c.getContext('2d');g.fillStyle='#fff';g.fillRect(0,0,w,h);g.drawImage(img,0,0,w,h);
      res(c.toDataURL('image/jpeg',q));
    }catch(e){rej(e)}finally{URL.revokeObjectURL(url)}};
    img.onerror=()=>{URL.revokeObjectURL(url);rej(new Error('ملف الصورة غير مدعوم'))};
    img.src=url;
  });
}
document.getElementById('nwFile').addEventListener('change',async function(){
  const file=this.files&&this.files[0];if(!file||!nwImgId)return;
  const id=nwImgId;
  try{
    nwLog(`⏳ [${id}] جاري تجهيز الصورة ورفعها...`);
    const data=await nwResize(file,900,0.82);
    const r=await(await fetch('api_easy_sync.php?act=save_img',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id,img:data})})).json();
    if(!r.ok)throw new Error(r.error||'فشل حفظ الصورة');
    const x=nwAll.find(a=>a.id===id);
    if(x){x.img=r.preview||('item_img.php?id='+id+'&v='+Date.now());x.has_img=true;x.hosted=r.remote?1:0;
      const cell=document.getElementById('nwimg'+id);if(cell)cell.innerHTML=nwImgCell(x);}
    nwLog(`🖼️ [${id}] اتحفظت الصورة${r.remote?' واترفعت للاستضافة ✅':' (محلياً فقط ⚠️)'}`);
    if(r.warning)nwLog('⚠️ '+r.warning);
  }catch(e){nwLog(`❌ [${id}] ${e.message}`)}
  nwImgId=0;
});
function nwLoadMore(){nwPage++;nwRender()}
function nwCheckAll(on){document.querySelectorAll('.nwchk').forEach(c=>c.checked=on)}
async function nwUpload(id,btn){
  if(btn){btn.disabled=true;btn.textContent='...'}
  try{
    const fd=new FormData();fd.append('id',id);
    const txt=await(await fetch('api_easy_sync.php?act=upload_new',{method:'POST',body:fd})).text();
    let r;try{r=JSON.parse(txt)}catch(e){throw new Error('رد غير صالح: '+txt.slice(0,120))}
    if(!r.ok)throw new Error(r.error||'فشل');
    const row=document.getElementById('nwrow'+id);if(row){row.classList.add('done');if(btn){btn.textContent='✅ مرفوع'}}
    nwLog(`✅ [${id}] ${r.slug} — السعر ${r.price} / ${r.sale_price} — رصيد ${r.qty}`);
    return true;
  }catch(e){
    if(btn){btn.disabled=false;btn.textContent='⬆ رفع'}
    nwLog(`❌ [${id}] ${e.message}`);
    return false;
  }
}
async function nwBulk(){
  const ids=[...document.querySelectorAll('.nwchk:checked')].map(c=>+c.value);
  if(!ids.length)return nwLog('⚠️ علّم على الأصناف المطلوب رفعها أولاً');
  if(!confirm('رفع '+ids.length+' صنف على الموقع؟'))return;
  const b=document.getElementById('nwBulkBtn');b.disabled=true;
  let ok=0,fail=0;
  for(let i=0;i<ids.length;i++){
    document.getElementById('nwProg').textContent=`⏳ ${i+1} / ${ids.length} — نجح ${ok} | فشل ${fail}`;
    const id=ids[i];const btn=document.querySelector(`#nwrow${id}.nw-up`)||document.querySelector(`#nwrow${id} button`);
    if(await nwUpload(id,btn))ok++;else fail++;
    await new Promise(r=>setTimeout(r,600));
  }
  document.getElementById('nwProg').textContent=`انتهى — نجح ${ok} | فشل ${fail}`;
  b.disabled=false;
  nwLog(`— انتهى الرفع: نجح ${ok} ، فشل ${fail} —`);
  nwAll=nwAll.filter(x=>!ids.slice(0,ids.length).includes(x.id)||!document.getElementById('nwrow'+x.id)?.classList.contains('done'));
  nwRender();
}
async function nwSaveThumb(){
  const fd=new FormData();fd.append('thumb',document.getElementById('nwThumb').value);
  try{
    const r=await(await fetch('api_easy_sync.php?act=save_thumb',{method:'POST',body:fd})).json();
    if(r.ok){nwThumbUrl=r.thumb;nwLog('💾 تم حفظ الصورة الافتراضية')}else nwLog('❌ '+(r.error||'فشل'))
  }catch(e){nwLog('❌ '+e.message)}
}
let eoReady = false;
function log(t){const el=document.getElementById('eoLog');el.textContent+=(el.textContent.endsWith('\n')||el.textContent==='جاهز — اضغط "جلب منتجات الموقع" أولاً ثم "تشغيل المزامنة".'?t+'\n':'\n'+t+'\n');el.scrollTop=el.scrollHeight}
async function eoFetch(){
  const b=document.getElementById('btnFetch');b.disabled=true;log('🔍 جاري جلب المنتجات...');
  try{
    const r=await(await fetch('api_easy_sync.php?act=fetch')).json();
    if(r.ok){document.getElementById('stTotal').textContent=r.total;document.getElementById('stMatched').textContent=r.matched;log('✅ تم جلب '+r.total+' منتج — مطابقة مع RS: '+r.matched);eoReady=true}
    else log('❌ '+(r.error||'فشل'));
  }catch(e){log('❌ خطأ: '+e)}
  b.disabled=false;
}
async function eoPost(fd){
  let last='';
  for(let i=0;i<3;i++){
    try{
      const txt=await(await fetch('api_easy_sync.php?act=sync_stock',{method:'POST',body:fd})).text();
      try{return JSON.parse(txt)}catch(e){last=txt;await new Promise(r=>setTimeout(r,900))}
    }catch(e){last=String(e);await new Promise(r=>setTimeout(r,900))}
  }
  throw new Error('رد غير صالح من الخادم: '+last.slice(0,180));
}
async function eoSync(quick){
  if(quick){document.getElementById('eoApply').checked=true;document.getElementById('eoMoved').checked=false;document.getElementById('eoPrices').checked=true;document.getElementById('eoLimit').value=100}
  const b=document.getElementById('btnSync');b.disabled=true;
  document.getElementById('eoProg').style.display='block';
  log('🚀 بدء المزامنة...');document.getElementById('eoBar').style.width='5%';
  const fd=new FormData();
  fd.append('apply',document.getElementById('eoApply').checked?1:0);
  fd.append('moved',document.getElementById('eoMoved').checked?1:0);
  fd.append('prices',document.getElementById('eoPrices').checked?1:0);
  fd.append('costs',document.getElementById('eoCosts').checked?1:0);
  fd.append('limit',document.getElementById('eoLimit').value);
  const agg={total:0,ok:0,skip:0,fail:0,notfound:0};
  try{
    while(true){
      const r=await eoPost(fd);
      if(!r.ok){log('❌ '+(r.error||'فشل'));break;}
      const s=r.stats;for(const k in agg)agg[k]+=s[k];
      document.getElementById('stOk').textContent=agg.ok;
      document.getElementById('stSkip').textContent=agg.skip;
      document.getElementById('stFail').textContent=agg.fail;
      document.getElementById('stNF').textContent=agg.notfound;
      if(r.of>0)document.getElementById('eoBar').style.width=Math.min(99,Math.round(r.to/r.of*100))+'%';
      if(s.ok>0)(r.log||[]).forEach(l=>log(l));
      if(r.of===0||r.to>=r.of)break;
      await new Promise(res=>setTimeout(res,150));
    }
    document.getElementById('eoBar').style.width='100%';
    log((document.getElementById('eoApply').checked?'⚡ تطبيق فعلي':'👁️ عرض فقط')+' — انتهت الدورة | إجمالي: '+agg.total+' | نجح: '+agg.ok+' | متخطى: '+agg.skip+' | فشل: '+agg.fail+' | غير موجود: '+agg.notfound);
    if(agg.total===0)log('ℹ️ مفيش أصناف في نطاق المزامنة — شيل علامة "متحركة فقط" لو عايز الكل');
  }catch(e){log('❌ خطأ: '+e)}
  b.disabled=false;
  setTimeout(()=>{document.getElementById('eoProg').style.display='none';document.getElementById('eoBar').style.width='0%'},2000);
}
</script>
<?php layout_close(); ?>
