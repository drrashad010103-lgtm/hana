<?php
// pages/whatsapp.php — خدمة واتساب (محادثة عميل + مشاركة فاتورة) — wa.me بلا بوابة
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/cfg.php';
require __DIR__ . '/../lib/wa.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$q = trim($_GET['q'] ?? '');
$invid = (int)($_GET['inv'] ?? 0);
layout_open('خدمة واتساب', '');
echo '<div class="card"><h3>🧾 مشاركة فاتورة</h3><form class="row" method="get"><div><label>رقم الفاتورة (id)</label><br><input name="inv" value="' . ($invid ?: '') . '" style="width:120px"></div><div><br><button>تجهيز النص</button></div></form>';
if ($invid > 0) {
  $h = $db->prepare("SELECT i.id, DATE(i.date) d, i.tot_net, i.paid, IFNULL(cu.name,'') cust, cu.mobile mob FROM inv i LEFT JOIN customers cu ON cu.id=i.cust_id WHERE i.id=? AND i.IS_Deleted=0 LIMIT 1");
  $h->execute([$invid]); $h = $h->fetch();
  if ($h) {
    $ls = $db->prepare("SELECT c.symbol nm, s.val q, s.exchange_price p FROM inv_sub s LEFT JOIN currencies c ON c.id=s.currency_from WHERE s.proc_id=(SELECT proc_id FROM inv WHERE id=? LIMIT 1)");
    $ls->execute([$invid]); $lines = $ls->fetchAll();
    $txt = "🧾 " . cfg_shop_name() . " — فاتورة $invid ($h[d])\nالعميل: $h[cust]\n";
    foreach ($lines as $ln) $txt .= "- {$ln['nm']}: {$ln['q']} × {$ln['p']}\n";
    $txt .= "الإجمالي: {$h['tot_net']} — المدفوع: {$h['paid']}";
    echo '<div class="note" style="white-space:pre-wrap;background:#f8fafc;padding:10px;border-radius:8px">' . e($txt) . '</div><div class="row">'
      . (!empty($h['mob']) ? '<div><a target="_blank" href="' . e(wa_link($h['mob'], $txt)) . '"><button>📲 إرسال للعميل (' . e($h['mob']) . ')</button></a></div>' : '<div class="note">العميل بلا موبايل مسجل</div>')
      . '<div><a target="_blank" href="' . e(wa_share($txt)) . '"><button class="ghost">↗ مشاركة عامة</button></a></div></div>';
  } else echo '<div class="note">لا فاتورة بهذا الرقم</div>';
}
echo '</div>';
echo '<div class="card"><h3>📲 محادثة عميل</h3><form class="row" method="get"><div><label>بحث (اسم/موبايل)</label><br><input name="q" value="' . e($q) . '" style="width:220px"></div><div><br><button>بحث</button></div></form>';
if ($q !== '') {
  $like = '%' . str_replace(['%', '_'], '', $q) . '%';
  $st = $db->prepare("SELECT id, name, mobile FROM customers WHERE (IS_Deleted=0 OR IS_Deleted IS NULL) AND (name LIKE ? OR mobile LIKE ? OR mobile2 LIKE ?) ORDER BY id LIMIT 30");
  $st->execute([$like, $like, $like]);
  echo '<table><tr><th>العميل</th><th>الموبايل</th><th>الرصيد</th><th></th></tr>';
  foreach ($st->fetchAll() as $c) {
    $mob = $c['mobile'] ?: '';
    $b = cust_balance((int)$c['id']);
    $rem = cfg_shop_name() . " 🌿\nأ/ {$c['name']}\nرصيد حسابك: " . number_format($b) . " جنيه\n" . ($b > 0 ? "برجاء السداد في أقرب وقت 🙏" : "حسابك مسدد ✅ شكراً لتعاملكم");
    echo '<tr><td><b>' . e($c['name']) . '</b></td><td dir="ltr">' . e($mob) . '</td><td><b>' . number_format($b) . '</b></td><td>'
      . ($mob !== '' ? '<a target="_blank" href="' . e(wa_link($mob, cfg_shop_name() . " 🌿\nأهلاً أ/ {$c['name']}")) . '"><button>💬 محادثة</button></a> <a target="_blank" href="' . e(wa_link($mob, $rem)) . '"><button class="warn">💰 تذكير بالرصيد</button></a>' : '<span class="note">بلا موبايل</span>')
      . '</td></tr>';
  }
  echo '</table>';
}
echo '</div>';
layout_close();
