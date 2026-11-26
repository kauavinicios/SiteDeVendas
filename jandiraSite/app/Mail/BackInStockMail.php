<?php

namespace App\Mail;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackInStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProductVariant $variant,
        public string $recipientName = 'cliente',
    ) {}

    public function envelope(): Envelope
    {
        $this->variant->loadMissing('product');
        $productName = $this->variant->product?->name ?? 'Produto';

        return new Envelope(
            subject: "Voltou ao estoque: {$productName} — Butique da Jandira",
        );
    }

    public function content(): Content
    {
        $this->variant->loadMissing('product');
        $product = $this->variant->product;

        return new Content(
            view: 'emails.back-in-stock',
            with: [
                'productName' => $product?->name ?? 'Produto',
                'variantName' => $this->variant->name,
                'priceFormatted' => 'R$ '.number_format((float) $this->variant->price, 2, ',', '.'),
                'recipientName' => $this->recipientName,
                'actionText' => 'Ver produto na loja',
                'actionUrl' => $product ? route('products.show', $product) : url('/produtos'),
            ],
        );
    }
}
