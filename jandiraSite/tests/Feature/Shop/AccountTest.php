<?php

namespace Tests\Feature\Shop;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_account_page(): void
    {
        $this->get(route('account.index'))->assertRedirect(route('login'));
    }

    public function test_customer_can_view_account_and_toggle_marketing_opt_in(): void
    {
        $customer = User::factory()->customer()->create(['marketing_opt_in' => false]);

        $this->actingAs($customer)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee($customer->name);

        $this->actingAs($customer)
            ->patch(route('account.profile.update'), [
                'name' => 'Cliente Atualizado',
                'email' => $customer->email,
                'phone' => '67999992222',
                'marketing_opt_in' => true,
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('success');

        $customer->refresh();
        $this->assertSame('Cliente Atualizado', $customer->name);
        $this->assertSame('67999992222', $customer->phone);
        $this->assertTrue($customer->marketing_opt_in);
        $this->assertNotNull($customer->marketing_opt_in_at);
        $this->assertNull($customer->marketing_opt_out_at);

        $this->actingAs($customer)
            ->patch(route('account.profile.update'), [
                'name' => 'Cliente Atualizado',
                'email' => $customer->email,
                'phone' => '67999992222',
            ])
            ->assertRedirect(route('account.index'));

        $customer->refresh();
        $this->assertFalse($customer->marketing_opt_in);
        $this->assertNotNull($customer->marketing_opt_out_at);
    }

    public function test_customer_can_update_password(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'nova-senha-123',
                'password_confirmation' => 'nova-senha-123',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('nova-senha-123', $customer->fresh()->password));
    }

    public function test_customer_can_delete_own_account_with_valid_password(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'exclusao@example.com',
        ]);

        $this->actingAs($customer)
            ->delete(route('account.destroy'), [
                'password' => 'password',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertGuest();
        $this->assertNull(User::withTrashed()->find($customer->id));
    }

    public function test_customer_cannot_delete_account_with_wrong_password(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->from(route('account.index'))
            ->delete(route('account.destroy'), [
                'password' => 'senha-errada',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHasErrorsIn('accountDeletion', 'password');

        $this->assertNotNull($customer->fresh());
    }

    public function test_staff_cannot_delete_own_account_on_my_account(): void
    {
        $staff = User::factory()->support()->create();

        $this->actingAs($staff)
            ->delete(route('account.destroy'), [
                'password' => 'password',
            ])
            ->assertForbidden();

        $this->assertNotNull($staff->fresh());
    }

    public function test_customer_anonymization_clears_pii_and_releases_email(): void
    {
        $customer = User::factory()->customer()->subscribedToMarketing()->create([
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
            'phone' => '67999998888',
        ]);

        $customer->anonymizeAndSoftDelete();

        $trashed = User::withTrashed()->findOrFail($customer->id);

        $this->assertTrue($trashed->trashed());
        $this->assertSame('Cliente excluído', $trashed->name);
        $this->assertNotSame('maria.souza@example.com', $trashed->email);
        $this->assertStringEndsWith('@anonimizado.local', $trashed->email);
        $this->assertNull($trashed->phone);
        $this->assertFalse($trashed->active);
        $this->assertFalse($trashed->marketing_opt_in);
        $this->assertNull(User::where('email', 'maria.souza@example.com')->first());
    }
}
