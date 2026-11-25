<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_staff_users_can_visit_the_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_customers_cannot_visit_the_dashboard(): void
    {
        $this->actingAs(User::factory()->customer()->create());

        $this->get('/admin/dashboard')->assertForbidden();
    }
}
