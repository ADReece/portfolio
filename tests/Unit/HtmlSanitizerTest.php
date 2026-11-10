<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_keeps_basic_formatting(): void
    {
        $this->assertSame('<p>Hi <strong>there</strong></p>', HtmlSanitizer::clean('<p>Hi <strong>there</strong></p>'));
    }

    public function test_removes_scripts_handlers_and_unsafe_links(): void
    {
        $out = HtmlSanitizer::clean('<p onclick="x()">a</p><script>alert(1)</script><a href="javascript:alert(1)">b</a><img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('script', $out);
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('<img', $out);
        $this->assertStringContainsString('<a>b</a>', $out);
    }

    public function test_safe_links_keep_href_and_open_safely(): void
    {
        $out = HtmlSanitizer::clean('<a href="https://example.com" style="color:red">site</a>');

        $this->assertSame('<a href="https://example.com" rel="nofollow noopener" target="_blank">site</a>', $out);
    }
}
