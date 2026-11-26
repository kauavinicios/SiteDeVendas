<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\LegalDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_registration_requires_legal_terms_acceptance(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'semtermos@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('terms_accepted');

        $this->assertDatabaseMissing('users', ['email' => 'semtermos@example.com']);
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
            'terms_accepted' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue($user->active);
        $this->assertFalse($user->marketing_opt_in);
        $this->assertNull($user->marketing_opt_in_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(LegalDocuments::TERMS_VERSION, $user->terms_version);
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame(LegalDocuments::PRIVACY_VERSION, $user->privacy_version);
    }

    public function test_registration_records_marketing_opt_in_timestamp_when_accepted(): void
    {
        $this->post('/register', [
            'name' => 'João Santos',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'marketing_opt_in' => true,
            'terms_accepted' => true,
        ])->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'joao@example.com')->firstOrFail();

        $this->assertTrue($user->marketing_opt_in);
        $this->assertNotNull($user->marketing_opt_in_at);
    }

    public function test_registration_sends_custom_verification_email_and_rejects_invalid_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Teste Inválido',
            'email' => 'email-invalido',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => true,
        ])->assertSessionHasErrors('email');

        $this->post('/register', [
            'name' => 'Ana Costa',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => true,
        ])->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'ana@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }
}
