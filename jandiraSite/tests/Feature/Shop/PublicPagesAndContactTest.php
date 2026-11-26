<?php

namespace Tests\Feature\Shop;

use App\Mail\ContactMessageReceivedMail;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicPagesAndContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_and_contact_pages_render_successfully(): void
    {
        $this->get(route('pages.about'))
            ->assertOk()
            ->assertSee('Butique da Jandira')
            ->assertSee('Cama &amp; Conforto', false)
            ->assertSee('Perfumaria Selecionada');

        $this->get(route('contact.index'))
            ->assertOk()
            ->assertSee('Fale com a Butique da Jandira')
            ->assertSee('Enviar mensagem');
    }

    public function test_guest_and_authenticated_customer_can_submit_contact_message(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), [
            'name' => '  Ana Clara  ',
            'email' => '  ANA@EXEMPLO.COM ',
            'phone' => '(67) 99888-7766',
            'subject' => 'Dúvida sobre enxoval',
            'message' => 'Gostaria de saber os tamanhos disponíveis do cobre-leito.',
        ])
            ->assertRedirect(route('contact.index'))
            ->assertSessionHas('contact_success');

        $this->assertDatabaseHas('contact_messages', [
            'user_id' => null,
            'name' => 'Ana Clara',
            'email' => 'ana@exemplo.com',
            'phone' => '(67) 99888-7766',
            'subject' => 'Dúvida sobre enxoval',
        ]);

        Mail::assertQueued(ContactMessageReceivedMail::class, function (ContactMessageReceivedMail $mail): bool {
            return $mail->contactMessage->email === 'ana@exemplo.com'
                && $mail->hasReplyTo('ana@exemplo.com', 'Ana Clara');
        });

        $customer = User::factory()->customer()->create([
            'name' => 'Beatriz Souza',
            'email' => 'beatriz@exemplo.com',
        ]);

        $this->actingAs($customer)
            ->get(route('contact.index'))
            ->assertOk()
            ->assertSee('Beatriz Souza', false)
            ->assertSee('beatriz@exemplo.com', false);

        $this->actingAs($customer)
            ->post(route('contact.store'), [
                'name' => 'Beatriz Souza',
                'email' => 'beatriz@exemplo.com',
                'subject' => 'Perfume importado',
                'message' => 'Vocês possuem previsão de reposição desta fragrância?',
            ])
            ->assertRedirect(route('contact.index'))
            ->assertSessionHas('contact_success');

        $this->assertDatabaseHas('contact_messages', [
            'user_id' => $customer->id,
            'email' => 'beatriz@exemplo.com',
            'subject' => 'Perfume importado',
        ]);
    }

    public function test_contact_form_validates_required_and_invalid_fields_in_portuguese(): void
    {
        Mail::fake();

        $response = $this->from(route('contact.index'))->post(route('contact.store'), [
            'name' => '',
            'email' => 'email-invalido',
            'subject' => 'Oi',
            'message' => 'Curta',
        ]);

        $response->assertRedirect(route('contact.index'))
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingQueued();
    }

    public function test_authorized_staff_can_view_and_toggle_read_status_on_contact_messages(): void
    {
        $message = ContactMessage::create([
            'name' => 'Lucas Pereira',
            'email' => 'lucas@exemplo.com',
            'subject' => 'Entrega em Campo Grande',
            'message' => 'Qual o prazo médio para entrega na região central?',
        ]);

        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $support = User::factory()->support()->create();

        $this->actingAs($admin)->get(route('admin.messages.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.messages.index'))->assertOk();
        $this->actingAs($support)->get(route('admin.messages.index'))->assertOk();

        $this->actingAs($support)
            ->patch(route('admin.messages.toggle-read', $message))
            ->assertRedirect()
            ->assertSessionHas('success', 'Mensagem marcada como lida.');

        $this->assertNotNull($message->fresh()->read_at);

        $this->actingAs($support)
            ->patch(route('admin.messages.toggle-read', $message))
            ->assertRedirect()
            ->assertSessionHas('success', 'Mensagem marcada como não lida.');

        $this->assertNull($message->fresh()->read_at);
    }

    public function test_unauthorized_roles_cannot_access_admin_messages(): void
    {
        $message = ContactMessage::create([
            'name' => 'Carla Mendes',
            'email' => 'carla@exemplo.com',
            'subject' => 'Dúvida',
            'message' => 'Gostaria de mais informações sobre os produtos.',
        ]);

        $storekeeper = User::factory()->storekeeper()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($storekeeper)->get(route('admin.messages.index'))->assertForbidden();
        $this->actingAs($storekeeper)->patch(route('admin.messages.toggle-read', $message))->assertForbidden();

        $this->actingAs($customer)->get(route('admin.messages.index'))->assertForbidden();
        $this->actingAs($customer)->patch(route('admin.messages.toggle-read', $message))->assertForbidden();
    }
}
