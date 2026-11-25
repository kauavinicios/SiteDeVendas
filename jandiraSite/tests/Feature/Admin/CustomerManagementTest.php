<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_with_view_customers_permission_can_list_and_filter_customers(): void
    {
        User::factory()->customer()->subscribedToMarketing()->create(['name' => 'Ana Inscrita']);
        User::factory()->customer()->create(['name' => 'Bruno Sem Optin']);

        foreach ([User::factory()->admin()->create(), User::factory()->manager()->create(), User::factory()->support()->create()] as $staff) {
            $this->actingAs($staff)
                ->get(route('admin.customers.index', ['novidades' => 'inscritos', 'busca' => 'Ana']))
                ->assertOk();
        }
    }

    public function test_only_admin_can_toggle_customer_status(): void
    {
        $customer = User::factory()->customer()->create(['active' => true]);
        $manager = User::factory()->manager()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($manager)
            ->patch(route('admin.customers.toggle-status', $customer))
            ->assertForbidden();

        $this->assertTrue($customer->fresh()->active);

        $this->actingAs($admin)
            ->patch(route('admin.customers.toggle-status', $customer))
            ->assertSessionHas('success');

        $this->assertFalse($customer->fresh()->active);
    }
}
