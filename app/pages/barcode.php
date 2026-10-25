<?php
// pages/barcode.php — طباعة باركود (مصمم ملصقات)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$q = trim($_GET['q'] ?? '');
$rows = [];
if ($q !== '') {
  if (ctype_digit($q)) {
    $st = $db->prepare("SELECT id, symbol, barcode, sale_price, purch_price FROM currencies WHERE (id=? OR barcode=?) AND IS_Deleted=0 LIMIT 20");
    $st->execute([(int)$q, $q]);
  } else {
    $parts = array_values(array_filter(preg_split('/\s+/u', mb_substr($q, 0, 64))));
    $w = []; $p = [];
    foreach ($parts as $x) { $w[] = 'symbol LIKE ?'; $p[] = "%$x%"; }
    $st = $db->prepare("SELECT id, symbol, barcode, sale_price, purch_price FROM currencies WHERE " . implode(' AND ', $w) . " AND IS_Deleted=0 ORDER BY id LIMIT 30");
    $st->execute($p);
  }
  $rows = $st->fetchAll();
}
layout_open('طباعة باركود', '');
echo '<div class="card no-print"><form class="row"><div><label>بحث صنف (اسم/كود/باركود)</label><br><input name="q" style="width:260px" value="' . e($q) . '"></div><div><br><button>بحث</button></div>'
  . '<div><label>عدد النسخ</label><br><input id="cp" type="number" value="1" min="1" style="width:70px"></div>'
  . '<div><label><input type="checkbox" id="sN" checked> اسم الصنف</label><br><label><input type="checkbox" id="sP" checked> السعر</label></div>'
  . '<div><label><input type="checkbox" id="sB" checked> الباركود</label><br><label><input type="checkbox" id="sC" checked> الكود</label></div>'
  . '<div><br><button type="button" onclick="window.print()">🖨️ طباعة</button></div></form></div>';
echo '<div class="card"><div><label>مقاس الملصق</label> <select id="sz" onchange="document.querySelectorAll(\'#labels .lbl\').forEach(el=>el.style.width=this.value)"><option value="220px">عريض</option><option value="150px">وسط</option><option value="110px">صغير</option></select></div><div id="labels" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px">';
foreach ($rows as $r) {
  $bc = preg_replace('/[^\dA-Za-z]/', '', $r['barcode'] ?: ('00' . $r['id']));
  echo '<div class="lbl" style="border:1px dashed #000;padding:8px;text-align:center;width:220px">'
    . '<b class="lnm">' . e(mb_substr($r['symbol'], 0, 30)) . '</b>'
    . '<svg class="lbc" data-bc="' . e($bc) . '"></svg>'
    . '<div class="lpr">' . e($bc) . ' — <span>' . e($r['sale_price']) . '</span> ج</div></div>';
}
echo '</div></div>';
echo '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script><script>'
  . 'function drawBC(){document.querySelectorAll(".lbc").forEach(el=>{try{JsBarcode(el,el.dataset.bc,{format:"CODE128",lineColor:"#000",width:2,height:50,displayValue:false});}catch(e){el.outerHTML="<div class=\'lbc\' style=\'font-family:monospace\'>"+el.dataset.bc+"</div>";}});}'
  . 'drawBC();'
  . 'document.getElementById("cp").addEventListener("input",function(){const n=Math.max(1,parseInt(this.value)||1);const box=document.getElementById("labels");box.querySelectorAll(".lblx").forEach(el=>el.remove());if(n>1){box.querySelectorAll(".lbl").forEach(el=>{for(let i=1;i<n;i++){const c=el.cloneNode(true);c.classList.add("lblx");box.appendChild(c);}});drawBC();}});'
  . 'function tgl(id,cls){document.getElementById(id).addEventListener("change",function(){document.querySelectorAll(cls).forEach(el=>el.style.display=this.checked?"":"none");});}'
  . 'tgl("sN",".lnm");tgl("sP",".lpr");tgl("sB",".lbc");</script>';
layout_close();
