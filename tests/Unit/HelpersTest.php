<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_mask_ip_hides_last_octet(): void
    {
        $this->assertSame('192.168.1.***', maskIp('192.168.1.100'));
    }

    public function test_mask_email_hides_most_of_username(): void
    {
        $result = maskEmail('john@example.com');

        $this->assertSame('j***@example.com', $result);
    }

    public function test_mask_email_preserves_domain(): void
    {
        $result = maskEmail('ab@test.org');

        $this->assertStringEndsWith('@test.org', $result);
    }
}
