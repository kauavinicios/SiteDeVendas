<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_manager_can_view_profit_and_demand_analytics(): void
    {
        $manager = User::factory()->manager()->create();
        $customer = User::factory()->customer()->create();

        $category = Category::factory()->create(['name' => 'Perfumaria']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Perfume Luxo',
            'active' => true,
        ]);

        $variantWithCost = ProductVariant::factory()->for($product)->create([
            'price' => 200.00,
            'cost_price' => 110.00,
            'stock' => 5,
        ]);

        $variantWithoutCost = ProductVariant::factory()->for($product)->outOfStock()->create([
            'name' => 'Refil 50ml',
            'price' => 100.00,
            'cost_price' => null,
        ]);

        // Pedido confirmado dentro do período
        $confirmedOrder = Order::create([
            'code' => 'BJ-20261126-ANL1',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => '67999990000',
            'status' => OrderStatus::Delivered,
            'total' => 500.00,
            'total_cost' => 220.00,
            'items_count' => 3,
        ]);

        $confirmedOrder->items()->createMany([
            [
                'product_id' => $product->id,
                'product_variant_id' => $variantWithCost->id,
                'product_name' => $product->name,
                'variant_name' => $variantWithCost->name,
                'unit_price' => 200.00,
                'unit_cost' => 110.00,
                'quantity' => 2,
                'subtotal' => 400.00,
            ],
            [
                'product_id' => $product->id,
                'product_variant_id' => $variantWithoutCost->id,
                'product_name' => $product->name,
                'variant_name' => $variantWithoutCost->name,
                'unit_price' => 100.00,
                'unit_cost' => null,
                'quantity' => 1,
                'subtotal' => 100.00,
            ],
        ]);

        // Pedido cancelado e pedido antigo (fora de 7d) não devem entrar na receita de 7d
        Order::create([
            'code' => 'BJ-20261126-ANL2',
            'customer_name' => 'Cancelado',
            'customer_phone' => '67999990001',
            'status' => OrderStatus::Cancelled,
            'total' => 999.00,
            'items_count' => 1,
        ]);

        $oldOrder = Order::create([
            'code' => 'BJ-20261001-OLD1',
            'customer_name' => 'Antigo',
            'customer_phone' => '67999990002',
            'status' => OrderStatus::Confirmed,
            'total' => 800.00,
            'items_count' => 1,
        ]);
        $oldOrder->forceFill(['created_at' => now()->subDays(20)])->save();

        // Favorito e alerta de estoque pendente
        $customer->favorites()->create(['product_id' => $product->id, 'notify_on_restock' => true]);
        StockAlert::create([
            'product_variant_id' => $variantWithoutCost->id,
            'email' => 'alerta@example.com',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.analytics.index', ['periodo' => '7d']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/analytics/index')
                ->where('period', '7d')
                ->where('kpis.gross_revenue', 500)
                ->where('kpis.revenue_with_cost', 400)
                ->where('kpis.estimated_cost', 220)
                ->where('kpis.estimated_profit', 180)
                ->where('kpis.margin_percent', 45)
                ->where('kpis.items_without_cost', 1)
                ->where('kpis.confirmed_orders', 1)
                ->where('kpis.cancelled_orders', 1)
                ->where('kpis.pending_stock_alerts', 1)
                ->where('kpis.total_favorites', 1)
                ->has('topProducts', 1)
                ->has('mostFavorited', 1)
                ->has('mostRequestedRestock', 1)
            );
    }

    public function test_unauthorized_roles_cannot_access_financial_reports(): void
    {
        $storekeeper = User::factory()->storekeeper()->create();
        $support = User::factory()->support()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($storekeeper)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($support)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.analytics.index'))->assertForbidden();
    }

    public function test_analytics_calculates_infinitepay_payment_metrics_accurately(): void
    {
        $admin = User::factory()->admin()->create();
        $baseNow = CarbonImmutable::now();

        $order = Order::create([
            'code' => 'BJ-20261127-INF01',
            'customer_name' => 'Patrícia Ramos',
            'customer_phone' => '67999997777',
            'status' => OrderStatus::Confirmed,
            'channel' => 'infinitepay',
            'payment_status' => 'paid',
            'total' => 450.00,
            'items_count' => 2,
        ]);

        // 1) Pix pago em 4 minutos (150,00 -> 150,00)
        $pixTx = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'infinitepay',
            'driver' => 'live',
            'auth_mode' => 'handle',
            'order_nsu' => $order->code,
            'slug' => 'slug-pix-1',
            'transaction_nsu' => 'tx-pix-1',
            'status' => PaymentTransactionStatus::Paid,
            'capture_method' => 'pix',
            'installments' => 1,
            'amount_cents' => 15000,
            'paid_amount_cents' => 15000,
            'paid_at' => $baseNow->subMinutes(6),
        ]);
        $pixTx->forceFill(['created_at' => $baseNow->subMinutes(10)])->save();

        // 2) Cartão de crédito 3x pago em 10 minutos (300,00 cobrado -> 315,00 pago)
        $cardTx = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'infinitepay',
            'driver' => 'live',
            'auth_mode' => 'handle',
            'order_nsu' => $order->code,
            'slug' => 'slug-card-2',
            'transaction_nsu' => 'tx-card-2',
            'status' => PaymentTransactionStatus::Paid,
            'capture_method' => 'credit_card',
            'installments' => 3,
            'amount_cents' => 30000,
            'paid_amount_cents' => 31500,
            'paid_at' => $baseNow->subMinutes(10),
        ]);
        $cardTx->forceFill(['created_at' => $baseNow->subMinutes(20)])->save();

        // 3) Link pendente dentro do período
        PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'infinitepay',
            'driver' => 'mock',
            'auth_mode' => 'mock',
            'order_nsu' => $order->code,
            'slug' => 'slug-pend-3',
            'status' => PaymentTransactionStatus::Pending,
            'amount_cents' => 10000,
        ]);

        // 4) Transação antiga (fora da janela de 7 dias) não deve entrar
        $oldTx = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'infinitepay',
            'driver' => 'live',
            'auth_mode' => 'handle',
            'order_nsu' => $order->code,
            'slug' => 'slug-old-4',
            'status' => PaymentTransactionStatus::Paid,
            'capture_method' => 'pix',
            'installments' => 1,
            'amount_cents' => 90000,
            'paid_amount_cents' => 90000,
            'paid_at' => $baseNow->subDays(20),
        ]);
        $oldTx->forceFill(['created_at' => $baseNow->subDays(20)])->save();

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['periodo' => '7d']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/analytics/index')
                ->where('paymentMetrics.funnel.created_links', 3)
                ->where('paymentMetrics.funnel.paid_links', 2)
                ->where('paymentMetrics.funnel.pending_links', 1)
                ->where('paymentMetrics.funnel.failed_or_cancelled', 0)
                ->where('paymentMetrics.funnel.checkout_conversion_rate', 66.7)
                ->where('paymentMetrics.avg_time_to_pay_minutes', 7)
                ->where('paymentMetrics.amounts.expected_paid_total', 450)
                ->where('paymentMetrics.amounts.actual_paid_total', 465)
                ->where('paymentMetrics.amounts.difference_total', 15)
                ->where('paymentMetrics.average_card_installments', 3)
                ->has('paymentMetrics.capture_methods', 2)
                ->where('paymentMetrics.capture_methods.0.method', 'credit_card')
                ->where('paymentMetrics.capture_methods.0.revenue', 315)
                ->where('paymentMetrics.capture_methods.1.method', 'pix')
                ->where('paymentMetrics.capture_methods.1.revenue', 150)
                ->has('paymentMetrics.installments_breakdown', 1)
                ->where('paymentMetrics.installments_breakdown.0.installments', 3)
                ->where('paymentMetrics.installments_breakdown.0.revenue', 315)
            );
    }
}
