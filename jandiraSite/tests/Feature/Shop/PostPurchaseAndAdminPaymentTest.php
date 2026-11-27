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

class PostPurchaseAndAdminPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_account_shows_resume_payment_for_pending_and_receipt_for_paid_orders(): void
    {
        $customer = User::factory()->customer()->create();

        $pendingOrder = Order::create([
            'code' => 'BJ-20261127-PEND1',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '67999991111',
            'status' => OrderStatus::Pending,
            'channel' => 'infinitepay',
            'payment_status' => 'pending',
            'total' => 150.00,
            'items_count' => 1,
        ]);

        PaymentTransaction::create([
            'order_id' => $pendingOrder->id,
            'provider' => 'infinitepay',
            'driver' => 'mock',
            'auth_mode' => 'mock',
            'order_nsu' => $pendingOrder->code,
            'slug' => 'mock-pending-123',
            'checkout_url' => 'https://pay.infinitepay.io/mock-pending-123',
            'status' => PaymentTransactionStatus::Pending,
            'amount_cents' => 15000,
        ]);

        $paidOrder = Order::create([
            'code' => 'BJ-20261127-PAID1',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '67999991111',
            'status' => OrderStatus::Confirmed,
            'channel' => 'infinitepay',
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'total' => 300.00,
            'items_count' => 2,
            'confirmed_at' => now(),
        ]);

        PaymentTransaction::create([
            'order_id' => $paidOrder->id,
            'provider' => 'infinitepay',
            'driver' => 'live',
            'auth_mode' => 'handle',
            'order_nsu' => $paidOrder->code,
            'slug' => 'live-paid-456',
            'transaction_nsu' => 'tx-live-456',
            'checkout_url' => 'https://pay.infinitepay.io/live-paid-456',
            'receipt_url' => 'https://pay.infinitepay.io/receipt/tx-live-456',
            'status' => PaymentTransactionStatus::Paid,
            'capture_method' => 'credit_card',
            'installments' => 3,
            'amount_cents' => 30000,
            'paid_amount_cents' => 30000,
            'paid_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('Retomar pagamento na InfinitePay')
            ->assertSee('https://pay.infinitepay.io/mock-pending-123')
            ->assertSee('Ver comprovante oficial')
            ->assertSee('https://pay.infinitepay.io/receipt/tx-live-456')
            ->assertSee('Cartão de crédito')
            ->assertSee('(3x)');
    }

    public function test_authorized_staff_can_manually_verify_infinitepay_payment(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');

        Http::fake([
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => true,
                'amount' => 20000,
                'paid_amount' => 20000,
                'order_nsu' => 'BJ-20261127-MAN01',
                'transaction_nsu' => 'tx-manual-001',
                'capture_method' => 'pix',
                'installments' => 1,
                'receipt_url' => 'https://pay.infinitepay.io/receipt/tx-manual-001',
            ], 200),
        ]);

        $storekeeper = User::factory()->storekeeper()->create();
        $product = Product::factory()->create(['active' => true]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 100.00,
            'stock' => 5,
            'active' => true,
        ]);

        $order = Order::create([
            'code' => 'BJ-20261127-MAN01',
            'customer_name' => 'Cláudia Ferreira',
            'customer_phone' => '67999993333',
            'status' => OrderStatus::Pending,
            'channel' => 'infinitepay',
            'payment_status' => 'pending',
            'total' => 200.00,
            'items_count' => 2,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'unit_price' => 100.00,
            'quantity' => 2,
            'subtotal' => 200.00,
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'infinitepay',
            'driver' => 'live',
            'auth_mode' => 'handle',
            'order_nsu' => $order->code,
            'slug' => 'slug-manual-001',
            'status' => PaymentTransactionStatus::Pending,
            'amount_cents' => 20000,
        ]);

        $this->actingAs($storekeeper)
            ->post(route('admin.orders.verify-payment', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame(PaymentTransactionStatus::Paid, $transaction->fresh()->status);

        $this->assertDatabaseHas('payment_events', [
            'payment_transaction_id' => $transaction->id,
            'source' => 'manual_check',
            'event_type' => 'payment.paid',
        ]);
    }

    public function test_unauthorized_roles_cannot_verify_payment_and_legacy_orders_show_friendly_error(): void
    {
        $manager = User::factory()->manager()->create();
        $support = User::factory()->support()->create();
        $customer = User::factory()->customer()->create();

        $legacyOrder = Order::create([
            'code' => 'BJ-20261127-LEG01',
            'customer_name' => 'Cliente WhatsApp',
            'customer_phone' => '67999990000',
            'status' => OrderStatus::Pending,
            'channel' => 'whatsapp',
            'payment_status' => 'pending',
            'total' => 100.00,
            'items_count' => 1,
        ]);

        $this->actingAs($support)
            ->post(route('admin.orders.verify-payment', $legacyOrder))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.orders.verify-payment', $legacyOrder))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('admin.orders.verify-payment', $legacyOrder))
            ->assertRedirect()
            ->assertSessionHas('error', 'Este pedido não possui uma transação InfinitePay vinculada.');
    }
}
