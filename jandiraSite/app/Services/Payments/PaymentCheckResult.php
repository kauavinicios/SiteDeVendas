<?php

namespace App\Services\Payments;

readonly class PaymentCheckResult
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public bool $paid,
        public ?int $amountCents,
        public ?int $paidAmountCents,
        public ?string $orderNsu,
        public ?string $transactionNsu,
        public ?string $captureMethod,
        public ?int $installments,
        public ?string $receiptUrl,
        public array $rawPayload = [],
    ) {}
}
