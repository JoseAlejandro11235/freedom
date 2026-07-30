<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\CulqiPaymentGateway;
use App\Payments\Gateways\FakePaymentGateway;
use App\Payments\Gateways\MercadoPagoPaymentGateway;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    public function __invoke(Request $request, string $provider): Response
    {
        $gateway = $this->resolveGateway($provider);

        $orderNumber = $gateway->resolveOrderNumber($request);

        if (! filled($orderNumber)) {
            return response('ignored', 200);
        }

        $order = Order::query()->where('number', $orderNumber)->first();

        if (! $order) {
            return response('order not found', 404);
        }

        if ($gateway->isPaymentApproved($request, $order)) {
            $reference = $request->input('data.id')
                ?? $request->input('id')
                ?? $request->input('payment_id');
            $this->checkout->markPaid($order, filled($reference) ? (string) $reference : null);
        }

        return response('ok', 200);
    }

    private function resolveGateway(string $provider): PaymentGateway
    {
        return match ($provider) {
            'fake' => app(FakePaymentGateway::class),
            'culqi' => app(CulqiPaymentGateway::class),
            'mercadopago' => app(MercadoPagoPaymentGateway::class),
            default => throw new NotFoundHttpException('Unknown payment provider.'),
        };
    }
}
