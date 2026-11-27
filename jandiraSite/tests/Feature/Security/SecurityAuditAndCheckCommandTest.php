<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class SecurityAuditAndCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_audit_redacts_sensitive_keys_recursively(): void
    {
        $capturedContext = null;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(
                'warning',
                'security.custom.test',
                $this->callback(function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;

                    return true;
                }),
            );

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturn($logger);

        SecurityAudit::record('custom.test', [
            'password' => 'minha-senha-secreta',
            'nested' => [
                'api_key' => 'inchk_12345',
                'token_value' => 'tok_abc',
                'safe_note' => 'ok',
            ],
        ], 'warning');

        $this->assertIsArray($capturedContext);
        $this->assertSame('[REDACTED]', $capturedContext['password']);
        $this->assertSame('[REDACTED]', $capturedContext['nested']['api_key']);
        $this->assertSame('[REDACTED]', $capturedContext['nested']['token_value']);
        $this->assertSame('ok', $capturedContext['nested']['safe_note']);
    }

    public function test_invalid_webhook_secret_and_staff_deactivation_emit_security_audit_logs(): void
    {
        config()->set('services.infinitepay.webhook_secret', 'segredo-real-forte-123456');

        $events = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context) use (&$events): void {
                $events[] = ['level' => $level, 'message' => $message, 'context' => $context];
            });

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturn($logger);

        $this->postJson(route('webhooks.infinitepay', ['secret' => 'segredo-errado']), [
            'slug' => 'qualquer',
        ])->assertForbidden();

        $admin = User::factory()->admin()->create();
        $support = User::factory()->support()->create(['active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $support))
            ->assertRedirect();

        $messages = array_column($events, 'message');
        $this->assertContains('security.webhook.invalid_secret', $messages);
        $this->assertContains('security.staff.status_toggled', $messages);
    }

    public function test_security_check_command_validates_testing_and_production_posture(): void
    {
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('session.encrypt', true);
        config()->set('session.http_only', true);
        config()->set('services.infinitepay.driver', 'mock');

        $this->artisan('app:security-check')
            ->expectsOutputToContain('TRUSTED_PROXIES')
            ->expectsOutputToContain('PHP_EXT_GD')
            ->assertExitCode(0);

        $originalEnv = $this->app['env'];

        try {
            $this->app['env'] = 'production';
            config()->set('app.debug', true);
            config()->set('services.infinitepay.driver', 'mock');

            $this->artisan('app:security-check')
                ->assertExitCode(1);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    public function test_trusted_proxies_config_is_honored_by_framework_middleware(): void
    {
        try {
            config()->set('trustedproxy.proxies', '*');

            $this->withServerVariables([
                'REMOTE_ADDR' => '10.0.0.5',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.42',
            ])->get('http://localhost/up')->assertOk();

            $this->assertTrue(request()->isSecure());
            $this->assertSame('203.0.113.42', request()->ip());

            config()->set('trustedproxy.proxies', null);

            $this->withServerVariables([
                'REMOTE_ADDR' => '10.0.0.5',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.42',
            ])->get('http://localhost/up')->assertOk();

            $this->assertFalse(request()->isSecure());
            $this->assertSame('10.0.0.5', request()->ip());
        } finally {
            config()->set('trustedproxy.proxies', null);
        }
    }
}
