<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        Notification::fake();

        $user = User::factory()->admin()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_customers_cannot_access_staff_settings_pages(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/settings/profile')->assertForbidden();
        $this->actingAs($customer)->get('/settings/password')->assertForbidden();
        $this->actingAs($customer)->get('/settings/appearance')->assertForbidden();
    }

    public function test_account_deletion_endpoint_is_not_available(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->delete('/settings/profile', [
                'password' => 'password',
            ])
            ->assertMethodNotAllowed();

        $this->assertNotNull($user->fresh());
    }
}
