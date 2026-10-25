<?php
// تحديث جدول الأرصدة (يعمل في الخلفية — قد يأخذ دقيقة)
require __DIR__ . '/lib/db.php';
$db = db();
$db->exec("REPLACE INTO item_bal SELECT s.currency_from, ROUND(SUM(CASE WHEN (i.IS_Buy=1 AND i.proc_type=1) OR i.proc_type=3 OR (i.IS_Buy=0 AND i.proc_type=2) THEN s.val ELSE -s.val END), 4), NOW() FROM inv_sub s JOIN inv i ON i.proc_id=s.proc_id AND i.IS_Buy=IF(s.proc_type=1,1,0) WHERE i.IS_Deleted=0 AND IFNULL(i.IS_Temp,0)=0 AND s.proc_type IN (1,2) GROUP BY s.currency_from");
$db->exec("INSERT IGNORE INTO item_bal (id, bal, updated) SELECT id, 0, NOW() FROM currencies");
header('Location: pages/stock.php?r=balances');
