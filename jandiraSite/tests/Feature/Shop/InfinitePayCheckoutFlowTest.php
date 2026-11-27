<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InfinitePayCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_checkout_in_mock_mode_creates_transaction_and_completes_via_simulator(): void
    {
        config()->set('services.infinitepay.driver', 'mock');

        $customer = User::factory()->customer()->create([
            'name' => 'Juliana Costa',
            'email' => 'juliana@exemplo.com',
            'phone' => '67999992222',
        ]);

        $product = Product::factory()->create(['active' => true, 'name' => 'Jogo de Cama Queen']);
        $variant = ProductVariant::factory()->for($product)->create([
            'name' => 'Percal Rosê',
            'price' => 180.00,
            'cost_price' => 95.00,
            'stock' => 6,
            'active' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.index'));

        $response = $this->actingAs($customer)
            ->postJson(route('orders.store'), [
                'customer_name' => 'Juliana Costa',
                'customer_phone' => '67999992222',
                'customer_email' => 'juliana@exemplo.com',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment.provider', 'infinitepay')
            ->assertJsonPath('payment.driver', 'mock')
            ->assertJsonPath('payment.status', 'pending');

        $order = Order::with('latestPaymentTransaction')->firstOrFail();
        $transaction = $order->latestPaymentTransaction;

        $this->assertNotNull($transaction);
        $this->assertSame('infinitepay', $order->channel);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(36000, $transaction->amount_cents);
        $this->assertSame(6, $variant->fresh()->stock);
        $this->assertEmpty(session('cart', []));
        $this->assertStringContainsString("/dev/infinitepay/checkout/{$transaction->slug}", (string) $response->json('checkout_url'));

        // Abre simulador local e aprova em 3x no cartão
        $this->get(route('dev.infinitepay.checkout', ['slug' => $transaction->slug]))
            ->assertOk()
            ->assertSee($order->code);

        $simulateResponse = $this->post(route('dev.infinitepay.simulate', ['slug' => $transaction->slug]), [
            'action' => 'pay_card_3x',
        ]);

        $simulateResponse->assertRedirect();

        $this->followRedirects($simulateResponse)
            ->assertOk()
            ->assertSee('Pagamento confirmado')
            ->assertSee('Cartão de crédito')
            ->assertSee('(3x)')
            ->assertSee('Ver comprovante oficial');

        $order->refresh();
        $transaction->refresh();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('credit_card', $order->payment_method);
        $this->assertNotNull($order->stock_deducted_at);
        $this->assertSame(4, $variant->fresh()->stock);

        $this->assertSame(PaymentTransactionStatus::Paid, $transaction->status);
        $this->assertSame(3, $transaction->installments);
        $this->assertNotNull($transaction->receipt_url);

        $this->get(route('dev.infinitepay.receipt', ['slug' => $transaction->slug]))
            ->assertOk()
            ->assertSee($order->code);
    }

    public function test_checkout_return_ignores_forged_query_params_when_payment_check_reports_unpaid(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');
        config()->set('services.infinitepay.api_key', '');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'success' => true,
                'url' => 'https://pay.infinitepay.io/slug-forjado-1',
                'slug' => 'slug-forjado-1',
                'order_nsu' => 'BJ-FORGE-01',
                'amount' => 15000,
            ], 200),
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => false,
                'order_nsu' => 'BJ-FORGE-01',
            ], 200),
        ]);

        $product = Product::factory()->create(['active' => true]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 150.00,
            'stock' => 5,
            'active' => true,
        ]);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->postJson(route('orders.store'), [
            'customer_name' => 'Cliente Teste',
            'customer_phone' => '67999990000',
        ])->assertCreated();

        $transaction = PaymentTransaction::firstOrFail();

        $this->get(route('checkout.return', [
            'order_nsu' => $transaction->order_nsu,
            'slug' => $transaction->slug,
            'transaction_nsu' => 'tx-forjado-na-url',
            'capture_method' => 'pix',
        ]))
            ->assertOk()
            ->assertSee('Aguardando confirmação')
            ->assertSee('Retomar pagamento na InfinitePay');

        $this->assertSame(OrderStatus::Pending, $transaction->order->fresh()->status);
        $this->assertSame('pending', $transaction->order->fresh()->payment_status);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_webhook_requires_valid_secret_verifies_via_payment_check_and_is_idempotent(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');
        config()->set('services.infinitepay.webhook_secret', 'segredo-webhook-123');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'success' => true,
                'url' => 'https://pay.infinitepay.io/slug-wh-1',
                'slug' => 'slug-wh-1',
                'amount' => 20000,
            ], 200),
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => true,
                'amount' => 20000,
                'paid_amount' => 20000,
                'transaction_nsu' => 'tx-webhook-001',
                'capture_method' => 'pix',
                'installments' => 1,
                'receipt_url' => 'https://pay.infinitepay.io/receipt/tx-webhook-001',
            ], 200),
        ]);

        $product = Product::factory()->create(['active' => true]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 100.00,
            'stock' => 7,
            'active' => true,
        ]);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->postJson(route('orders.store'), [
            'customer_name' => 'Mariana Dias',
            'customer_phone' => '67999995555',
        ])->assertCreated();

        $transaction = PaymentTransaction::firstOrFail();

        // Segredo inválido -> 403
        $this->postJson(route('webhooks.infinitepay', ['secret' => 'errado']), [
            'order_nsu' => $transaction->order_nsu,
            'slug' => $transaction->slug,
            'transaction_nsu' => 'tx-webhook-001',
        ])->assertForbidden();

        // Segredo válido -> 200, confirma pedido e baixa estoque
        $this->postJson(route('webhooks.infinitepay', ['secret' => 'segredo-webhook-123']), [
            'order_nsu' => $transaction->order_nsu,
            'slug' => $transaction->slug,
            'transaction_nsu' => 'tx-webhook-001',
        ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'paid' => true,
                'status' => 'paid',
            ]);

        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame(OrderStatus::Confirmed, $transaction->order->fresh()->status);

        // Webhook duplicado -> idempotente, estoque não baixa de novo
        $this->postJson(route('webhooks.infinitepay', ['secret' => 'segredo-webhook-123']), [
            'order_nsu' => $transaction->order_nsu,
            'slug' => $transaction->slug,
            'transaction_nsu' => 'tx-webhook-001',
        ])
            ->assertOk()
            ->assertJson(['ok' => true, 'paid' => true]);

        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertDatabaseHas('payment_events', [
            'payment_transaction_id' => $transaction->id,
            'source' => 'webhook',
            'event_type' => 'payment.paid_duplicate',
        ]);
    }

    public function test_cart_is_preserved_and_orphan_order_removed_when_infinitepay_fails(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'message' => 'Erro temporário na InfinitePay.',
            ], 500),
        ]);

        $product = Product::factory()->create(['active' => true]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 90.00,
            'stock' => 4,
            'active' => true,
        ]);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->postJson(route('orders.store'), [
            'customer_name' => 'Patrícia Alves',
            'customer_phone' => '67999994444',
        ])->assertStatus(502)->assertJsonPath('success', false);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertNotEmpty(session('cart', []));
    }
}
