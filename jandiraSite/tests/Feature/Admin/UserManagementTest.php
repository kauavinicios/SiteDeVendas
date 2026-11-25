<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_admin_can_list_create_update_and_toggle_staff_users(): void
    {
        $this->get(route('admin.users.index'))->assertOk();

        $this->post(route('admin.users.store'), [
            'name' => 'Lojista Novo',
            'email' => 'lojista.novo@example.com',
            'phone' => '67999991111',
            'role' => UserRole::Storekeeper->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'active' => true,
        ])->assertRedirect(route('admin.users.index'));

        $staff = User::where('email', 'lojista.novo@example.com')->firstOrFail();
        $this->assertSame(UserRole::Storekeeper, $staff->role);

        $this->put(route('admin.users.update', $staff), [
            'name' => 'Gerente Promovido',
            'email' => 'lojista.novo@example.com',
            'phone' => '',
            'role' => UserRole::Manager->value,
            'password' => '',
            'password_confirmation' => '',
            'active' => true,
        ])->assertRedirect(route('admin.users.index'));

        $staff->refresh();
        $this->assertSame('Gerente Promovido', $staff->name);
        $this->assertSame(UserRole::Manager, $staff->role);
        $this->assertNull($staff->phone);

        $this->patch(route('admin.users.toggle-status', $staff))
            ->assertSessionHas('success');
        $this->assertFalse($staff->fresh()->active);

        $this->patch(route('admin.users.toggle-status', $staff))
            ->assertSessionHas('success');
        $this->assertTrue($staff->fresh()->active);
    }

    public function test_admin_cannot_deactivate_self_or_downgrade_own_role(): void
    {
        $this->patch(route('admin.users.toggle-status', $this->admin))
            ->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->active);

        $this->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'phone' => '',
            'role' => UserRole::Support->value,
            'password' => '',
            'password_confirmation' => '',
            'active' => true,
        ])->assertSessionHas('error');

        $this->assertSame(UserRole::Admin, $this->admin->fresh()->role);
    }

    public function test_admin_can_deactivate_another_admin_while_remaining_active(): void
    {
        $otherAdmin = User::factory()->admin()->create(['active' => true]);

        $this->patch(route('admin.users.toggle-status', $otherAdmin))
            ->assertSessionHas('success');

        $this->assertFalse($otherAdmin->fresh()->active);
        $this->assertTrue($this->admin->fresh()->active);
    }
}
