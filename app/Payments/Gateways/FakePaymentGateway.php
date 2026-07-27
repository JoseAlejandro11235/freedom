<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function createCheckout(Order $order): PaymentCheckout
    {
        $reference = 'fake_'.Str::lower(Str::random(16));

        return new PaymentCheckout(
            provider: $this->name(),
            reference: $reference,
            redirectUrl: route('checkout.fake.pay', $order),
        );
    }

    public function resolveOrderNumber(Request $request): ?string
    {
        return $request->string('order')->toString() ?: null;
    }

    public function isPaymentApproved(Request $request, Order $order): bool
    {
        return $request->boolean('approved', true);
    }
}
