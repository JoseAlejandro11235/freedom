<?php

namespace App\Payments;

readonly class PaymentCheckout
{
    public function __construct(
        public string $provider,
        public string $reference,
        public string $redirectUrl,
    ) {}
}
