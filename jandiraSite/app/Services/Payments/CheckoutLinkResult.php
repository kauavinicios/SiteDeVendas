<?php

namespace App\Services\Payments;

readonly class CheckoutLinkResult
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $url,
        public string $slug,
        public string $orderNsu,
        public int $amountCents,
        public string $driver,
        public string $authMode,
        public array $rawPayload = [],
    ) {}
}
