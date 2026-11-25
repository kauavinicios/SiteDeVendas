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
}
