<?php
// pages/vouchers.php — القيود الافتتاحية (رأس + بنود متزنة + بحث)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$tab = $_GET['tab'] ?? 'new';
$id = (int)($_GET['id'] ?? 0);
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $lines = json_decode($_POST['lines'] ?? '[]', true) ?: [];
  $ok = array_values(array_filter($lines, fn($l) => (int)($l['acc'] ?? 0) > 0 && (max(0, (float)($l['d'] ?? 0)) + max(0, (float)($l['c'] ?? 0))) > 0));
  $td = array_sum(array_map(fn($l) => max(0, (float)($l['d'] ?? 0)), $ok));
  $tc = array_sum(array_map(fn($l) => max(0, (float)($l['c'] ?? 0)), $ok));
  if (count($ok) && abs($td - $tc) < 0.01) {
    $mx = seq_next_table('restrictions', 'id');
    $doc = seq_next_table('restrictions', 'doc_no');
    $db->beginTransaction();
    $dt = preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?$/', (string)($_POST['date'] ?? '')) ? $_POST['date'] : date('Y-m-d');
    $db->prepare("INSERT INTO restrictions (id, date, doc_no, type, state, notes, branch, IS_Deleted) VALUES (?,?,?,2,1,?,99,0)")->execute([$mx, $dt, $doc, mb_substr(trim($_POST['notes'] ?? 'قيد افتتاحي'), 0, 255)]);
    $ins = $db->prepare("INSERT INTO restrictions_sub (res_id, date, dept, credit, acc_no, notes, branch) VALUES (?,?,?,?,?,?,99)");
    foreach ($ok as $l) $ins->execute([$mx, $dt, max(0, (float)($l['d'] ?? 0)), max(0, (float)($l['c'] ?? 0)), (int)$l['acc'], mb_substr(trim($l['n'] ?? ''), 0, 255)]);
    $db->commit();
    $msg = '✅ قيد ' . $doc . ' (متزن: ' . number_format($td) . ')';
  } else $msg = '❌ القيد غير متزن (مدين ≠ دائن) أو فارغ';
}
$accs = $db->query("SELECT Code, AName FROM accounts_index ORDER BY Code LIMIT 500")->fetchAll();
$opt = '';
foreach ($accs as $a) $opt .= '<option value="' . $a['Code'] . '">' . $a['Code'] . ' - ' . e(mb_substr($a['AName'] ?? '', 0, 40)) . '</option>';
layout_open('القيود الافتتاحية', '');
echo '<div class="card"><a href="?tab=new">القيد الافتتاحي</a> | <a href="?tab=search">البحث</a></div>';
if ($tab === 'search') {
  $q = trim($_GET['q'] ?? '');
  $like = $q !== '' ? str_replace(['%', '_'], ['\%', '\_'], $q) : '';
  $st = $db->prepare("SELECT r.id, r.doc_no, DATE(r.date) d, r.notes, SUM(s.dept) t FROM restrictions r LEFT JOIN restrictions_sub s ON s.res_id=r.id WHERE r.IS_Deleted=0" . ($like !== '' ? " AND (r.doc_no LIKE ?)" : "") . " GROUP BY r.id, r.doc_no, DATE(r.date), r.notes ORDER BY r.id DESC LIMIT 100");
  $st->execute($like !== '' ? ['%' . $like . '%'] : []);
  $rows = $st->fetchAll();
  echo '<div class="card"><form class="row"><input type="hidden" name="tab" value="search"><div><label>رقم القيد</label><br><input name="q" value="' . e($q) . '" style="width:120px"></div><div><br><button>بحث</button></div></form>'
    . '<table><tr><th>رقم القيد</th><th>التاريخ</th><th>البيان</th><th>الإجمالي</th></tr>';
  foreach ($rows as $r) echo '<tr><td>' . $r['doc_no'] . '</td><td>' . $r['d'] . '</td><td>' . e($r['notes']) . '</td><td>' . number_format($r['t']) . '</td></tr>';
  echo '</table></div>';
} else {
  $doc = (int)$db->query("SELECT IFNULL(MAX(doc_no),0)+1 n FROM restrictions")->fetch()['n'];
  echo '<div class="card"><form method="post" id="vForm"><div class="row">'
    . '<div><label>رقم القيد</label><br><input value="' . $doc . '" readonly style="width:90px"></div>'
    . '<div><label>تاريخ القيد</label><br><input name="date" type="date" value="' . date('Y-m-d') . '"></div>'
    . '<div><label>حالة القيد</label><br><select><option>معتمد</option><option>مسودة</option></select></div>'
    . '<div><label>بيان القيد</label><br><input name="notes" style="width:260px" value="قيد افتتاحي"></div>'
    . '</div><table style="margin-top:8px"><tr><th>م</th><th>كود الحساب</th><th>مدين</th><th>دائن</th><th>البيان</th><th></th></tr><tbody id="vl"></tbody></table>'
    . '<div class="row" style="margin-top:8px"><div>مدين: <b id="vD">0</b> — دائن: <b id="vC">0</b> — <b id="vB" style="color:#16a34a">متزن</b></div>'
    . '<div><br><button type="button" onclick="vAdd()">➕ سطر</button> <button name="save" value="1">💾 حفظ القيد</button> <span>' . $msg . '</span></div></div></form></div>'
    . '<script>const ACCS=`' . $opt . '`;let vn=0;'
    . 'function vAdd(){vn++;document.getElementById("vl").insertAdjacentHTML("beforeend",`<tr><td>${vn}</td><td><select class="va" style="width:220px">${ACCS}</select></td><td><input type="number" class="vd" value="0" style="width:100px" oninput="vT()"></td><td><input type="number" class="vc" value="0" style="width:100px" oninput="vT()"></td><td><input class="vn" style="width:180px"></td><td><button type="button" onclick="this.closest(\\"tr\\").remove();vT()">✕</button></td></tr>`);}'
    . 'function vT(){let d=0,c=0;document.querySelectorAll("#vl tr").forEach(tr=>{d+=parseFloat(tr.querySelector(".vd").value)||0;c+=parseFloat(tr.querySelector(".vc").value)||0;});document.getElementById("vD").textContent=d.toFixed(2);document.getElementById("vC").textContent=c.toFixed(2);const b=document.getElementById("vB");const ok=Math.abs(d-c)<0.01&&d>0;b.textContent=ok?"متزن":"غير متزن";b.style.color=ok?"#16a34a":"#dc2626";}'
    . 'document.getElementById("vForm").addEventListener("submit",e=>{const L=[];document.querySelectorAll("#vl tr").forEach(tr=>{L.push({acc:tr.querySelector(".va").value,d:tr.querySelector(".vd").value,c:tr.querySelector(".vc").value,n:tr.querySelector(".vn").value});});const h=document.createElement("input");h.type="hidden";h.name="lines";h.value=JSON.stringify(L);e.target.appendChild(h);});'
    . 'vAdd();vAdd();</script>';
}
layout_close();
