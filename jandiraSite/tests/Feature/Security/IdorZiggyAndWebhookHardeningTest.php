<?php

namespace Tests\Feature\Security;

use App\Enums\OrderStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdorZiggyAndWebhookHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_return_blocks_idor_from_anonymous_or_other_users_and_allows_valid_token_or_owner(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');

        $owner = User::factory()->customer()->create([
            'name' => 'Dona Privada',
            'email' => 'dona@exemplo.com',
        ]);
        $attacker = User::factory()->customer()->create();

        $order = Order::create([
            'code' => 'BJ-20261127-IDOR1',
            'user_id' => $owner->id,
            'customer_name' => 'Dona Privada',
            'customer_email' => 'dona@exemplo.com',
            'customer_phone' => '67999990001',
            'status' => OrderStatus::Confirmed,
            'channel' => 'infinitepay',
            'payment_status' => 'paid',
            'total' => 320.00,
            'items_count' => 1,
        ]);

        $order->items()->create([
            'product_name' => 'Item Sigiloso',
            'variant_name' => 'Único',
            'unit_price' => 320.00,
            'quantity' => 1,
            'subtotal' => 320.00,
        ]);

        $validToken = 'token-retorno-secreto-1234567890';

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'infinitepay',
            'driver' => 'live',
            'auth_mode' => 'handle',
            'order_nsu' => $order->code,
            'slug' => 'slug-idor-privado',
            'return_token_hash' => hash('sha256', $validToken),
            'transaction_nsu' => 'tx-idor-1',
            'receipt_url' => 'https://pay.infinitepay.io/receipt/privado',
            'status' => PaymentTransactionStatus::Paid,
            'capture_method' => 'pix',
            'installments' => 1,
            'amount_cents' => 32000,
            'paid_amount_cents' => 32000,
            'paid_at' => now(),
        ]);

        Http::fake();

        // 1) Visitante sem token nem sessão não vê dados nem dispara payment_check
        $this->get(route('checkout.return', [
            'order_nsu' => $transaction->order_nsu,
            'slug' => $transaction->slug,
        ]))
            ->assertOk()
            ->assertSee('Não foi possível localizar esta transação')
            ->assertDontSee('Dona Privada')
            ->assertDontSee('Item Sigiloso')
            ->assertDontSee('https://pay.infinitepay.io/receipt/privado');

        // 2) Outro cliente com token inválido também é bloqueado
        $this->actingAs($attacker)
            ->get(route('checkout.return', [
                'order_nsu' => $transaction->order_nsu,
                'slug' => $transaction->slug,
                'rt' => 'token-adivinhado',
            ]))
            ->assertOk()
            ->assertSee('Não foi possível localizar esta transação')
            ->assertDontSee('Dona Privada');

        Http::assertNothingSent();

        // 3) Token válido ou dono autenticado têm acesso liberado
        Http::fake([
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => true,
                'amount' => 32000,
                'paid_amount' => 32000,
                'order_nsu' => $order->code,
                'transaction_nsu' => 'tx-idor-1',
                'capture_method' => 'pix',
                'installments' => 1,
                'receipt_url' => 'https://pay.infinitepay.io/receipt/privado',
            ], 200),
        ]);

        auth()->logout();

        $this->get(route('checkout.return', [
            'order_nsu' => $transaction->order_nsu,
            'slug' => $transaction->slug,
            'rt' => $validToken,
        ]))
            ->assertOk()
            ->assertSee('Dona Privada')
            ->assertSee('Item Sigiloso')
            ->assertSee('https://pay.infinitepay.io/receipt/privado');

        $this->actingAs($owner)
            ->get(route('checkout.return', [
                'order_nsu' => $transaction->order_nsu,
                'slug' => $transaction->slug,
            ]))
            ->assertOk()
            ->assertSee('Dona Privada')
            ->assertSee('https://pay.infinitepay.io/receipt/privado');
    }

    public function test_ziggy_does_not_leak_admin_webhook_or_dev_routes_on_public_auth_pages(): void
    {
        $loginResponse = $this->get(route('login'))->assertOk();
        $loginHtml = (string) $loginResponse->getContent();

        $this->assertStringContainsString('"login"', $loginHtml);
        $this->assertStringContainsString('"register"', $loginHtml);
        $this->assertStringNotContainsString('admin.users.index', $loginHtml);
        $this->assertStringNotContainsString('admin.orders.index', $loginHtml);
        $this->assertStringNotContainsString('webhooks.infinitepay', $loginHtml);
        $this->assertStringNotContainsString('dev.infinitepay.checkout', $loginHtml);

        $admin = User::factory()->admin()->create();
        $adminResponse = $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $adminHtml = (string) $adminResponse->getContent();

        $this->assertStringContainsString('admin.orders.index', $adminHtml);
        $this->assertStringNotContainsString('webhooks.infinitepay', $adminHtml);
    }

    public function test_unmatched_webhook_sanitizes_oversized_payload_before_persisting_event(): void
    {
        config()->set('services.infinitepay.webhook_secret', 'segredo-wh-123');

        $payload = [
            'slug' => 'slug-inexistente',
            'long_field' => str_repeat('A', 500),
            'nested' => ['a' => 1, 'b' => 2],
        ];

        for ($i = 0; $i < 25; $i++) {
            $payload["extra_{$i}"] = "valor_{$i}";
        }

        $this->postJson(route('webhooks.infinitepay', ['secret' => 'segredo-wh-123']), $payload)
            ->assertNotFound();

        $event = PaymentEvent::where('event_type', 'webhook.unmatched')->firstOrFail();

        $this->assertLessThanOrEqual(20, count($event->payload));
        $this->assertLessThanOrEqual(203, strlen((string) $event->payload['long_field']));
        $this->assertSame('[array]', $event->payload['nested']);
    }
}
