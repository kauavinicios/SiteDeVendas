<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_register_as_customers_even_if_role_is_sent(): void
    {
        $response = $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '67999990000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue($user->active);
        $this->assertFalse($user->marketing_opt_in);
        $this->assertNull($user->marketing_opt_in_at);
    }

    public function test_registration_records_marketing_opt_in_timestamp_when_accepted(): void
    {
        $this->post('/register', [
            'name' => 'João Santos',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'marketing_opt_in' => true,
        ])->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'joao@example.com')->firstOrFail();

        $this->assertTrue($user->marketing_opt_in);
        $this->assertNotNull($user->marketing_opt_in_at);
    }
}
