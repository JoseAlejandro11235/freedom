<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\Gateways\CulqiPaymentGateway;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {}

    public function create(): Response|RedirectResponse
    {
        $summary = $this->cart->summary();

        if ($summary['count'] < 1) {
            return redirect()->route('home')->with('error', 'Tu carrito está vacío.');
        }

        return Inertia::render('checkout/create', [
            'cart' => $summary,
            'paymentProvider' => config('services.payments.driver', 'fake'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:64'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_district' => ['nullable', 'string', 'max:120'],
            'shipping_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->checkout->begin([
                ...$validated,
                'user_id' => $request->user()?->id,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo iniciar el pago. Inténtalo de nuevo.');
        }

        if (! filled($order->payment_url)) {
            return back()->with('error', 'No se pudo generar el enlace de pago.');
        }

        return redirect()->away($order->payment_url);
    }

    public function fakePay(Order $order): Response|RedirectResponse
    {
        if ($order->payment_provider !== 'fake') {
            abort(404);
        }

        if ($order->isPaid()) {
            return redirect()->route('checkout.success', $order);
        }

        return Inertia::render('checkout/fake-pay', [
            'order' => $this->presentOrder($order),
        ]);
    }

    public function fakeConfirm(Request $request, Order $order): RedirectResponse
    {
        if ($order->payment_provider !== 'fake') {
            abort(404);
        }

        $approved = $request->boolean('approved', true);

        if ($approved) {
            $this->checkout->markPaid($order);
            $this->cart->clear();

            return redirect()->route('checkout.success', $order);
        }

        $this->checkout->markFailed($order);

        return redirect()->route('checkout.failure', $order);
    }

    public function culqiPay(Order $order): Response|RedirectResponse
    {
        if ($order->payment_provider !== 'culqi') {
            abort(404);
        }

        if ($order->isPaid()) {
            return redirect()->route('checkout.success', $order);
        }

        $publicKey = (string) config('services.culqi.public_key');

        if ($publicKey === '') {
            return redirect()->route('checkout.failure', $order)
                ->with('error', 'Culqi no está configurado.');
        }

        return Inertia::render('checkout/culqi-pay', [
            'order' => $this->presentOrder($order),
            'culqiPublicKey' => $publicKey,
        ]);
    }

    public function culqiCharge(Request $request, Order $order, CulqiPaymentGateway $culqi): RedirectResponse
    {
        if ($order->payment_provider !== 'culqi') {
            abort(404);
        }

        if ($order->isPaid()) {
            $this->cart->clear();

            return redirect()->route('checkout.success', $order);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        try {
            $charge = $culqi->createCharge($order, $validated['token']);
            $this->checkout->markPaid($order, $charge['id']);
            $this->cart->clear();

            return redirect()->route('checkout.success', $order);
        } catch (\Throwable $exception) {
            report($exception);
            $this->checkout->markFailed($order);

            return redirect()->route('checkout.failure', $order)
                ->with('error', $exception->getMessage() ?: 'No se pudo completar el pago.');
        }
    }

    public function success(Order $order): Response
    {
        if ($order->isPaid()) {
            $this->cart->clear();
        }

        return Inertia::render('checkout/success', [
            'order' => $this->presentOrder($order),
        ]);
    }

    public function pending(Order $order): Response
    {
        return Inertia::render('checkout/pending', [
            'order' => $this->presentOrder($order),
        ]);
    }

    public function failure(Order $order): Response
    {
        return Inertia::render('checkout/failure', [
            'order' => $this->presentOrder($order),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentOrder(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'statusLabel' => $order->status->label(),
            'customerName' => $order->customer_name,
            'customerEmail' => $order->customer_email,
            'total' => (float) $order->total,
            'currency' => $order->currency,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->product_name,
                'brand' => $item->brand_name,
                'quantity' => $item->quantity,
                'unitPrice' => (float) $item->unit_price,
                'lineTotal' => (float) $item->line_total,
            ])->values()->all(),
        ];
    }
}
