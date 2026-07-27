<?php

namespace App\Services;

use App\Models\Product;
use App\Support\CatalogPresenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

class CartService
{
    private const SESSION_KEY = 'cart.items';

    /**
     * @return array<string, int> productId => quantity
     */
    public function raw(): array
    {
        /** @var array<string, int> $items */
        $items = Session::get(self::SESSION_KEY, []);

        return collect($items)
            ->map(fn ($quantity) => max(0, (int) $quantity))
            ->filter(fn (int $quantity) => $quantity > 0)
            ->all();
    }

    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(): Collection
    {
        $quantities = $this->raw();

        if ($quantities === []) {
            return collect();
        }

        $products = Product::query()
            ->with(['brand', 'images'])
            ->published()
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        $staleIds = [];

        $items = collect($quantities)
            ->map(function (int $quantity, string $productId) use ($products, &$staleIds) {
                $product = $products->get($productId);

                if (! $product instanceof Product) {
                    $staleIds[] = $productId;

                    return null;
                }

                $presented = CatalogPresenter::product($product);
                $maxQuantity = $product->track_inventory ? max(0, $product->stock_quantity) : null;

                if ($maxQuantity !== null && $quantity > $maxQuantity) {
                    $quantity = $maxQuantity;
                    $this->persistQuantity($productId, $quantity);
                }

                if ($quantity < 1) {
                    $staleIds[] = $productId;

                    return null;
                }

                return [
                    ...$presented,
                    'quantity' => $quantity,
                    'maxQuantity' => $maxQuantity,
                    'lineTotal' => round(((float) $product->selling_price) * $quantity, 2),
                ];
            })
            ->filter()
            ->values();

        foreach ($staleIds as $productId) {
            $this->remove($productId);
        }

        return $items;
    }

    /**
     * @return array{count: int, subtotal: float, items: list<array<string, mixed>>}
     */
    public function summary(): array
    {
        $items = $this->items();

        return [
            'count' => (int) $items->sum('quantity'),
            'subtotal' => round((float) $items->sum('lineTotal'), 2),
            'items' => $items->all(),
        ];
    }

    public function add(string $productId, int $quantity = 1): void
    {
        $quantity = max(1, $quantity);
        $product = $this->findPurchasable($productId);
        $current = $this->raw()[$product->id] ?? 0;
        $this->setQuantity($product->id, $current + $quantity);
    }

    public function update(string $productId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($productId);

            return;
        }

        $product = $this->findPurchasable($productId);
        $this->setQuantity($product->id, $quantity);
    }

    public function remove(string $productId): void
    {
        $items = $this->raw();
        unset($items[$productId]);
        Session::put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    private function setQuantity(string $productId, int $quantity): void
    {
        $product = $this->findPurchasable($productId);
        $maxQuantity = $product->track_inventory ? $product->stock_quantity : null;

        if ($maxQuantity !== null && $quantity > $maxQuantity) {
            throw new InvalidArgumentException('No hay suficiente stock para este producto.');
        }

        $this->persistQuantity($product->id, $quantity);
    }

    private function persistQuantity(string $productId, int $quantity): void
    {
        $items = $this->raw();

        if ($quantity < 1) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }

        Session::put(self::SESSION_KEY, $items);
    }

    private function findPurchasable(string $productId): Product
    {
        $product = Product::query()->published()->find($productId);

        if (! $product instanceof Product) {
            throw new InvalidArgumentException('Producto no disponible.');
        }

        if (! $product->isInStock()) {
            throw new InvalidArgumentException('Este producto está agotado.');
        }

        return $product;
    }
}
