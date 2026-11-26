<?php

namespace Tests\Feature\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{order: Order, variant: ProductVariant}
     */
    private function createOrderWithVariant(int $stock = 5, int $quantity = 2, float $price = 120.00): array
    {
        $product = Product::factory()->create([
            'active' => true,
            'name' => 'Cobre-leito Casal',
        ]);

        $variant = ProductVariant::factory()->for($product)->create([
            'name' => 'Rosê Queen',
            'sku' => 'COB-ROS-Q',
            'price' => $price,
            'stock' => $stock,
            'active' => true,
        ]);

        $total = round($price * $quantity, 2);

        $order = Order::create([
            'code' => 'BJ-20261126-PAY01',
            'customer_name' => 'Fernanda Lima',
            'customer_email' => 'fernanda@exemplo.com',
            'customer_phone' => '67999998888',
            'status' => OrderStatus::Pending,
            'channel' => 'infinitepay',
            'payment_status' => 'pending',
            'total' => $total,
            'items_count' => $quantity,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'unit_price' => $price,
            'quantity' => $quantity,
            'subtotal' => $total,
        ]);

        return [
            'order' => $order->load('items'),
            'variant' => $variant,
        ];
    }

    public function test_initiate_checkout_records_transaction_updates_order_and_logs_event(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');
        config()->set('services.infinitepay.api_key', '');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'success' => true,
                'url' => 'https://pay.infinitepay.io/slug-abc-123',
                'slug' => 'slug-abc-123',
                'order_nsu' => 'BJ-20261126-PAY01',
                'amount' => 24000,
            ], 200),
        ]);

        ['order' => $order] = $this->createOrderWithVariant();
        $service = app(OrderPaymentService::class);

        $transaction = $service->initiateInfinitePayCheckout(
            order: $order,
            redirectUrl: 'https://loja.test/checkout/retorno',
            webhookUrl: 'https://loja.test/webhooks/infinitepay/secret',
        );

        $this->assertSame(PaymentTransactionStatus::Pending, $transaction->status);
        $this->assertSame('infinitepay', $transaction->provider);
        $this->assertSame('live', $transaction->driver);
        $this->assertSame('handle', $transaction->auth_mode);
        $this->assertSame('slug-abc-123', $transaction->slug);
        $this->assertSame(24000, $transaction->amount_cents);
        $this->assertSame('https://pay.infinitepay.io/slug-abc-123', $transaction->checkout_url);

        $order->refresh();
        $this->assertSame('infinitepay', $order->channel);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('slug-abc-123', $order->payment_reference);

        $this->assertDatabaseHas('payment_events', [
            'payment_transaction_id' => $transaction->id,
            'order_id' => $order->id,
            'source' => 'link_created',
            'event_type' => 'checkout.created',
            'external_event_id' => 'slug-abc-123',
        ]);
    }

    public function test_verify_paid_confirms_order_deducts_stock_and_is_idempotent(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');
        config()->set('services.infinitepay.api_key', '');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'success' => true,
                'url' => 'https://pay.infinitepay.io/slug-paid-1',
                'slug' => 'slug-paid-1',
                'order_nsu' => 'BJ-20261126-PAY01',
                'amount' => 24000,
            ], 200),
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => true,
                'amount' => 24000,
                'paid_amount' => 24000,
                'order_nsu' => 'BJ-20261126-PAY01',
                'transaction_nsu' => 'tx-uuid-999',
                'capture_method' => 'credit_card',
                'installments' => 3,
                'receipt_url' => 'https://pay.infinitepay.io/receipt/tx-uuid-999',
            ], 200),
        ]);

        ['order' => $order, 'variant' => $variant] = $this->createOrderWithVariant(stock: 5, quantity: 2);
        $service = app(OrderPaymentService::class);
        $transaction = $service->initiateInfinitePayCheckout($order);

        $synced = $service->verifyAndSyncTransaction($transaction, 'tx-uuid-999', 'webhook');

        $this->assertSame(PaymentTransactionStatus::Paid, $synced->status);
        $this->assertNotNull($synced->paid_at);
        $this->assertSame('credit_card', $synced->capture_method);
        $this->assertSame(3, $synced->installments);
        $this->assertSame(24000, $synced->paid_amount_cents);
        $this->assertSame('https://pay.infinitepay.io/receipt/tx-uuid-999', $synced->receipt_url);

        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('credit_card', $order->payment_method);
        $this->assertSame('tx-uuid-999', $order->payment_reference);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->stock_deducted_at);
        $this->assertSame(3, $variant->fresh()->stock);

        $this->assertDatabaseHas('payment_events', [
            'payment_transaction_id' => $transaction->id,
            'source' => 'webhook',
            'event_type' => 'payment.paid',
        ]);

        // Segunda verificação não pode baixar estoque novamente
        $service->verifyAndSyncTransaction($synced, 'tx-uuid-999', 'redirect_return');

        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertDatabaseHas('payment_events', [
            'payment_transaction_id' => $transaction->id,
            'source' => 'redirect_return',
            'event_type' => 'payment.paid_duplicate',
        ]);
    }

    public function test_verify_unpaid_keeps_order_pending_without_deducting_stock(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'success' => true,
                'url' => 'https://pay.infinitepay.io/slug-wait-1',
                'slug' => 'slug-wait-1',
                'order_nsu' => 'BJ-20261126-PAY01',
                'amount' => 24000,
            ], 200),
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => false,
                'order_nsu' => 'BJ-20261126-PAY01',
            ], 200),
        ]);

        ['order' => $order, 'variant' => $variant] = $this->createOrderWithVariant(stock: 5, quantity: 2);
        $service = app(OrderPaymentService::class);
        $transaction = $service->initiateInfinitePayCheckout($order);

        $checked = $service->verifyAndSyncTransaction($transaction, null, 'manual_check');

        $this->assertSame(PaymentTransactionStatus::Pending, $checked->status);
        $this->assertNull($checked->paid_at);
        $this->assertNotNull($checked->last_checked_at);

        $order->refresh();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->stock_deducted_at);
        $this->assertSame(5, $variant->fresh()->stock);

        $this->assertDatabaseHas('payment_events', [
            'payment_transaction_id' => $transaction->id,
            'source' => 'manual_check',
            'event_type' => 'payment.pending',
        ]);
    }

    public function test_paid_order_with_insufficient_stock_confirms_payment_and_logs_stock_warning(): void
    {
        config()->set('services.infinitepay.driver', 'live');
        config()->set('services.infinitepay.base_url', 'https://api.checkout.infinitepay.io');
        config()->set('services.infinitepay.handle', 'butiquedajandira');

        Http::fake([
            'https://api.checkout.infinitepay.io/links' => Http::response([
                'success' => true,
                'url' => 'https://pay.infinitepay.io/slug-low-stock',
                'slug' => 'slug-low-stock',
                'order_nsu' => 'BJ-20261126-PAY01',
                'amount' => 24000,
            ], 200),
            'https://api.checkout.infinitepay.io/payment_check' => Http::response([
                'paid' => true,
                'amount' => 24000,
                'paid_amount' => 24000,
                'order_nsu' => 'BJ-20261126-PAY01',
                'transaction_nsu' => 'tx-low-stock',
                'capture_method' => 'pix',
                'installments' => 1,
                'receipt_url' => 'https://pay.infinitepay.io/receipt/tx-low-stock',
            ], 200),
        ]);

        ['order' => $order, 'variant' => $variant] = $this->createOrderWithVariant(stock: 1, quantity: 2);
        $service = app(OrderPaymentService::class);
        $transaction = $service->initiateInfinitePayCheckout($order);

        $synced = $service->verifyAndSyncTransaction($transaction, 'tx-low-stock', 'webhook');

        $this->assertSame(PaymentTransactionStatus::Paid, $synced->status);

        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNull($order->stock_deducted_at);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertStringContainsString('Atenção estoque no pagamento', (string) $order->notes);

        $this->assertDatabaseHas('payment_events', [
            'payment_transaction_id' => $transaction->id,
            'order_id' => $order->id,
            'event_type' => 'stock.insufficient',
        ]);
    }

    public function test_restore_stock_for_order_returns_inventory_and_is_idempotent(): void
    {
        config()->set('services.infinitepay.driver', 'mock');

        ['order' => $order, 'variant' => $variant] = $this->createOrderWithVariant(stock: 5, quantity: 2);
        $service = app(OrderPaymentService::class);

        DB::transaction(function () use ($order, $service): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $service->deductStockForOrder($locked);
            $locked->save();
        });

        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertNotNull($order->fresh()->stock_deducted_at);

        DB::transaction(function () use ($order, $service): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $service->restoreStockForOrder($locked);
            $locked->save();
        });

        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertNull($order->fresh()->stock_deducted_at);

        // Chamada repetida não deve incrementar além de 5
        DB::transaction(function () use ($order, $service): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $service->restoreStockForOrder($locked);
            $locked->save();
        });

        $this->assertSame(5, $variant->fresh()->stock);
    }
}
