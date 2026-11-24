<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_active_products_only(): void
    {
        Product::factory()->create(['name' => 'Lençol Ativo']);
        Product::factory()->inactive()->create(['name' => 'Lençol Inativo']);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Lençol Ativo')
            ->assertDontSee('Lençol Inativo');
    }

    public function test_search_is_case_insensitive(): void
    {
        Product::factory()->create(['name' => 'Perfume Malbec']);
        Product::factory()->create(['name' => 'Fronha Floral']);

        $this->get(route('products.index', ['busca' => 'malbec']))
            ->assertOk()
            ->assertSee('Perfume Malbec')
            ->assertDontSee('Fronha Floral');
    }

    public function test_filter_by_parent_category_includes_subcategory_products(): void
    {
        $casa = Category::factory()->create(['name' => 'Casa', 'slug' => 'casa']);
        $lencois = Category::factory()->childOf($casa)->create(['name' => 'Lençóis', 'slug' => 'lencois']);
        $perfumaria = Category::factory()->create(['name' => 'Perfumaria', 'slug' => 'perfumaria']);

        Product::factory()->for($lencois)->create(['name' => 'Lençol Percal']);
        Product::factory()->for($perfumaria)->create(['name' => 'Perfume Egeo']);

        $this->get(route('products.index', ['categorias' => ['casa']]))
            ->assertOk()
            ->assertSee('Lençol Percal')
            ->assertDontSee('Perfume Egeo');
    }

    public function test_product_page_is_resolved_by_slug(): void
    {
        $product = Product::factory()
            ->has(ProductVariant::factory()->state(['name' => 'Queen']), 'variants')
            ->create(['name' => 'Cobre-leito Rosé', 'slug' => 'cobre-leito-rose']);

        $this->get('/produtos/cobre-leito-rose')
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Queen');
    }

    public function test_inactive_product_page_returns_404(): void
    {
        Product::factory()->inactive()->create(['slug' => 'produto-inativo']);

        $this->get('/produtos/produto-inativo')->assertNotFound();
    }
}
