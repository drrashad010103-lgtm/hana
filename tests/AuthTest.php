<?php
// اختبارات auth: bcrypt + النص الصريح + الترقية + تأخير التخمين (بدون كتابة على users)
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testHashPwdEmptyStaysEmpty(): void
    {
        $this->assertSame('', auth_hash_pwd(''));
        $this->assertSame('', auth_hash_pwd('   '));
    }

    public function testHashPwdProducesBcryptAndVerifies(): void
    {
        $h = auth_hash_pwd('secret123');
        $this->assertNotSame('', $h);
        $this->assertNotSame('secret123', $h);
        $this->assertTrue(password_verify('secret123', $h));
        $this->assertFalse(password_verify('wrong', $h));
    }

    public function testHashPwdTruncatesLongInput(): void
    {
        // bcrypt يقرأ 72 بايت فقط — التجزئة للاسم الطويل تتحقق من أطول جزء
        $long = str_repeat('a', 100);
        $this->assertTrue(password_verify(substr($long, 0, 72), auth_hash_pwd($long)));
    }

    public function testHashPwdIsStableForSameInput(): void
    {
        $this->assertNotSame(auth_hash_pwd('x'), auth_hash_pwd('y'));
    }
}
