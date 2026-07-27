<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\PaymentCheckout;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function name(): string;

    public function createCheckout(Order $order): PaymentCheckout;

    /**
     * Resolve the local order number (or null) from a provider webhook/callback payload.
     */
    public function resolveOrderNumber(Request $request): ?string;

    public function isPaymentApproved(Request $request, Order $order): bool;
}
