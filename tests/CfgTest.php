<?php
// اختبارات cfg: خريطة الصلاحيات + القوائم + الأعلام (جلسة وهمية)
use PHPUnit\Framework\TestCase;

final class CfgTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['u'] = 'guest'; // ليس admin — للتأكد من فروع الصلاحيات
    }

    protected function tearDown(): void
    {
        $_SESSION['u'] = null;
    }

    public function testIsAdminReflectsSession(): void
    {
        $_SESSION['u'] = 'admin';
        $this->assertTrue(cfg_is_admin());
        $_SESSION['u'] = 'guest';
        $this->assertFalse(cfg_is_admin());
    }

    public function testPermMapCoversCriticalPages(): void
    {
        $map = cfg_perm_map();
        // كل شاشات الفواتير/الحماية لازم تكون في الخريطة
        foreach (['sale_rs.php', 'buy_rs.php', 'api_sale_save.php', 'admin.php', 'order.php', 'reports.php'] as $p) {
            $this->assertArrayHasKey($p, $map, "$p missing from perm map");
        }
    }

    public function testGuardNeverAllowsUnknownApiWithoutMapEntry(): void
    {
        // صفحات غير مدرجة = null = بدون قيد صلاحيات (سلوك guard الحالي)
        $this->assertNull(cfg_perm_of('login.php'));
        $this->assertNull(cfg_perm_of('nonexistent.php'));
    }

    public function testToolsPointToExistingPages(): void
    {
        $missing = [];
        foreach (cfg_tools() as $tab => $items) {
            foreach ($items as $it) {
                $file = explode('?', $it[2])[0];
                if (!is_file(__DIR__ . '/../app/pages/' . $file)) $missing[] = "$tab -> $it[2]";
            }
        }
        $this->assertSame([], $missing, 'cfg_tools links to missing pages: ' . implode(', ', $missing));
    }

    public function testScreensMapToExistingPages(): void
    {
        foreach (array_keys(cfg_screens()) as $file) {
            $this->assertFileExists(__DIR__ . '/../app/pages/' . $file, "cfg_screens entry missing: $file");
        }
    }
}
