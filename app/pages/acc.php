<?php
// pages/acc.php — شجرة الحسابات: عرض هرمي + أرصدة + إضافة/تعديل/حذف أوراق (فرع 99)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/audit.php';
$db = db();
$msg = '';
if (($_POST['act'] ?? '') !== '') {
  $act = $_POST['act'];
  try {
    if ($act === 'add') {
      $parent = (int)($_POST['parent'] ?? 0);
      $nm = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
      $prow = $db->query("SELECT Code FROM accounts_index WHERE Code=$parent")->fetch();
      if (!$prow || $parent === 0 || $nm === '') throw new Exception('اختار حساب أب وارسم اسم صحيح');
      if ((int)$db->query("SELECT COUNT(*) n FROM restrictions_sub WHERE acc_no=$parent AND branch=99")->fetch()['n'] > 0) throw new Exception('الحساب الأب عليه قيود — منفعش أب');
      $base = $parent < 100 ? $parent * 100 : $parent * 1000;
      for ($n = 1; $n < 1000; $n++) {
        $code = $base + $n;
        if (!$db->query("SELECT 1 FROM accounts_index WHERE Code=$code LIMIT 1")->fetch()) break;
      }
      if ($n >= 1000) throw new Exception('مفيش أكواد فاضية تحت الأب ده');
      $lvl = (int)($db->query("SELECT acc_level FROM accounts_index WHERE Code=$parent")->fetch()['acc_level'] ?? 0) + 1;
      $st = $db->prepare("INSERT INTO accounts_index (Code, AName, Nature, ParentCode, `Date`, acc_level) VALUES (?,?,'0',?,NOW(),?)");
      $st->execute([$code, $nm, $parent, $lvl]);
      audit_log('create', 'account', $code, "parent=$parent name=$nm");
      $msg = '✅ اتضاف حساب ' . $code . ' — ' . $nm;
    } elseif ($act === 'edit') {
      $code = (int)($_POST['code'] ?? 0);
      $nm = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
      if ($code === 0 || $nm === '') throw new Exception('بيانات ناقصة');
      $has = (int)$db->query("SELECT COUNT(*) n FROM restrictions_sub WHERE acc_no=$code AND branch=99")->fetch()['n'];
      if ($has === 0 && (int)$db->query("SELECT COUNT(*) c FROM accounts_index WHERE ParentCode=$code AND Code<>$code")->fetch()['c'] === 0) {
        $db->prepare("UPDATE accounts_index SET AName=? WHERE Code=?")->execute([$nm, $code]);
        audit_log('edit', 'account', $code, "name=$nm");
        $msg = '✅ اتعدل الاسم';
      } else throw new Exception('حساب عليه حركة أو له فروع — الاسم متعدلش من هنا');
    } elseif ($act === 'del') {
      $code = (int)($_POST['code'] ?? 0);
      if ($code === 0) throw new Exception('حساب ناقص');
      if ($code < 100) throw new Exception('الحسابات الرئيسية متتسحبش');
      $kids = (int)$db->query("SELECT COUNT(*) c FROM accounts_index WHERE ParentCode=$code")->fetch()['c'];
      $has = (int)$db->query("SELECT COUNT(*) n FROM restrictions_sub WHERE acc_no=$code AND branch=99")->fetch()['n'];
      if ($kids > 0) throw new Exception('لعله فروع تانية — اسحب الأبناء الأول');
      if ($has > 0) throw new Exception('عليه ' . $has . ' سطر قيود — متتسحبش');
      $db->prepare("DELETE FROM accounts_index WHERE Code=?")->execute([$code]);
      audit_log('delete', 'account', $code, '');
      $msg = '🗑️ الحساب اتسحب';
    }
  } catch (Throwable $t) { $msg = '❌ ' . $t->getMessage(); }
}
$accs = $db->query("SELECT Code, AName, Nature, ParentCode, acc_level, is_center FROM accounts_index ORDER BY Code")->fetchAll();
$bal = [];
foreach ($db->query("SELECT s.acc_no, SUM(s.dept)-SUM(s.credit) b FROM restrictions_sub s JOIN restrictions r ON r.id=s.res_id WHERE r.IS_Deleted=0 AND r.branch=99 GROUP BY s.acc_no") as $r) $bal[(int)$r['acc_no']] = (float)$r['b'];
$kids = [];
$leaf = [];
foreach ($accs as $a) { $p = (int)$a['ParentCode']; $kids[$p][] = $a; if (!isset($kids[(int)$a['Code']])) $kids[(int)$a['Code']] = []; }
$sums = $bal;
function accRoll(int $code, array &$kids, array &$sums): float {
  $t = $sums[$code] ?? 0;
  foreach ($kids[$code] ?? [] as $c) $t += accRoll((int)$c['Code'], $kids, $sums);
  return $sums[$code] = $t;
}
$roots = [];
foreach ($kids[0] ?? [] as $a) accRoll((int)$a['Code'], $kids, $sums);
$natN = ['0' => 'مدين', '1' => 'دائن'];
$popt = '';
foreach ($kids[0] ?? [] as $a) {
  $popt .= '<option value="' . (int)$a['Code'] . '">' . (int)$a['Code'] . ' — ' . e(trim((string)$a['AName'])) . '</option>';
  $walk = function (int $code, int $lvl) use (&$popt, &$walk, $kids) {
    foreach ($kids[$code] ?? [] as $b) {
      $popt .= '<option value="' . (int)$b['Code'] . '">' . str_repeat('— ', $lvl) . (int)$b['Code'] . ' ' . e(mb_substr(trim((string)$b['AName']), 0, 32)) . '</option>';
      $walk((int)$b['Code'], $lvl + 1);
    }
  };
  $walk((int)$a['Code'], 1);
}
layout_open('شجرة الحسابات', '');
echo '<div class="card"><form method="post" class="row" id="addF"><input type="hidden" name="act" value="add">'
  . '<div><label>حساب أب (اختار ورقة أو فرع)</label><br><select name="parent"><option value="0">—</option>' . $popt . '</select></div>'
  . '<div><label>اسم الحساب الجديد</label><br><input name="name" style="width:220px" required></div>'
  . '<div><br><button>➕ إضافة فرع</button></div><div>' . $msg . '</div></form></div>';
echo '<div class="card"><table><tr><th>الكود</th><th style="text-align:right">الاسم</th><th>الطبيعة</th><th>الرصيد</th><th>إدارة</th></tr>';
$render = function ($code, $lvl) use (&$render, $kids, $sums, $bal, $natN) {
  foreach ($kids[$code] ?? [] as $a) {
    $c = (int)$a['Code'];
    $name = trim((string)($a['AName'] ?? ''));
    $leafB = $sums[$c] ?? 0;
    $isLeaf = count($kids[$c] ?? []) === 0;
    echo '<tr><td><b>' . $c . '</b></td><td style="text-align:right;padding-right:' . (10 + $lvl * 26) . 'px">'
      . ($isLeaf ? '<a href="trial.php?r=ledger&acc=' . $c . '&amp;from=' . date('Y-m-01') . '">' . e($name) . '</a>' : '<b>' . e($name) . '</b>')
      . ($lvl === 0 ? '' : ($isLeaf ? ' <small>🍃</small>' : '')) . '</td>'
      . '<td>' . ($natN[$a['Nature']] ?? '—') . '</td>'
      . '<td style="color:' . ($leafB < 0 ? '#dc2626' : '#065f46') . '">' . number_format($leafB, 2) . '</td>'
      . '<td>'
      . '<form method="post" style="display:inline"><input type="hidden" name="act" value="edit"><input type="hidden" name="code" value="' . $c . '"><input type="hidden" id="en' . $c . '" name="name" value="' . e($name) . '" style="width:120px;display:none"><button type="button" onclick="pb(this,' . $c . ')" style="padding:3px 8px;font-size:12px">✏️</button></form>'
      . '<form method="post" style="display:inline" onsubmit="return confirm(\'سحب الحساب ' . $c . '؟\')"><input type="hidden" name="act" value="del"><input type="hidden" name="code" value="' . $c . '"><button style="background:#dc2626;padding:3px 8px;font-size:12px">🗑</button></form>'
      . '</td></tr>';
    $render($c, $lvl + 1);
  }
};
foreach ($kids[0] ?? [] as $a) { echo '<tr><td colspan="5" style="background:#ede9fe;font-weight:800;text-align:right;font-size:14px">' . (int)$a['Code'] . ' - ' . e(trim((string)$a['AName'])) . ' <span style="color:#065f46">' . number_format($sums[(int)$a['Code']] ?? 0, 2) . '</span></td></tr>'; $render((int)$a['Code'], 1); }
echo '</table></div>';
echo '<script>const C0=[`<option value="0">—</option>`];'
  . 'function pb(btn,c){const row=btn.closest("tr");const inp=document.getElementById("en"+c);'
  . 'if(inp.style.display==="none"){inp.style.display="";inp.focus();btn.textContent="✔";}'
  . 'else{inp.style.display="none";btn.textContent="✏️";inp.closest("form").submit();}}</script>';
layout_close();
