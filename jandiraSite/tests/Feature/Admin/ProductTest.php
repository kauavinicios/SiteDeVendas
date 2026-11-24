<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jogo de Cama Percal',
            'category_id' => Category::factory()->create()->id,
            'description' => 'Algodão 200 fios',
            'active' => true,
            'featured' => false,
        ], $overrides);
    }

    public function test_guests_cannot_access_admin_products(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect('/login');
        $this->post(route('admin.products.store'), $this->payload())->assertRedirect('/login');
    }

    public function test_admin_pages_render(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.products.edit', $product))->assertOk();
    }

    public function test_store_creates_product_with_unique_slug(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.products.store'), $this->payload())
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->post(route('admin.products.store'), $this->payload());

        $this->assertDatabaseHas('products', ['slug' => 'jogo-de-cama-percal']);
        $this->assertDatabaseHas('products', ['slug' => 'jogo-de-cama-percal-2']);
    }

    public function test_update_keeps_own_slug_when_name_is_unchanged(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create(['name' => 'Fronha Lisa', 'slug' => 'fronha-lisa']);

        $this->put(route('admin.products.update', $product), $this->payload([
            'name' => 'Fronha Lisa',
            'description' => 'Nova descrição',
        ]))->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('fronha-lisa', $product->slug);
        $this->assertSame('Nova descrição', $product->description);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.products.store'), [])
            ->assertSessionHasErrors(['name', 'category_id', 'active', 'featured']);

        $this->assertDatabaseCount('products', 0);
    }
}
