<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_index_renders(): void
    {
        Category::factory()->count(2)->create();

        $this->get(route('admin.categories.index'))->assertOk();
    }

    public function test_store_generates_unique_slug_from_name(): void
    {
        Category::factory()->create(['name' => 'Cobertas', 'slug' => 'cobertas']);

        $this->post(route('admin.categories.store'), ['name' => 'Cobertas'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['slug' => 'cobertas-2']);
    }

    public function test_store_normalizes_given_slug(): void
    {
        $this->post(route('admin.categories.store'), ['name' => 'Jogos', 'slug' => 'Jogos de Cama']);

        $this->assertDatabaseHas('categories', ['name' => 'Jogos', 'slug' => 'jogos-de-cama']);
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }

    public function test_cannot_delete_category_with_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();

        $this->delete(route('admin.categories.destroy', $parent))
            ->assertSessionHas('error');

        $this->assertModelExists($parent);
    }

    public function test_can_delete_empty_category(): void
    {
        $category = Category::factory()->create();

        $this->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('success');

        $this->assertModelMissing($category);
    }
}
