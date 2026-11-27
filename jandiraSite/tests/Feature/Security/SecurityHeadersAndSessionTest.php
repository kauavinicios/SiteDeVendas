<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersAndSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_responses_include_owasp_security_headers_and_strict_csp_nonce(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeaderMissing('X-Powered-By')
            ->assertHeaderMissing('Strict-Transport-Security');

        $permissionsPolicy = (string) $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self' https://pay.infinitepay.io", $csp);

        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9+\/=]+'/", $csp);

        preg_match('/script-src ([^;]+)/', $csp, $scriptDirective);
        $this->assertArrayHasKey(1, $scriptDirective);
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptDirective[1]);
    }

    public function test_inertia_pages_share_csp_nonce_with_ziggy_and_vite_scripts(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([^']+)'/", $csp, $matches);
        $this->assertArrayHasKey(1, $matches);
        $nonce = $matches[1];

        $content = (string) $response->getContent();
        $this->assertStringContainsString('nonce="'.$nonce.'"', $content);
    }

    public function test_hsts_and_upgrade_insecure_requests_are_sent_in_production_over_https(): void
    {
        $originalEnv = $this->app['env'];

        try {
            $this->app['env'] = 'production';

            $response = $this->get('https://loja.test/');

            $response
                ->assertOk()
                ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

            $csp = (string) $response->headers->get('Content-Security-Policy');
            $this->assertStringContainsString('upgrade-insecure-requests', $csp);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    public function test_session_encryption_and_http_only_flags_are_enabled_by_default(): void
    {
        $this->assertTrue((bool) config('session.encrypt'));
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }
}
