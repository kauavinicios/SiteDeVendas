<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_admin_routes(): void
    {
        $this->actingAs(User::factory()->customer()->create());

        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('admin.products.index'))->assertForbidden();
        $this->get(route('admin.categories.index'))->assertForbidden();
        $this->get(route('admin.stock.index'))->assertForbidden();
        $this->get(route('admin.customers.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_support_has_read_only_access_and_cannot_manage_resources(): void
    {
        $variant = ProductVariant::factory()->create();
        $this->actingAs(User::factory()->support()->create());

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.stock.index'))->assertOk();
        $this->get(route('admin.customers.index'))->assertOk();

        $this->get(route('admin.products.create'))->assertForbidden();
        $this->patch(route('admin.stock.update', $variant), ['stock' => 10])->assertForbidden();
        $this->post(route('admin.categories.store'), ['name' => 'Nova'])->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_storekeeper_manages_products_and_stock_but_not_categories_or_users(): void
    {
        $category = Category::factory()->create();
        $variant = ProductVariant::factory()->create(['stock' => 2]);
        $this->actingAs(User::factory()->storekeeper()->create());

        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.categories.index'))->assertOk();

        $this->patch(route('admin.stock.update', $variant), ['stock' => 8])
            ->assertSessionHas('success');
        $this->assertSame(8, $variant->fresh()->stock);

        $this->post(route('admin.categories.store'), ['name' => 'Bloqueada'])->assertForbidden();
        $this->delete(route('admin.categories.destroy', $category))->assertForbidden();
        $this->get(route('admin.customers.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_manager_manages_catalog_and_views_customers_but_not_staff_users(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $this->actingAs(User::factory()->manager()->create());

        $this->get(route('admin.customers.index'))->assertOk();

        $this->post(route('admin.categories.store'), ['name' => 'Banho'])
            ->assertRedirect(route('admin.categories.index'));
        $this->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('success');

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->patch(route('admin.customers.toggle-status', $customer))->assertForbidden();
        $this->assertTrue(Product::query()->count() === 0);
    }

    public function test_inactive_authenticated_user_is_logged_out_by_active_middleware(): void
    {
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        $this->actingAs($inactiveAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
