<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentCheckout;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CulqiPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'culqi';
    }

    public function createCheckout(Order $order): PaymentCheckout
    {
        $publicKey = (string) config('services.culqi.public_key');
        $secretKey = (string) config('services.culqi.secret_key');

        if ($publicKey === '' || $secretKey === '') {
            throw new RuntimeException('Culqi no está configurado. Define CULQI_PUBLIC_KEY y CULQI_SECRET_KEY.');
        }

        return new PaymentCheckout(
            provider: $this->name(),
            reference: 'culqi_'.Str::lower(Str::random(16)),
            redirectUrl: route('checkout.culqi.pay', $order),
        );
    }

    /**
     * Create a Culqi charge from a Checkout token and return the charge id.
     *
     * @return array{id: string, raw: array<string, mixed>}
     */
    public function createCharge(Order $order, string $token): array
    {
        $secretKey = (string) config('services.culqi.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('Culqi no está configurado. Define CULQI_SECRET_KEY.');
        }

        $amountCents = (int) round(((float) $order->total) * 100);

        if ($amountCents < 100) {
            throw new RuntimeException('El monto mínimo de pago en Culqi es S/1.00.');
        }

        $payload = [
            'amount' => $amountCents,
            'currency_code' => $order->currency ?: 'PEN',
            'email' => $order->customer_email,
            'source_id' => $token,
            'description' => 'Pedido '.$order->number,
            'metadata' => [
                'order_number' => $order->number,
                'order_id' => (string) $order->id,
            ],
        ];

        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->acceptJson()
                ->asJson()
                ->post('https://api.culqi.com/v2/charges', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = data_get($exception->response?->json(), 'user_message')
                ?? data_get($exception->response?->json(), 'merchant_message')
                ?? 'No se pudo procesar el pago con Culqi.';

            throw new RuntimeException((string) $message, previous: $exception);
        }

        $chargeId = (string) ($response['id'] ?? '');
        $outcomeType = (string) data_get($response, 'outcome.type', '');

        if ($chargeId === '' || ($outcomeType !== '' && $outcomeType !== 'venta_exitosa')) {
            $userMessage = (string) data_get($response, 'outcome.user_message', 'El pago no fue aprobado.');

            throw new RuntimeException($userMessage);
        }

        return [
            'id' => $chargeId,
            'raw' => is_array($response) ? $response : [],
        ];
    }

    public function resolveOrderNumber(Request $request): ?string
    {
        $fromMetadata = $request->input('data.metadata.order_number')
            ?? $request->input('metadata.order_number');

        if (filled($fromMetadata)) {
            return (string) $fromMetadata;
        }

        $chargeId = $request->input('data.id') ?? $request->input('id');

        if (! filled($chargeId) || ! str_starts_with((string) $chargeId, 'chr_')) {
            return null;
        }

        $secretKey = (string) config('services.culqi.secret_key');

        if ($secretKey === '') {
            return null;
        }

        $charge = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->get('https://api.culqi.com/v2/charges/'.$chargeId)
            ->json();

        $orderNumber = data_get($charge, 'metadata.order_number');

        return filled($orderNumber) ? (string) $orderNumber : null;
    }

    public function isPaymentApproved(Request $request, Order $order): bool
    {
        $type = (string) $request->input('type', '');

        if ($type !== '' && $type !== 'charge.creation.succeeded') {
            return false;
        }

        $orderNumber = $request->input('data.metadata.order_number')
            ?? $request->input('metadata.order_number');

        if (filled($orderNumber) && (string) $orderNumber === $order->number) {
            $outcomeType = (string) (
                $request->input('data.outcome.type')
                ?? $request->input('outcome.type')
                ?? 'venta_exitosa'
            );

            return $outcomeType === 'venta_exitosa';
        }

        $secretKey = (string) config('services.culqi.secret_key');
        $chargeId = $request->input('data.id') ?? $request->input('id');

        if ($secretKey === '' || ! filled($chargeId)) {
            return false;
        }

        $charge = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->get('https://api.culqi.com/v2/charges/'.$chargeId)
            ->json();

        if (data_get($charge, 'metadata.order_number') !== $order->number) {
            return false;
        }

        return data_get($charge, 'outcome.type') === 'venta_exitosa';
    }
}
