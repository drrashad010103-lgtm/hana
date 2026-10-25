<?php
// lib/seq.php — مسلسلات آمنة (Oracle-style sequences) بدل MAX+1 — ضد الحفظ المتزامن
if (!function_exists('db')) require __DIR__ . '/db.php';

if (!function_exists('seq_init')) {
function seq_init(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  if (db()->inTransaction()) return; // لا DDL جوه ترانزاكشن — لازم seq_init() قبلها
  db()->exec("CREATE TABLE IF NOT EXISTS rs_seq (name VARCHAR(32) PRIMARY KEY, val BIGINT NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  // آخر رقم مستعمل عموماً: عند المهندس (branch<>99 + rs_sync) وعندنا في فرع 99
  // — لازم نراعي فواتير فرع 99 المنشأة مسبقاً وإلا زرعنا رقماً مكرراً (Duplicate على ux_inv_proc_id)
  $rsP = $rsI = 0;
  if (db()->query("SHOW TABLES LIKE 'rs_sync'")->fetch()) {
    foreach (db()->query("SELECT k,v FROM rs_sync WHERE k IN ('rs_last_proc','rs_max_id')") as $r) {
      if ($r['k'] === 'rs_last_proc') $rsP = (int)$r['v'];
      else $rsI = (int)$r['v'];
    }
  }
  // استعلام فوري O(1) عبر الفهرس بدلاً من Full Table Scan — عبر الفهرس الفريد ux_inv_proc_id
  $p = (int)db()->query("SELECT MAX(proc_id) FROM inv")->fetchColumn();
  $i = (int)db()->query("SELECT MAX(id) FROM inv")->fetchColumn();
  $s = (int)db()->query("SELECT IFNULL(MAX(id),0) FROM inv_sub")->fetchColumn();
  // نزرع "آخر رقم مستعمل" ثم نزيد 1 — والـ +1 يحترم أن رقمه التالي عند المهندس قد يكون لم يُسحب بعد
  // الحد الأدنى 10,000,000: ترقيم فروعنا المحلية (99) يبدأ من عشرة مليون دائماً —
  // لا يتصادق مع المهندس (رقميه < مليون عملياً) حتى بعد إعادة تهيئة القاعدة من الدامب
  $base = [
    'seq_proc' => max($p, $rsP, 10000000) + 1,
    'seq_inv'  => max($i, $rsI, 10000000) + 1,
    'seq_sub'  => max($s, 1000),
  ];
  $st = db()->prepare("INSERT INTO rs_seq (name,val) VALUES (?,?) ON DUPLICATE KEY UPDATE val=GREATEST(val,VALUES(val))");
  foreach ($base as $k => $v) $st->execute([$k, $v]);
}
/**
 * لا تسلّم رقماً مستعملاً — لا عند المهندس ولا في فرعنا (99) — فقد يُستورد
 * بعد تهيئة المسلسل أو يُنشأ محلياً. يحمي من تصادم proc_id/id (قيد ux_inv_proc_id الفريد).
 * استعلام فوري O(1) عبر الفهرس الفريد.
 */
function seq_avoid_his(string $name, int $v): int {
  if ($name !== 'seq_proc' && $name !== 'seq_inv') return $v;
  $col = $name === 'seq_proc' ? 'proc_id' : 'id';
  $his = (int)db()->query("SELECT MAX(`$col`) FROM inv")->fetchColumn();
  if ($v > $his) return $v;
  $v = $his + 1;
  db()->prepare("UPDATE rs_seq SET val=? WHERE name=?")->execute([$v, $name]);
  return $v;
}
function seq_next(string $name): int {
  seq_init();
  $db = db();
  $wasTx = $db->inTransaction();
  if (!$wasTx) $db->beginTransaction();
  try {
    $db->prepare("UPDATE rs_seq SET val=LAST_INSERT_ID(val+1) WHERE name=?")->execute([$name]);
    $v = (int)$db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
    $v = seq_avoid_his($name, $v);
    if (!$wasTx) $db->commit();
    return $v;
  } catch (Throwable $t) {
    if (!$wasTx && $db->inTransaction()) $db->rollBack();
    throw $t;
  }
}

/**
 * مسلسل لنصف جداول الماسترز (بدل MAX+1 المعرّض للسباقات).
 * يُزرع الرقم أول مرة من أقصى قيمة موجودة بعد ذلك يزيد بآمن.
 * الاستخدام: seq_next_table('users') أو seq_next_table('restrictions','doc_no')
 * ملاحظة: استدعِه قبل beginTransaction (الزرع لا يعمل داخل ترانزاكشن).
 */
function seq_next_table(string $table, string $col = 'id'): int {
  if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,30}$/', $table) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,30}$/', $col)) {
    throw new InvalidArgumentException('seq: اسم جدول/عمود غير صالح');
  }
  seq_init();
  $db = db();
  $name = 'tbl_' . $table . '_' . $col;
  $q = $db->prepare("SELECT val FROM rs_seq WHERE name=?");
  $q->execute([$name]);
  if ($q->fetchColumn() === false) {
    if ($db->inTransaction()) throw new RuntimeException('seq: يجب تهيئة المسلسل قبل بدء الترانزاكشن');
    try {
      $db->prepare("INSERT INTO rs_seq (name,val) SELECT ?, IFNULL(MAX(`$col`),0) FROM `$table`")->execute([$name]);
    } catch (Throwable $t) {
      if (strpos($t->getMessage(), 'Duplicate entry') === false) throw $t;
    }
  }
  return seq_next($name);
}
}
