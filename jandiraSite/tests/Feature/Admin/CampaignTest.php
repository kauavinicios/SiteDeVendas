<?php

namespace Tests\Feature\Admin;

use App\Mail\MarketingCampaignMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_manager_can_create_edit_and_preview_campaigns(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('admin.campaigns.index'))
            ->assertOk();

        $this->actingAs($manager)
            ->post(route('admin.campaigns.store'), [
                'subject' => 'Novidades de Verão',
                'title' => 'Chegaram novos perfumes!',
                'body' => 'Confira nossa nova linha na vitrine.',
                'cta_label' => 'Ver produtos',
                'cta_url' => 'https://example.com/produtos',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $campaign = Campaign::firstOrFail();
        $this->assertTrue($campaign->isDraft());

        $this->actingAs($manager)
            ->put(route('admin.campaigns.update', $campaign), [
                'subject' => 'Novidades de Verão Atualizadas',
                'title' => 'Chegaram novos perfumes e lençóis!',
                'body' => 'Confira nossa vitrine atualizada.',
                'cta_label' => 'Acessar loja',
                'cta_url' => 'https://example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Novidades de Verão Atualizadas', $campaign->fresh()->subject);

        $this->actingAs($manager)
            ->get(route('admin.campaigns.preview', $campaign))
            ->assertOk()
            ->assertSee('Butique da Jandira')
            ->assertSee('Novidades da Butique')
            ->assertSee('Cancelar inscrição em 1 clique');
    }

    public function test_unauthorized_roles_cannot_access_campaigns_module(): void
    {
        $storekeeper = User::factory()->storekeeper()->create();
        $support = User::factory()->support()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($storekeeper)->get(route('admin.campaigns.index'))->assertForbidden();
        $this->actingAs($support)->get(route('admin.campaigns.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.campaigns.index'))->assertForbidden();
    }

    public function test_sending_campaign_queues_only_to_eligible_lgpd_customers(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create([
            'marketing_opt_in' => true,
        ]);

        $eligible = User::factory()->customer()->subscribedToMarketing()->create();
        $unverified = User::factory()->customer()->unverified()->subscribedToMarketing()->create();
        $inactive = User::factory()->customer()->inactive()->subscribedToMarketing()->create();
        $optedOut = User::factory()->customer()->create(['marketing_opt_in' => false]);

        $campaign = Campaign::create([
            'subject' => 'Semana Perfumaria',
            'title' => 'Fragrâncias em destaque',
            'body' => 'Ofertas especiais para você.',
            'status' => Campaign::STATUS_DRAFT,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.campaigns.send', $campaign))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(MarketingCampaignMail::class, 1);
        Mail::assertQueued(MarketingCampaignMail::class, fn (MarketingCampaignMail $mail) => $mail->hasTo($eligible->email));
        Mail::assertNotQueued(MarketingCampaignMail::class, fn (MarketingCampaignMail $mail) => $mail->hasTo($unverified->email));
        Mail::assertNotQueued(MarketingCampaignMail::class, fn (MarketingCampaignMail $mail) => $mail->hasTo($inactive->email));
        Mail::assertNotQueued(MarketingCampaignMail::class, fn (MarketingCampaignMail $mail) => $mail->hasTo($optedOut->email));
        Mail::assertNotQueued(MarketingCampaignMail::class, fn (MarketingCampaignMail $mail) => $mail->hasTo($admin->email));

        $campaign->refresh();
        $this->assertTrue($campaign->isSent());
        $this->assertSame(1, $campaign->recipients_count);
        $this->assertNotNull($campaign->sent_at);

        // Campanha já enviada não pode ser editada nem reenviada
        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $campaign), [
                'subject' => 'Alteração bloqueada',
                'title' => 'Título',
                'body' => 'Corpo',
            ])
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('admin.campaigns.send', $campaign))
            ->assertSessionHas('error');
    }

    public function test_signed_unsubscribe_link_opts_out_customer_and_unsigned_is_rejected(): void
    {
        $customer = User::factory()->customer()->subscribedToMarketing()->create();

        $this->get('/novidades/cancelar/'.$customer->id)
            ->assertForbidden();

        $signedUrl = URL::signedRoute('newsletter.unsubscribe', ['user' => $customer->id]);

        $this->get($signedUrl)
            ->assertOk()
            ->assertSee('Você não receberá mais e-mails de novidades')
            ->assertSee($customer->email);

        $customer->refresh();
        $this->assertFalse($customer->marketing_opt_in);
        $this->assertNotNull($customer->marketing_opt_out_at);
    }
}
