<?php
// pages/audit.php — عرض سجل التدقيق (عملية/مستخدم/تاريخ/بحث)
require __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/audit.php';
$db = db();
audit_ensure();
$entities = ['', 'sale', 'buy', 'voucher'];
$actions = ['', 'create', 'edit', 'delete'];
$rawE = (string)($_GET['entity'] ?? '');
$rawA = (string)($_GET['action'] ?? '');
$entity = in_array($rawE, $entities, true) ? $rawE : '';
$action = in_array($rawA, $actions, true) ? $rawA : '';
$from = dt($_GET['from'] ?? '', date('Y-m-01'));
$to = dt($_GET['to'] ?? '', date('Y-m-d'));
$where = "ts>='$from 00:00:00' AND ts<='$to 23:59:59'";
$args = [];
if ($entity !== '') { $where .= ' AND entity=?'; $args[] = $entity; }
if ($action !== '') { $where .= ' AND action=?'; $args[] = $action; }
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') { $where .= ' AND (uname LIKE ? OR detail LIKE ? OR CAST(entity_id AS CHAR)=?)'; $args[] = "%$q%"; $args[] = "%$q%"; $args[] = $q; }
$st = $db->prepare("SELECT ts, uid, uname, action, entity, entity_id, detail, ip FROM audit_log WHERE $where ORDER BY id DESC LIMIT 300");
$st->execute($args);
$rows = $st->fetchAll();
$ae = ['create' => '🟢 إنشاء', 'edit' => '🔵 تعديل', 'delete' => '🔴 حذف'];
layout_open('سجل التدقيق', '');
echo '<div class="card"><form method="get" class="row" style="align-items:flex-end">'
  . '<div><label>من</label><br><input name="from" value="' . e($from) . '" style="width:130px"></div>'
  . '<div><label>لـ</label><br><input name="to" value="' . e($to) . '" style="width:130px"></div>'
  . '<div><label>النوع</label><br><select name="entity"><option value="">—</option>'
  . implode('', array_map(fn($x) => '<option value="' . $x . '"' . ($x === $entity ? ' selected' : '') . '>' . ($x === '' ? '' : e($x)) . '</option>', array_slice($entities, 1)))
  . '</select></div>'
  . '<div><label>العملية</label><br><select name="action"><option value="">—</option>'
  . implode('', array_map(fn($x) => '<option value="' . $x . '"' . ($x === $action ? ' selected' : '') . '>' . ($x === '' ? '' : e($ae[$x] ?? $x)) . '</option>', array_slice($actions, 1)))
  . '</select></div>'
  . '<div><label>بحث</label><br><input name="q" value="' . e($q) . '" style="width:160px" placeholder="مستخدم / رقم / تفصيل"></div>'
  . '<div><br><button>🔍 عرض</button></div></form>';
echo '<div class="note">آخر ' . number_format(count($rows)) . ' عملية (حد أقصى 300) في الفترة ' . e($from) . ' → ' . e($to) . '</div></div>';
echo '<div class="card"><table class="grid" style="width:100%"><tr><th>الوقت</th><th>المستخدم</th><th>العملية</th><th>النوع</th><th>رقم</th><th>التفاصيل</th><th>IP</th></tr>';
foreach ($rows as $r) {
  echo '<tr><td style="direction:ltr;text-align:left">' . e($r['ts']) . '</td><td>' . e($r['uname'] ?: ('#' . $r['uid'])) . '</td>'
    . '<td>' . e($ae[$r['action']] ?? $r['action']) . '</td><td>' . e($r['entity']) . '</td>'
    . '<td>' . (int)$r['entity_id'] . '</td><td style="text-align:left;direction:ltr;font-size:12px">' . e($r['detail']) . '</td>'
    . '<td style="direction:ltr;text-align:left">' . e($r['ip']) . '</td></tr>';
}
if (!$rows) echo '<tr><td colspan="7" style="padding:14px">مافيش سجلات في الفترة دي</td></tr>';
echo '</table></div>';
layout_close();
