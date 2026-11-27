<?php

namespace Tests\Feature\Security;

use App\Enums\OrderStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptionAndMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_fields_are_encrypted_at_rest_and_transparently_decrypted_by_eloquent(): void
    {
        $order = Order::create([
            'code' => 'BJ-20261127-SEC01',
            'customer_name' => 'Cliente Sigilo',
            'customer_phone' => '67999998888',
            'status' => OrderStatus::Confirmed,
            'channel' => 'infinitepay',
            'payment_status' => 'paid',
            'total' => 250.00,
            'items_count' => 1,
            'notes' => 'Entregar apenas para Ana na portaria 202',
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'infinitepay',
            'driver' => 'live',
            'auth_mode' => 'handle',
            'order_nsu' => $order->code,
            'slug' => 'slug-sec-01',
            'transaction_nsu' => 'tx-sec-01',
            'checkout_url' => 'https://pay.infinitepay.io/secret-checkout-link',
            'receipt_url' => 'https://pay.infinitepay.io/receipt/secret-receipt-999',
            'status' => PaymentTransactionStatus::Paid,
            'capture_method' => 'pix',
            'installments' => 1,
            'amount_cents' => 25000,
            'paid_amount_cents' => 25000,
            'paid_at' => now(),
            'raw_create_payload' => ['secret_token' => 'create-xyz-123', 'amount' => 25000],
            'raw_last_check_payload' => ['paid' => true, 'payer_doc' => 'sigilo-check-999'],
        ]);

        $event = PaymentEvent::create([
            'payment_transaction_id' => $transaction->id,
            'order_id' => $order->id,
            'provider' => 'infinitepay',
            'source' => 'webhook',
            'event_type' => 'payment.paid',
            'payload' => ['webhook_detail' => 'evento-privado-infinitepay'],
            'processed_at' => now(),
        ]);

        $contactMessage = ContactMessage::create([
            'name' => 'Mariana Silveira',
            'email' => 'mariana@exemplo.com',
            'phone' => '67999997777',
            'subject' => 'Dúvida sobre endereço',
            'message' => 'Conteúdo privado da mensagem enviada pelo site.',
        ]);

        $rawOrder = DB::table('orders')->where('id', $order->id)->first();
        $rawTx = DB::table('payment_transactions')->where('id', $transaction->id)->first();
        $rawEvent = DB::table('payment_events')->where('id', $event->id)->first();
        $rawContact = DB::table('contact_messages')->where('id', $contactMessage->id)->first();

        $this->assertStringNotContainsString('portaria 202', (string) $rawOrder->notes);
        $this->assertSame('Entregar apenas para Ana na portaria 202', Crypt::decryptString((string) $rawOrder->notes));

        $this->assertStringNotContainsString('secret-checkout-link', (string) $rawTx->checkout_url);
        $this->assertSame('https://pay.infinitepay.io/secret-checkout-link', Crypt::decryptString((string) $rawTx->checkout_url));

        $this->assertStringNotContainsString('secret-receipt-999', (string) $rawTx->receipt_url);
        $this->assertSame('https://pay.infinitepay.io/receipt/secret-receipt-999', Crypt::decryptString((string) $rawTx->receipt_url));

        $this->assertStringNotContainsString('create-xyz-123', (string) $rawTx->raw_create_payload);
        $this->assertSame(
            ['secret_token' => 'create-xyz-123', 'amount' => 25000],
            json_decode(Crypt::decryptString((string) $rawTx->raw_create_payload), true),
        );

        $this->assertStringNotContainsString('sigilo-check-999', (string) $rawTx->raw_last_check_payload);
        $this->assertSame(
            ['paid' => true, 'payer_doc' => 'sigilo-check-999'],
            json_decode(Crypt::decryptString((string) $rawTx->raw_last_check_payload), true),
        );

        $this->assertStringNotContainsString('evento-privado-infinitepay', (string) $rawEvent->payload);
        $this->assertSame(
            ['webhook_detail' => 'evento-privado-infinitepay'],
            json_decode(Crypt::decryptString((string) $rawEvent->payload), true),
        );

        $this->assertStringNotContainsString('Conteúdo privado', (string) $rawContact->message);
        $this->assertSame('Conteúdo privado da mensagem enviada pelo site.', Crypt::decryptString((string) $rawContact->message));

        // Eloquent devolve os dados descriptografados automaticamente
        $this->assertSame('Entregar apenas para Ana na portaria 202', $order->fresh()->notes);
        $this->assertSame('https://pay.infinitepay.io/receipt/secret-receipt-999', $transaction->fresh()->receipt_url);
        $this->assertSame(['webhook_detail' => 'evento-privado-infinitepay'], $event->fresh()->payload);
        $this->assertSame('Conteúdo privado da mensagem enviada pelo site.', $contactMessage->fresh()->message);
    }

    public function test_user_model_rejects_mass_assignment_of_role_and_active(): void
    {
        $this->assertThrows(function (): void {
            (new User)->fill(['role' => UserRole::Admin->value]);
        }, MassAssignmentException::class);

        $this->assertThrows(function (): void {
            (new User)->fill(['active' => false]);
        }, MassAssignmentException::class);

        $response = $this->post(route('register'), [
            'name' => 'Atacante',
            'email' => 'atacante@exemplo.com',
            'password' => 'SenhaForte123',
            'password_confirmation' => 'SenhaForte123',
            'terms_accepted' => true,
            'role' => 'admin',
            'active' => false,
        ]);

        $response->assertRedirect();

        $created = User::where('email', 'atacante@exemplo.com')->firstOrFail();
        $this->assertSame(UserRole::Customer, $created->role);
        $this->assertTrue($created->active);
    }

    public function test_admin_can_still_create_staff_user_with_role_active_and_verified_email(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Nova Gerente',
                'email' => 'gerente.nova@exemplo.com',
                'phone' => '67999990000',
                'role' => UserRole::Manager->value,
                'active' => true,
                'password' => 'SenhaSegura123',
                'password_confirmation' => 'SenhaSegura123',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $staff = User::where('email', 'gerente.nova@exemplo.com')->firstOrFail();
        $this->assertSame(UserRole::Manager, $staff->role);
        $this->assertTrue($staff->active);
        $this->assertNotNull($staff->email_verified_at);
    }
}
