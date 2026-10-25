<?php
// اختبارات الدوال النقية (بدون قاعدة بيانات)
use PHPUnit\Framework\TestCase;

final class ValidatorsTest extends TestCase
{
    public function testDtAcceptsValidDate(): void
    {
        $this->assertSame('2026-10-20', dt('2026-10-20'));
    }

    public function testDtRejectsInjection(): void
    {
        $this->assertSame('', dt("' OR '1'='1"));
        $this->assertSame('fb', dt('2026-13-40', 'fb'));   // شهر غير صالح
        $this->assertSame('fb', dt('DROP TABLE users', 'fb'));
        $this->assertSame('fb', dt(null, 'fb'));
    }

    public function testYrAcceptsYear(): void
    {
        $this->assertSame('2026', yr('2026'));
        $this->assertSame('', yr('2026-10-20'));
        $this->assertSame('x', yr('abc OR 1=1', 'x'));
    }

    public function testFmtRoundsAndFormats(): void
    {
        $this->assertSame('4,041,527', fmt(4041527.47));
        $this->assertSame('0', fmt('abc'));
    }

    public function testEescapesHtml(): void
    {
        $this->assertSame('&lt;b&gt;&#039;x&#039;&lt;/b&gt;', e("<b>'x'</b>"));
    }
}
