<?php

namespace App\Models;

use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'driver',
        'auth_mode',
        'order_nsu',
        'slug',
        'transaction_nsu',
        'checkout_url',
        'receipt_url',
        'status',
        'capture_method',
        'installments',
        'amount_cents',
        'paid_amount_cents',
        'paid_at',
        'last_checked_at',
        'raw_create_payload',
        'raw_last_check_payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentTransactionStatus::class,
            'checkout_url' => 'encrypted',
            'receipt_url' => 'encrypted',
            'installments' => 'integer',
            'amount_cents' => 'integer',
            'paid_amount_cents' => 'integer',
            'paid_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'raw_create_payload' => 'encrypted:array',
            'raw_last_check_payload' => 'encrypted:array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentTransactionStatus::Paid || $this->paid_at !== null;
    }

    public function amountInReais(): float
    {
        return round($this->amount_cents / 100, 2);
    }

    public function paidAmountInReais(): ?float
    {
        if ($this->paid_amount_cents === null) {
            return null;
        }

        return round($this->paid_amount_cents / 100, 2);
    }

    public function captureMethodLabel(): ?string
    {
        if ($this->capture_method === null || $this->capture_method === '') {
            return null;
        }

        return match ($this->capture_method) {
            'pix' => 'Pix',
            'credit_card' => 'Cartão de crédito',
            'debit_card' => 'Cartão de débito',
            default => ucfirst(str_replace('_', ' ', $this->capture_method)),
        };
    }
}
