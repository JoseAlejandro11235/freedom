<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentCheckout;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'mercadopago';
    }

    public function createCheckout(Order $order): PaymentCheckout
    {
        $accessToken = (string) config('services.mercadopago.access_token');

        if ($accessToken === '') {
            throw new RuntimeException('Mercado Pago no está configurado. Define MERCADOPAGO_ACCESS_TOKEN.');
        }

        $order->loadMissing('items');

        $payload = [
            'external_reference' => $order->number,
            'notification_url' => route('payments.webhook', ['provider' => $this->name()]),
            'back_urls' => [
                'success' => route('checkout.success', $order),
                'pending' => route('checkout.pending', $order),
                'failure' => route('checkout.failure', $order),
            ],
            'auto_return' => 'approved',
            'statement_descriptor' => 'FREEDOM',
            'items' => $order->items->map(fn ($item) => [
                'id' => (string) ($item->product_id ?? $item->id),
                'title' => $item->product_name,
                'quantity' => $item->quantity,
                'currency_id' => $order->currency,
                'unit_price' => (float) $item->unit_price,
            ])->values()->all(),
            'payer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => [
                    'number' => (string) ($order->customer_phone ?? ''),
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post('https://api.mercadopago.com/checkout/preferences', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            throw new RuntimeException('No se pudo crear el pago en Mercado Pago.', previous: $exception);
        }

        $preferenceId = (string) ($response['id'] ?? '');
        $sandbox = (bool) config('services.mercadopago.sandbox', true);
        $redirectUrl = $sandbox
            ? (string) ($response['sandbox_init_point'] ?? $response['init_point'] ?? '')
            : (string) ($response['init_point'] ?? '');

        if ($preferenceId === '' || $redirectUrl === '') {
            throw new RuntimeException('Mercado Pago no devolvió una URL de pago válida.');
        }

        return new PaymentCheckout(
            provider: $this->name(),
            reference: $preferenceId,
            redirectUrl: $redirectUrl,
        );
    }

    public function resolveOrderNumber(Request $request): ?string
    {
        if ($request->filled('external_reference')) {
            return $request->string('external_reference')->toString();
        }

        $paymentId = $request->input('data.id') ?? $request->input('id');

        if (! filled($paymentId) || $request->input('type') === 'test') {
            return null;
        }

        $accessToken = (string) config('services.mercadopago.access_token');

        if ($accessToken === '') {
            return null;
        }

        $payment = Http::withToken($accessToken)
            ->acceptJson()
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}")
            ->json();

        return filled($payment['external_reference'] ?? null)
            ? (string) $payment['external_reference']
            : null;
    }

    public function isPaymentApproved(Request $request, Order $order): bool
    {
        $accessToken = (string) config('services.mercadopago.access_token');

        if ($accessToken === '') {
            return false;
        }

        $paymentId = $request->input('data.id') ?? $request->input('id') ?? $request->input('payment_id');

        if (! filled($paymentId)) {
            return false;
        }

        $payment = Http::withToken($accessToken)
            ->acceptJson()
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}")
            ->json();

        if (($payment['external_reference'] ?? null) !== $order->number) {
            return false;
        }

        return ($payment['status'] ?? null) === 'approved';
    }
}
