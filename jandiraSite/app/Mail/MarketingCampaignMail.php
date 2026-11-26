<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class MarketingCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketing-campaign',
            with: [
                'campaign' => $this->campaign,
                'recipientName' => $this->recipient->name,
                'actionText' => $this->campaign->cta_label,
                'actionUrl' => $this->campaign->cta_url,
                'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', [
                    'user' => $this->recipient->id,
                ]),
            ],
        );
    }
}
