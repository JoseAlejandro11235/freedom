<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
        ]);

        try {
            $this->cart->add(
                $validated['product_id'],
                (int) ($validated['quantity'] ?? 1),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Producto añadido al carrito.');
    }

    public function update(Request $request, string $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        try {
            $this->cart->update($product, (int) $validated['quantity']);
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back();
    }

    public function destroy(string $product): RedirectResponse
    {
        $this->cart->remove($product);

        return back();
    }
}
