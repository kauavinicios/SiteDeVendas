<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_creates_variant_with_trimmed_sku(): void
    {
        $product = Product::factory()->create();

        $this->post(route('admin.product-variants.store', $product), [
            'name' => 'Casal',
            'sku' => '  CAS-001  ',
            'price' => 129.9,
            'stock' => 3,
            'active' => true,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Casal',
            'sku' => 'CAS-001',
        ]);
    }

    public function test_empty_sku_is_stored_as_null(): void
    {
        $product = Product::factory()->create();

        $this->post(route('admin.product-variants.store', $product), [
            'name' => 'Solteiro',
            'sku' => '',
            'price' => 89.9,
            'stock' => 1,
            'active' => true,
        ]);

        $this->assertDatabaseHas('product_variants', ['name' => 'Solteiro', 'sku' => null]);
    }

    public function test_sku_must_be_unique(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create(['sku' => 'DUP-1']);

        $this->post(route('admin.product-variants.store', $product), [
            'name' => 'Queen',
            'sku' => 'DUP-1',
            'price' => 10,
            'stock' => 1,
            'active' => true,
        ])->assertSessionHasErrors('sku');
    }

    public function test_update_ignores_own_sku_in_unique_rule(): void
    {
        $variant = ProductVariant::factory()->create(['sku' => 'KEEP-1', 'price' => 10]);

        $this->put(route('admin.product-variants.update', [$variant->product, $variant]), [
            'name' => $variant->name,
            'sku' => 'KEEP-1',
            'price' => 99.9,
            'stock' => 7,
            'active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('99.90', $variant->fresh()->price);
        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_destroy_deletes_variant(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->delete(route('admin.product-variants.destroy', [$variant->product, $variant]))
            ->assertSessionHas('success');

        $this->assertModelMissing($variant);
    }

    public function test_variant_from_another_product_returns_404(): void
    {
        $variant = ProductVariant::factory()->create();
        $otherProduct = Product::factory()->create();

        $this->delete(route('admin.product-variants.destroy', [$otherProduct, $variant]))
            ->assertNotFound();

        $this->assertModelExists($variant);
    }
}
