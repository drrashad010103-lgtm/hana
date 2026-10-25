<?php
// فحص قاعدة البيانات: اتصال + جداول أساسية (قراءة فقط — لا كتابة)
use PHPUnit\Framework\TestCase;

final class DbTest extends TestCase
{
    public function testConnectionAndCoreTables(): void
    {
        $db = db();
        $this->assertInstanceOf(PDO::class, $db);
        foreach (['inv', 'inv_sub', 'currencies', 'item_bal', 'users'] as $t) {
            $n = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $this->assertNotFalse($n, "table $t unreachable");
        }
    }

    public function testBranch99IsOnlyWriterIsolation(): void
    {
        // عزل البيانات: لا فواتير فرع 99 سليمة (غير محذوفة) بلا بنود
        $db = db();
        $orphans = $db->query("SELECT COUNT(*) FROM inv i WHERE i.branch=99 AND i.IS_Deleted=0 AND NOT EXISTS (SELECT 1 FROM inv_sub s WHERE s.proc_id=i.proc_id)")->fetchColumn();
        $this->assertEquals(0, (int)$orphans, 'active branch-99 invoices without lines');
    }
}
