<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CampaignRequest;
use App\Mail\MarketingCampaignMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(): Response
    {
        $campaigns = Campaign::query()
            ->with('creator:id,name')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'subject' => $campaign->subject,
                'title' => $campaign->title,
                'body' => $campaign->body,
                'cta_label' => $campaign->cta_label,
                'cta_url' => $campaign->cta_url,
                'status' => $campaign->status,
                'recipients_count' => $campaign->recipients_count,
                'sent_at' => $campaign->sent_at?->format('d/m/Y H:i'),
                'created_at' => $campaign->created_at?->format('d/m/Y H:i'),
                'creator_name' => $campaign->creator?->name,
            ]);

        return Inertia::render('admin/campaigns/index', [
            'campaigns' => $campaigns,
            'stats' => [
                'eligible_recipients' => Campaign::eligibleRecipientsQuery()->count(),
                'total_opted_in' => User::query()
                    ->where('role', UserRole::Customer->value)
                    ->where('active', true)
                    ->where('marketing_opt_in', true)
                    ->count(),
                'sent_campaigns' => Campaign::query()->where('status', Campaign::STATUS_SENT)->count(),
                'draft_campaigns' => Campaign::query()->where('status', Campaign::STATUS_DRAFT)->count(),
            ],
        ]);
    }

    public function store(CampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Campaign::create([
            ...$validated,
            'cta_label' => filled($validated['cta_label'] ?? null) ? trim($validated['cta_label']) : null,
            'cta_url' => filled($validated['cta_url'] ?? null) ? trim($validated['cta_url']) : null,
            'status' => Campaign::STATUS_DRAFT,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Rascunho da campanha criado com sucesso.');
    }

    public function update(CampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->isSent()) {
            return back()->with('error', 'Campanhas já enviadas não podem ser editadas.');
        }

        $validated = $request->validated();

        $campaign->update([
            ...$validated,
            'cta_label' => filled($validated['cta_label'] ?? null) ? trim($validated['cta_label']) : null,
            'cta_url' => filled($validated['cta_url'] ?? null) ? trim($validated['cta_url']) : null,
        ]);

        return back()->with('success', 'Campanha atualizada com sucesso.');
    }

    public function preview(Request $request, Campaign $campaign): View
    {
        return view('emails.marketing-campaign', [
            'campaign' => $campaign,
            'recipientName' => $request->user()?->name ?? 'Cliente',
            'actionText' => $campaign->cta_label,
            'actionUrl' => $campaign->cta_url,
            'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', [
                'user' => $request->user()->id,
            ]),
        ]);
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        if ($campaign->isSent()) {
            return back()->with('error', 'Esta campanha já foi enviada anteriormente.');
        }

        $recipients = Campaign::eligibleRecipientsQuery()->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'Não há clientes elegíveis (ativos, com e-mail verificado e inscritos em novidades).');
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(new MarketingCampaignMail($campaign, $recipient));
        }

        $campaign->update([
            'status' => Campaign::STATUS_SENT,
            'recipients_count' => $recipients->count(),
            'sent_at' => now(),
        ]);

        return back()->with('success', "Campanha colocada na fila de envio para {$recipients->count()} cliente(s).");
    }
}
