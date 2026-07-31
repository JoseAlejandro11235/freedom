<?php

namespace App\Services;

use App\Enums\SellingLineStatus;
use App\Enums\StockDocumentStatus;
use App\Models\LotLine;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseStatus;
use App\Models\Selling;
use App\Models\SellingLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockDocumentService
{
    public function __construct(
        private readonly CashService $cash,
    ) {}

    public function confirmPurchase(Purchase $purchase): Purchase
    {
        return $this->transitionPurchase($purchase, PurchaseStatus::APPROVED);
    }

    public function cancelPurchase(Purchase $purchase): Purchase
    {
        throw ValidationException::withMessages([
            'status' => __('Purchases can only be DRAFT, APPROVED, or PAID.'),
        ]);
    }

    public function payPurchase(Purchase $purchase): Purchase
    {
        return $this->transitionPurchase($purchase, PurchaseStatus::PAID);
    }

    public function confirmSelling(Selling $selling): Selling
    {
        return $this->transitionSelling($selling, StockDocumentStatus::Confirmed, applyStock: true);
    }

    public function cancelSelling(Selling $selling): Selling
    {
        return $this->transitionSelling($selling, StockDocumentStatus::Cancelled, applyStock: false, reverseStock: true);
    }

    public function generatePurchaseId(): string
    {
        return $this->generateDocumentId(Purchase::class, 'purchase_id', 'PUR');
    }

    public function generateSellingId(): string
    {
        return $this->generateDocumentId(Selling::class, 'selling_id', 'VEN');
    }

    /**
     * @param  class-string<Purchase|Selling>  $modelClass
     */
    private function generateDocumentId(string $modelClass, string $column, string $prefix): string
    {
        $lastNumber = $modelClass::query()
            ->where($column, 'like', "{$prefix}-%")
            ->pluck($column)
            ->map(fn (string $id) => (int) Str::afterLast($id, '-'))
            ->max();

        return sprintf('%s-%06d', $prefix, ($lastNumber ?? 0) + 1);
    }

    private function transitionPurchase(
        Purchase $purchase,
        string $toStatus,
    ): Purchase {
        $purchase->loadMissing(['lines.product', 'lines.lotLines']);

        $this->assertHasLines($purchase->lines, 'purchase');

        return DB::transaction(function () use ($purchase, $toStatus) {
            if ($toStatus === PurchaseStatus::APPROVED) {
                $this->assertPurchaseStatus($purchase, PurchaseStatus::DRAFT, 'approve');
            } elseif ($toStatus === PurchaseStatus::PAID) {
                $this->assertPurchaseStatus($purchase, PurchaseStatus::APPROVED, 'pay');
            } else {
                throw $this->invalidTransition('purchase', $toStatus);
            }

            $purchase->status = $toStatus;
            $purchase->save();

            $fresh = $purchase->fresh(['lines.product', 'status', 'user', 'expenses', 'currency']);

            if ($toStatus === PurchaseStatus::PAID && $fresh) {
                $this->cash->recordPurchaseExpense($fresh);
            }

            return $fresh;
        });
    }

    private function transitionSelling(
        Selling $selling,
        StockDocumentStatus $toStatus,
        bool $applyStock = false,
        bool $reverseStock = false,
    ): Selling {
        $selling->loadMissing(['lines.product', 'lines.lotLine.lot', 'lines.lotLine.purchaseLine']);

        $this->assertHasLines($selling->lines, 'selling');

        return DB::transaction(function () use ($selling, $toStatus, $applyStock, $reverseStock) {
            $fromStatus = $selling->status;

            if ($toStatus === StockDocumentStatus::Confirmed) {
                $this->assertStatus($selling, StockDocumentStatus::Draft, 'confirm');
                $this->validateProductStockForSelling($selling->lines);
                $this->assignLatestLotLines($selling);
                $selling->load(['lines.product', 'lines.lotLine.lot', 'lines.lotLine.purchaseLine']);
                $this->assertUnassignedLinesWithoutLots($selling->lines);
                $this->applyLotStock($selling->lines, multiplier: -1);
                $this->applySellingStock($selling->lines, multiplier: -1);
                $this->setSellingLineState($selling->lines, SellingLineStatus::Confirmed);
            } elseif ($toStatus === StockDocumentStatus::Cancelled) {
                if ($fromStatus === StockDocumentStatus::Confirmed) {
                    $this->applyLotStock($selling->lines, multiplier: 1);
                    $this->applySellingStock($selling->lines, multiplier: 1);
                    $this->setSellingLineState($selling->lines, SellingLineStatus::Cancelled);
                } elseif ($fromStatus !== StockDocumentStatus::Draft) {
                    throw $this->invalidTransition('selling', 'cancel');
                }
            } else {
                throw $this->invalidTransition('selling', $toStatus->value);
            }

            unset($applyStock, $reverseStock);

            $selling->status = $toStatus;
            $selling->save();

            $fresh = $selling->fresh(['lines.product', 'user']);

            if ($toStatus === StockDocumentStatus::Confirmed && $fresh) {
                $this->cash->recordSellingIncome($fresh);
            }

            if ($toStatus === StockDocumentStatus::Cancelled && $fromStatus === StockDocumentStatus::Confirmed && $fresh) {
                $this->cash->removeForSource($fresh);
            }

            return $fresh;
        });
    }

    /**
     * @param  iterable<int, SellingLine>  $lines
     */
    private function validateProductStockForSelling(iterable $lines): void
    {
        foreach ($lines as $index => $line) {
            $product = Product::query()->whereKey($line->product_id)->lockForUpdate()->firstOrFail();

            if (! $product->track_inventory) {
                throw ValidationException::withMessages([
                    "lines.{$index}.product_id" => __('Inventory tracking is disabled for :name.', ['name' => $product->name]),
                ]);
            }

            if ($product->stock_quantity < $line->quantity) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => __('Not enough stock for :name. Current stock: :stock.', [
                        'name' => $product->name,
                        'stock' => $product->stock_quantity,
                    ]),
                ]);
            }
        }
    }

    private function assignLatestLotLines(Selling $selling): void
    {
        foreach ($selling->lines as $line) {
            if ($line->lot_line_id !== null) {
                continue;
            }

            $lotLine = $this->findLatestAvailableLotLine(
                $line->product_id,
                $line->size_id,
                $line->quantity,
            );

            if ($lotLine === null) {
                continue;
            }

            $line->lot_line_id = $lotLine->id;
            $line->lot_id = $lotLine->lot_id;
            $line->state = SellingLineStatus::Assigned;
            $line->save();
        }
    }

    private function findLatestAvailableLotLine(string $productId, ?string $sizeId, int $quantity): ?LotLine
    {
        return LotLine::query()
            ->where('lot_lines.product_id', $productId)
            ->where('lot_lines.quantity_available', '>=', $quantity)
            ->when(
                $sizeId !== null,
                fn ($query) => $query->whereHas(
                    'purchaseLine',
                    fn ($query) => $query->where('size_id', $sizeId),
                ),
            )
            ->join('lots', 'lots.id', '=', 'lot_lines.lot_id')
            ->orderByDesc('lots.received_at')
            ->select('lot_lines.*')
            ->first();
    }

    /**
     * @param  iterable<int, SellingLine>  $lines
     */
    private function assertUnassignedLinesWithoutLots(iterable $lines): void
    {
        foreach ($lines as $index => $line) {
            if ($line->lot_line_id !== null) {
                continue;
            }

            $hasLotLines = LotLine::query()
                ->where('product_id', $line->product_id)
                ->exists();

            if ($hasLotLines) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => __('No lot with enough available quantity for :name.', [
                        'name' => $line->product?->name ?? $line->product_id,
                    ]),
                ]);
            }
        }
    }

    /**
     * @param  iterable<int, SellingLine>  $lines
     */
    private function applySellingStock(iterable $lines, int $multiplier): void
    {
        foreach ($lines as $index => $line) {
            if ($line->lot_line_id !== null || $line->lot_id !== null) {
                continue;
            }

            $this->adjustProductStock(
                $line->product,
                $line->quantity * $multiplier,
                "lines.{$index}.quantity",
                allowNegativeCheck: $multiplier < 0,
            );
        }
    }

    /**
     * @param  iterable<int, SellingLine>  $lines
     */
    private function applyLotStock(iterable $lines, int $multiplier): void
    {
        foreach ($lines as $index => $line) {
            if ($line->lot_line_id === null) {
                continue;
            }

            $lotLine = LotLine::query()
                ->with('lot')
                ->whereKey($line->lot_line_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lotLine->product_id !== $line->product_id) {
                throw ValidationException::withMessages([
                    "lines.{$index}.lot_id" => __('The selected lot does not belong to the selected product.'),
                ]);
            }

            if ($lotLine->purchaseLine?->size_id !== $line->size_id) {
                throw ValidationException::withMessages([
                    "lines.{$index}.lot_line_id" => __('The selected lot does not belong to the selected size.'),
                ]);
            }

            $newQuantity = $lotLine->quantity_available + ($line->quantity * $multiplier);

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => __('Not enough quantity in lot :lot. Available: :stock.', [
                        'lot' => $lotLine->lot?->lot_number ?? $lotLine->lot_id,
                        'stock' => $lotLine->quantity_available,
                    ]),
                ]);
            }

            if ($newQuantity > $lotLine->quantity_received) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => __('Lot :lot cannot exceed its received quantity.', [
                        'lot' => $lotLine->lot?->lot_number ?? $lotLine->lot_id,
                    ]),
                ]);
            }

            $lotLine->quantity_available = $newQuantity;
            $lotLine->save();
        }
    }

    /**
     * @param  iterable<int, SellingLine>  $lines
     */
    private function setSellingLineState(iterable $lines, SellingLineStatus $status): void
    {
        foreach ($lines as $line) {
            $line->state = $status;
            $line->save();
        }
    }

    private function adjustProductStock(
        Product $product,
        int $delta,
        string $field,
        bool $allowNegativeCheck = true,
    ): void {
        $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

        if (! $locked->track_inventory) {
            throw ValidationException::withMessages([
                $field => __('Inventory tracking is disabled for :name.', ['name' => $locked->name]),
            ]);
        }

        $newQuantity = $locked->stock_quantity + $delta;

        if ($allowNegativeCheck && $newQuantity < 0) {
            throw ValidationException::withMessages([
                $field => __('Not enough stock for :name. Current stock: :stock.', [
                    'name' => $locked->name,
                    'stock' => $locked->stock_quantity,
                ]),
            ]);
        }

        $locked->stock_quantity = $newQuantity;
        $locked->save();
    }

    /**
     * @param  iterable<int, mixed>  $lines
     */
    private function assertHasLines(iterable $lines, string $document): void
    {
        if (count($lines) === 0) {
            throw ValidationException::withMessages([
                'lines' => __('Add at least one product line.'),
            ]);
        }
    }

    private function assertStatus(Purchase|Selling $document, StockDocumentStatus $expected, string $action): void
    {
        if ($document->status !== $expected) {
            throw ValidationException::withMessages([
                'status' => __('Cannot :action this document while it is :status.', [
                    'action' => $action,
                    'status' => $document->status->label(),
                ]),
            ]);
        }
    }

    private function assertPurchaseStatus(Purchase $purchase, string $expectedCode, string $action): void
    {
        if (! ($purchase->status?->isCode($expectedCode) ?? false)) {
            throw ValidationException::withMessages([
                'status' => __('Cannot :action this document while it is :status.', [
                    'action' => $action,
                    'status' => $purchase->status?->label() ?? '—',
                ]),
            ]);
        }
    }

    private function invalidTransition(string $document, string $action): ValidationException
    {
        return ValidationException::withMessages([
            'status' => __('Invalid :document status transition for :action.', [
                'document' => $document,
                'action' => $action,
            ]),
        ]);
    }
}
