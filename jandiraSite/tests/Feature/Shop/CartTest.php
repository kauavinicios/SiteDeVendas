<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function cartItem(ProductVariant $variant, int $quantity): array
    {
        return [
            'variant_id' => $variant->id,
            'product_id' => $variant->product->id,
            'product_name' => $variant->product->name,
            'product_slug' => $variant->product->slug,
            'variant_name' => $variant->name,
            'price' => (float) $variant->price,
            'quantity' => $quantity,
        ];
    }

    public function test_empty_cart_page_renders(): void
    {
        $this->get(route('cart.index'))->assertOk();
    }

    public function test_can_add_variant_to_cart(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 5, 'price' => 100]);

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $this->assertSame(2, session('cart')[$variant->id]['quantity']);
    }

    public function test_adding_more_than_stock_is_capped(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 3]);

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 5]);

        $this->assertSame(3, session('cart')[$variant->id]['quantity']);
    }

    public function test_out_of_stock_variant_is_not_added(): void
    {
        $variant = ProductVariant::factory()->outOfStock()->create();

        $this->from(route('products.show', $variant->product))
            ->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertSessionHas('error');

        $this->assertEmpty(session('cart', []));
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        $product = Product::factory()->inactive()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertNotFound();
    }

    public function test_update_quantity_returns_new_totals(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 4, 'price' => 50]);

        $this->withSession(['cart' => [$variant->id => $this->cartItem($variant, 1)]])
            ->patchJson(route('cart.update', $variant), ['quantity' => 10])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'item' => ['variant_id' => $variant->id, 'quantity' => 4, 'subtotal' => 200],
                'cart' => ['count' => 4, 'total' => 200],
            ]);
    }

    public function test_update_item_not_in_cart_returns_404(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->patchJson(route('cart.update', $variant), ['quantity' => 1])
            ->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_can_remove_item(): void
    {
        $keep = ProductVariant::factory()->create(['price' => 10]);
        $remove = ProductVariant::factory()->create(['price' => 20]);

        $this->withSession(['cart' => [
            $keep->id => $this->cartItem($keep, 2),
            $remove->id => $this->cartItem($remove, 1),
        ]])
            ->deleteJson(route('cart.destroy', $remove))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'removed_variant_id' => $remove->id,
                'cart' => ['count' => 2, 'total' => 20],
            ]);
    }

    public function test_can_clear_cart(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->withSession(['cart' => [$variant->id => $this->cartItem($variant, 1)]])
            ->deleteJson(route('cart.clear'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(session('cart'));
    }

    public function test_cart_page_syncs_price_and_stock_with_database(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 5, 'price' => 100]);
        $soldOut = ProductVariant::factory()->create(['stock' => 2]);

        $session = ['cart' => [
            $variant->id => $this->cartItem($variant, 4),
            $soldOut->id => $this->cartItem($soldOut, 1),
        ]];

        // Depois de adicionado: preço subiu, estoque caiu e outra variação esgotou.
        $variant->update(['price' => 120, 'stock' => 2]);
        $soldOut->update(['stock' => 0]);

        $this->withSession($session)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertViewHas('total', 240.0);

        $cart = session('cart');
        $this->assertSame(2, $cart[$variant->id]['quantity']);
        $this->assertSame(120.0, $cart[$variant->id]['price']);
        $this->assertArrayNotHasKey($soldOut->id, $cart);
    }
}
