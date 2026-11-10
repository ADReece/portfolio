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

    /**
     * Quill 2.0.3 (GHSA-v3m3-f69x-jf25) can produce unsafe HTML, so the bio is
     * sanitised server-side on save and on render. These are payloads that
     * slip past naive filters.
     */
    public function test_strips_obfuscated_xss_payloads(): void
    {
        $payloads = [
            '<a href="&#106;avascript:alert(1)">x</a>',
            '<a href="  javascript:alert(1)">x</a>',
            '<a href="JaVaScRiPt:alert(1)">x</a>',
            '<a href="java&#x09;script:alert(1)">x</a>',
            '<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">x</a>',
            '<a href="vbscript:msgbox(1)">x</a>',
            '<svg onload=alert(1)><circle /></svg>',
            '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
            '<p style="background:url(javascript:alert(1))">x</p>',
            '<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>',
            '<p><a href="https://example.com" onmouseover="alert(1)">ok</a></p>',
        ];

        foreach ($payloads as $payload) {
            $out = strtolower(HtmlSanitizer::clean($payload));

            foreach (['javascript', 'vbscript', 'data:', 'onload', 'onerror', 'onmouseover', '<svg', '<iframe', '<img', '<style', 'srcdoc', 'style='] as $needle) {
                $this->assertStringNotContainsString($needle, $out, "Payload kept {$needle}: {$payload}");
            }
        }
    }
}
