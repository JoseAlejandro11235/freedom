<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PaymentGateway $payments,
        private readonly CashService $cash,
    ) {}

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_email: string,
     *     customer_phone?: string|null,
     *     shipping_address: string,
     *     shipping_city: string,
     *     shipping_district?: string|null,
     *     shipping_notes?: string|null,
     *     user_id?: string|null
     * }  $customer
     */
    public function begin(array $customer): Order
    {
        $cartItems = $this->cart->items();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Tu carrito está vacío.',
            ]);
        }

        $order = DB::transaction(function () use ($customer, $cartItems) {
            $subtotal = round((float) $cartItems->sum('lineTotal'), 2);

            $order = Order::query()->create([
                'number' => $this->generateOrderNumber(),
                'status' => OrderStatus::PendingPayment,
                'user_id' => $customer['user_id'] ?? null,
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'customer_phone' => $customer['customer_phone'] ?? null,
                'shipping_address' => $customer['shipping_address'],
                'shipping_city' => $customer['shipping_city'],
                'shipping_district' => $customer['shipping_district'] ?? null,
                'shipping_notes' => $customer['shipping_notes'] ?? null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'currency' => 'PEN',
                'payment_provider' => $this->payments->name(),
            ]);

            foreach ($cartItems as $item) {
                /** @var Product|null $product */
                $product = Product::query()
                    ->with('brand')
                    ->lockForUpdate()
                    ->find($item['id']);

                if (! $product instanceof Product || ! $product->is_published) {
                    throw ValidationException::withMessages([
                        'cart' => "El producto {$item['name']} ya no está disponible.",
                    ]);
                }

                if ($product->track_inventory && $product->stock_quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => "No hay stock suficiente de {$product->name}.",
                    ]);
                }

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'brand_name' => $product->brand?->name,
                    'product_code' => $product->code,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'line_total' => round(((float) $product->selling_price) * $item['quantity'], 2),
                    'image_path' => $product->primaryImage()?->path,
                ]);
            }

            return $order->load('items');
        });

        $checkout = $this->payments->createCheckout($order);

        $order->update([
            'payment_provider' => $checkout->provider,
            'payment_reference' => $checkout->reference,
            'payment_url' => $checkout->redirectUrl,
        ]);

        return $order->fresh('items');
    }

    public function markPaid(Order $order, ?string $paymentReference = null): Order
    {
        if ($order->isPaid()) {
            return $order;
        }

        return DB::transaction(function () use ($order, $paymentReference) {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->isPaid()) {
                return $locked;
            }

            $locked->load('items.product');

            foreach ($locked->items as $item) {
                $product = $item->product;

                if (! $product instanceof Product || ! $product->track_inventory) {
                    continue;
                }

                /** @var Product $product */
                $product = Product::query()->lockForUpdate()->findOrFail($product->id);

                if ($product->stock_quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'stock' => "Stock insuficiente para {$product->name}.",
                    ]);
                }

                $product->stock_quantity -= $item->quantity;
                $product->save();
            }

            $locked->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'payment_reference' => $paymentReference ?: $locked->payment_reference,
            ]);

            $paid = $locked->fresh('items');

            if ($paid) {
                $this->cash->recordOrderIncome($paid);
            }

            return $paid;
        });
    }

    public function markFailed(Order $order): Order
    {
        if ($order->isPaid()) {
            return $order;
        }

        $order->update(['status' => OrderStatus::Failed]);

        return $order->fresh();
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
