<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    public function test_csp_is_report_only_by_default_and_allows_the_services_the_site_uses(): void
    {
        config(['services.chatmesh.url' => 'https://chat.example.test/', 'security.csp_enforce' => false]);

        $response = $this->get('/up')->assertOk();

        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $csp = $response->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString('https://js.stripe.com', $csp);
        $this->assertStringContainsString('https://chat.example.test', $csp);
        $this->assertStringContainsString('wss://chat.example.test', $csp);
    }

    public function test_csp_can_be_enforced(): void
    {
        config(['security.csp_enforce' => true]);

        $response = $this->get('/up')->assertOk();

        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }
}
