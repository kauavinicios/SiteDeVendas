<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_only_active_featured_products(): void
    {
        $featured = Product::factory()->featured()->has(ProductVariant::factory(), 'variants')->create(['name' => 'Jogo de Cama Destaque']);
        Product::factory()->create(['name' => 'Produto Comum']);
        Product::factory()->featured()->inactive()->create(['name' => 'Destaque Inativo']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($featured->name)
            ->assertDontSee('Produto Comum')
            ->assertDontSee('Destaque Inativo');
    }
}
