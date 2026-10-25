<?php
// pages/journal.php — دفتر اليومية: قيد يدوي متعدد السطور + عرض القيود بالبنود + حذف (فرع 99)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/audit.php';
$db = db();
if (($_GET['r'] ?? '') === 'lines') { // JSON: بنود قيد
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)($_GET['id'] ?? 0);
  $st = $db->prepare("SELECT s.dept, s.credit, s.notes, s.acc_no, a.AName FROM restrictions_sub s LEFT JOIN accounts_index a ON a.Code=s.acc_no WHERE s.res_id=? AND s.branch=99 ORDER BY s.acc_no");
  $st->execute([$id]);
  echo json_encode($st->fetchAll());
  exit;
}
$tab = $_GET['tab'] ?? 'new';
$msg = '';
if (($_POST['save'] ?? '') !== '') {
  $lines = json_decode($_POST['lines'] ?? '[]', true) ?: [];
  $ok = [];
  foreach ($lines as $l) {
    $d = max(0, (float)($l['d'] ?? 0)); $c = max(0, (float)($l['c'] ?? 0));
    if ((int)($l['acc'] ?? 0) > 0 && ($d > 0) !== ($c > 0)) $ok[] = ['acc' => (int)$l['acc'], 'd' => $d, 'c' => $c, 'n' => mb_substr(trim((string)($l['n'] ?? '')), 0, 255)];
  }
  $td = array_sum(array_column($ok, 'd'));
  $tc = array_sum(array_column($ok, 'c'));
  if (count($ok) >= 2 && abs($td - $tc) < 0.01 && $td > 0) {
    $mx = seq_next_table('restrictions', 'id');
    $doc = seq_next_table('restrictions', 'doc_no');
    $Serve = preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?$/', (string)($_POST['date'] ?? '')) ? $_POST['date'] : date('Y-m-d');
    $nt = mb_substr(trim($_POST['notes'] ?? 'قيد يدوي'), 0, 255);
    try {
      $db->beginTransaction();
      $db->prepare("INSERT INTO restrictions (id, date, doc_no, type, state, notes, branch, IS_Deleted) VALUES (?,?,?,1,1,?,99,0)")->execute([$mx, $Serve, $doc, $nt]);
      $ins = $db->prepare("INSERT INTO restrictions_sub (res_id, date, dept, credit, acc_no, notes, branch) VALUES (?,?,?,?,?,?,99)");
      foreach ($ok as $l) $ins->execute([$mx, $Serve, $l['d'], $l['c'], $l['acc'], $l['n']]);
      $db->commit();
      audit_log('create', 'journal', $mx, "doc=$doc td=$td lines=" . count($ok));
      $msg = '✅ قيد ' . $doc . ' (متزن: ' . number_format($td) . ') — ' . count($ok) . ' سطر';
    } catch (Throwable $t1) { $db->rollBack(); $msg = '❌ ' . $t1->getMessage(); }
  } else $msg = '❌ القيد لازم: سطرين على الأقل + مدين = دائن + كل سطر جهة واحدة فقط';
}
if (($_POST['del'] ?? '') !== '') { // حذف قيود يدوية/افتتاحية فقط (نوع 1 و 2)
  $id = (int)($_POST['did'] ?? 0);
  $t = (int)$db->query("SELECT type FROM restrictions WHERE id=$id AND branch=99 AND IS_Deleted=0")->fetch()['type'] ?? 0;
  if (in_array($t, [1, 2], true)) {
    $db->prepare("UPDATE restrictions SET IS_Deleted=1 WHERE id=?")->execute([$id]);
    audit_log('delete', 'journal', $id, "type=$t");
    $msg = '🗑️ القيد اتلغى (أرشيف IS_Deleted=1)';
  } else $msg = '❌ ده قيد تلقائي من سندات الخزينة — مش بيتسحب من هنا';
}
$accs = $db->query("SELECT Code, AName FROM accounts_index ORDER BY Code")->fetchAll();
$opt = '';
foreach ($accs as $a) $opt .= '<option value="' . $a['Code'] . '">' . $a['Code'] . ' - ' . e(mb_substr($a['AName'] ?? '', 0, 40)) . '</option>';
layout_open('دفتر اليومية — القيد المزدوج', '');
echo '<div class="card"><a href="?tab=new">✏️ قيد جديد</a> | <a href="?tab=list">📒 دفتر اليومية</a></div>';
if ($tab === 'new') {
  $doc = (int)$db->query("SELECT IFNULL(MAX(doc_no),0)+1 n FROM restrictions")->fetch()['n'];
  echo '<div class="card"><form method="post" id="gForm"><div class="row">'
    . '<div><label>رقم القيد</label><br><input value="' . $doc . '" readonly style="width:90px"></div>'
    . '<div><label>التاريخ</label><br><input name="date" type="date" value="' . date('Y-m-d') . '"></div>'
    . '<div><label>البيان</label><br><input name="notes" style="width:300px" placeholder="وارد — سبب القيد"></div>'
    . '</div><table style="margin-top:8px"><tr><th>م</th><th>الحساب</th><th>مدين</th><th>دائن</th><th>بيان السطر</th><th></th></tr><tbody id="gl"></tbody></table>'
    . '<div class="row" style="margin-top:8px"><div>مدين: <b id="gD">0.00</b> — دائن: <b id="gC">0.00</b> — <b id="gB" style="color:#dc2626">غير متزن</b></div>'
    . '<div><br><button type="button" onclick="gAdd()">➕ سطر</button> <button name="save" value="1">💾 حفظ القيد</button> <span>' . $msg . '</span></div></div></form></div>'
    . '<script>const ACCS=`' . $opt . '`;'
    . 'function gAdd(){const tr=document.createElement("tr");tr.innerHTML=`<td></td><td><select class="ga" style="width:230px">${ACCS}</select></td><td><input type="number" step="0.01" class="gd" value="0" style="width:105px" oninput="gT()"></td><td><input type="number" step="0.01" class="gc" value="0" style="width:105px" oninput="gT()"></td><td><input class="gn" style="width:170px"></td><td><button type="button" onclick="this.parentElement.parentElement.remove();gT()">✕</button></td></tr>`;document.getElementById("gl").appendChild(tr);gT();}'
    . 'function gT(){let d=0,c=0,n=0;document.querySelectorAll("#gl tr").forEach((x,i)=>{x.children[0].textContent=i+1;d+=parseFloat(x.querySelector(".gd").value)||0;c+=parseFloat(x.querySelector(".gc").value)||0;if(x.querySelector(".ga").value>0&&(parseFloat(x.querySelector(".gd").value)||0)+(parseFloat(x.querySelector(".gc").value)||0)>0)n++;});document.getElementById("gD").textContent=d.toFixed(2);document.getElementById("gC").textContent=c.toFixed(2);const ok=Math.abs(d-c)<0.01&&d>0&&n>=2;const b=document.getElementById("gB");b.textContent=ok?"متزن ✓":"غير متزن";b.style.color=ok?"#16a34a":"#dc2626"}'
    . 'document.getElementById("gForm").addEventListener("submit",e=>{const L=[];document.querySelectorAll("#gl tr").forEach(x=>{L.push({acc:x.querySelector(".ga").value,d:x.querySelector(".gd").value,c:x.querySelector(".gc").value,n:x.querySelector(".gn").value});});const h=document.createElement("input");h.type="hidden";h.name="lines";h.value=JSON.stringify(L);e.target.appendChild(h);});'
    . 'gAdd();gAdd();</script>';
} else {
  $f = dt($_GET['from'] ?? '', date('Y-m-01')); $t2 = dt($_GET['to'] ?? '', date('Y-m-d'));
  $q = trim($_GET['q'] ?? ''); $like = $q !== '' ? str_replace(['%', '_'], ['\%', '\_'], $q) : [];
  $st = $db->prepare("SELECT r.id, r.doc_no, DATE(r.date) d, r.type, r.notes, SUM(s.dept) td, SUM(s.credit) tc, COUNT(s.acc_no) ln FROM restrictions r LEFT JOIN restrictions_sub s ON s.res_id=r.id AND s.branch=99 WHERE r.IS_Deleted=0 AND r.branch=99 AND r.date>=? AND r.date<=?" . ($like !== [] ? " AND (r.doc_no LIKE ? OR r.notes LIKE ?)" : "") . " GROUP BY r.id, r.doc_no, d, r.type, r.notes ORDER BY r.doc_no DESC LIMIT 200");
  $ps = [$f . ' 00:00:00', $t2 . ' 23:59:59'];
  if ($like !== []) { $ps[] = '%' . $q . '%'; $ps[] = '%' . $q . '%'; }
  $st->execute($ps);
  $rows = $st->fetchAll();
  $tn = [1 => 'قيد يدوي', 2 => 'افتتاحي', 11 => 'قبض خزينة', 12 => 'صرف خزينة', 13 => 'تحويل خزائن', 14 => 'قبض خارجي', 15 => 'صرف خارجي'];
  echo '<div class="card"><form class="row" method="get"><input type="hidden" name="tab" value="list">'
    . '<div><label>من</label><br><input name="from" type="date" value="' . $f . '"></div>'
    . '<div><label>إلى</label><br><input name="to" type="date" value="' . $t2 . '"></div>'
    . '<div><label>بحث (رقم/بيان)</label><br><input name="q" value="' . e($q) . '" style="width:150px"></div>'
    . '<div><br><button>فلتر</button></div></form>'
    . '<table style="margin-top:8px"><tr><th>السند</th><th>التاريخ</th><th>النوع</th><th>البيان</th><th>سطور</th><th>مدين</th><th>دائن</th><th></th></tr>';
  foreach ($rows as $r) {
    $can = in_array((int)$r['type'], [1, 2], true);
    echo '<tr><td><b>' . $r['doc_no'] . '</b></td><td>' . $r['d'] . '</td><td>' . ($tn[$r['type']] ?? $r['type']) . '</td><td style="text-align:right">' . e(mb_substr($r['notes'] ?? '', 0, 60)) . '</td><td>' . $r['ln'] . '</td><td><b>' . number_format($r['td'] ?? 0, 2) . '</b></td><td>' . number_format($r['tc'] ?? 0, 2) . '</td>'
      . '<td><button type="button" onclick="exp(' . $r['id'] . ',this)">👁 بنود</button></td></tr>';
    if ($can) echo '<tr><td colspan="8" style="padding:2px 6px"><form method="post" style="display:inline" onsubmit="return confirm(\'لسحب القيد ' . $r['doc_no'] . '؟\')"><button name="del" value="1" style="background:#dc2626;padding:4px 10px;font-size:12px">🗑 حذف</button><input type="hidden" name="did" value="' . $r['id'] . '"></form></td></tr>';
    echo '<tr id="xs' . $r['id'] . '" style="display:none"><td colspan="8" style="text-align:right;direction:rtl"></td></tr>';
  }
  if (!$rows) echo '<tr><td colspan="8">لا قيود في الفترة</td></tr>';
  echo '</table></div>'
    . '<script>'
    . 'function exp(id,btn){const x=document.getElementById("xs"+id);'
    . 'if(x.style.display==="none"){fetch("journal.php?r=lines&id="+id).then(z=>z.json()).then(ls=>{x.style.display="";btn.textContent="▲";let h="<table><tr><th>الحساب</th><th>مدين</th><th>دائن</th><th>بيان</th></tr>";ls.forEach(l=>{h+="<tr><td>"+l.acc_no+" - "+(l.AName||"")+"</td><td>"+Number(l.dept).toFixed(2)+"</td><td>"+Number(l.credit).toFixed(2)+"</td><td>"+(l.notes||"")+"</td></tr>"});h+="</table>";x.innerHTML="<td colspan=8>"+h+"</td>"}).catch(()=>{})}'
    . 'else{x.style.display="none";btn.textContent="👁 بنود"}}'
    . '</script>';
}
layout_close();
