<?php
// pages/sync.php — حالة كوبري RS_RS → rs_own (قراءة فقط من الأصل)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
$db = db();
$st = [];
foreach ($db->query("SELECT k,v FROM rs_sync")->fetchAll() as $r) $st[$r['k']] = $r['v'];
$loc = $db->query("SELECT COUNT(*) n, MAX(proc_id) m FROM inv WHERE proc_id<1000000")->fetch();
$b99 = $db->query("SELECT COUNT(*) n FROM inv WHERE branch=99")->fetch()['n'];
$log = is_file('D:/RS_Own/tools/sync.log') ? array_slice(file('D:/RS_Own/tools/sync.log'), -12) : [];
layout_open('كوبري المزامنة', '');
echo '<div class="cards" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">'
  . '<div class="card">آخر proc في الأصل: <b>' . e($st['rs_last_proc'] ?? '—') . '</b><br>آخر تشغيل: ' . e($st['rs_last_run'] ?? '—') . '<br>آخر إضافة: ' . e($st['rs_last_added'] ?? 0) . '</div>'
  . '<div class="card">فواتير الأصل عندنا: <b>' . number_format($loc['n']) . '</b> (حتى ' . $loc['m'] . ')<br>فواتير فرع 99: <b>' . number_format($b99) . '</b> (من مليون — بلا تصادم)</div></div>';
echo '<div class="card"><h3>آخر التشغيلات</h3><pre style="direction:ltr;text-align:left;background:#f8fafc;padding:10px;border-radius:8px;overflow:auto">' . e(implode('', $log)) . '</pre>'
  . '<div class="note">التشغيل من جهاز المحل فقط: <code>php D:\\RS_Own\\tools\\sync_rs_to_own.php</code> (أو --dry للتجربة) — المهمة المجدولة: <code>run_sync_15m.bat</code> كل ربع ساعة. الأصل يُقرأ فقط ولا يُكتب فيه أبداً.</div></div>';
layout_close();
