<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_place_order_from_cart_and_stock_remains_pending_until_confirmed(): void
    {
        config(['services.whatsapp.number' => '5567999999999']);

        $customer = User::factory()->customer()->create([
            'name' => 'Cliente Teste',
            'email' => 'comprador@example.com',
            'phone' => '67999991111',
        ]);

        $product = Product::factory()->create(['active' => true, 'name' => 'Perfume Floral']);
        $variant = ProductVariant::factory()->for($product)->create([
            'name' => '100 ml',
            'price' => 150.00,
            'cost_price' => 80.00,
            'stock' => 5,
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
                'customer_name' => 'Cliente Teste',
                'customer_phone' => '67999991111',
                'customer_email' => 'COMPRADOR@example.com',
                'notes' => 'Entregar no período da tarde',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.total', 300);

        $order = Order::with('items')->firstOrFail();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame('300.00', $order->total);
        $this->assertSame('160.00', $order->total_cost);
        $this->assertSame(2, $order->items_count);
        $this->assertNull($order->stock_deducted_at);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertEmpty(session('cart', []));
        $this->assertStringContainsString($order->code, $response->json('whatsapp_url'));
    }

    public function test_admin_order_confirmation_deducts_stock_and_cancellation_restores_it(): void
    {
        $storekeeper = User::factory()->storekeeper()->create();
        $product = Product::factory()->create(['active' => true]);
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 100.00,
            'stock' => 4,
        ]);

        $order = Order::create([
            'code' => 'BJ-20261126-TEST1',
            'customer_name' => 'Ana Paula',
            'customer_phone' => '67999990000',
            'status' => OrderStatus::Pending,
            'total' => 300.00,
            'items_count' => 3,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'unit_price' => 100.00,
            'quantity' => 3,
            'subtotal' => 300.00,
        ]);

        $this->actingAs($storekeeper)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => OrderStatus::Confirmed->value,
                'payment_status' => 'paid',
                'payment_method' => 'Pix',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertNotNull($order->fresh()->stock_deducted_at);

        // Cancelamento devolve o estoque automaticamente
        $this->actingAs($storekeeper)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => OrderStatus::Cancelled->value,
                'payment_status' => 'refunded',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertNull($order->fresh()->stock_deducted_at);

        // Bloqueia confirmação caso estoque fique menor que o pedido
        $variant->update(['stock' => 2]);

        $this->actingAs($storekeeper)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => OrderStatus::Confirmed->value,
            ])
            ->assertSessionHas('error');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_orders_rbac_and_cost_price_visibility(): void
    {
        $manager = User::factory()->manager()->create();
        $storekeeper = User::factory()->storekeeper()->create();
        $support = User::factory()->support()->create();
        $customer = User::factory()->customer()->create();

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create([
            'price' => 100.00,
            'cost_price' => 45.00,
        ]);

        // Lojista não sobrescreve cost_price ao atualizar variação
        $this->actingAs($storekeeper)
            ->put(route('admin.product-variants.update', ['product' => $product->id, 'variant' => $variant->id]), [
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => 120.00,
                'cost_price' => 1.00,
                'stock' => 10,
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertSame('45.00', $variant->fresh()->cost_price);
        $this->assertSame('120.00', $variant->fresh()->price);

        // Gerente consegue atualizar cost_price
        $this->actingAs($manager)
            ->put(route('admin.product-variants.update', ['product' => $product->id, 'variant' => $variant->id]), [
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => 120.00,
                'cost_price' => 60.00,
                'stock' => 10,
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertSame('60.00', $variant->fresh()->cost_price);

        $order = Order::create([
            'code' => 'BJ-20261126-RBAC1',
            'customer_name' => 'Cliente',
            'customer_phone' => '67999990000',
            'status' => OrderStatus::Pending,
            'total' => 120.00,
            'total_cost' => 60.00,
            'items_count' => 1,
        ]);

        $this->actingAs($support)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($support)
            ->patch(route('admin.orders.update-status', $order), ['status' => OrderStatus::Confirmed->value])
            ->assertForbidden();

        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_my_account_displays_orders_and_deleting_account_with_orders_anonymizes_customer(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'comprador.lgpd@example.com',
        ]);

        $order = Order::create([
            'code' => 'BJ-20261126-LGPD1',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '67999993333',
            'status' => OrderStatus::Delivered,
            'total' => 250.00,
            'items_count' => 2,
            'confirmed_at' => now(),
        ]);

        $order->items()->create([
            'product_name' => 'Cobre-leito Queen',
            'variant_name' => 'Queen',
            'unit_price' => 125.00,
            'quantity' => 2,
            'subtotal' => 250.00,
        ]);

        $this->actingAs($customer)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('BJ-20261126-LGPD1')
            ->assertSee('250,00');

        // Com histórico de pedido, exclusão deve anonimizar com SoftDeletes e preservar o pedido
        $this->actingAs($customer)
            ->delete(route('account.destroy'), ['password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $trashed = User::withTrashed()->findOrFail($customer->id);
        $this->assertTrue($trashed->trashed());
        $this->assertSame('Cliente excluído', $trashed->name);
        $this->assertNull(User::where('email', 'comprador.lgpd@example.com')->first());
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'code' => 'BJ-20261126-LGPD1',
            'user_id' => $customer->id,
        ]);
    }
}
