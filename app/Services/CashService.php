<?php

namespace App\Services;

use App\Enums\CashMovementSource;
use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Selling;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CashService
{
    /**
     * @param  array{
     *     type: CashMovementType|string,
     *     amount: float|int|string,
     *     description: string,
     *     occurred_at?: Carbon|string|null,
     *     currency?: string|null,
     *     user_id?: string|null,
     *     meta?: array<string, mixed>|null
     * }  $data
     */
    public function createManual(array $data): CashMovement
    {
        $type = $data['type'] instanceof CashMovementType
            ? $data['type']
            : CashMovementType::from((string) $data['type']);

        return CashMovement::query()->create([
            'type' => $type,
            'amount' => round((float) $data['amount'], 2),
            'currency' => $data['currency'] ?? 'PEN',
            'description' => $data['description'],
            'occurred_at' => $data['occurred_at'] ?? now(),
            'source' => CashMovementSource::Manual,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'meta' => $data['meta'] ?? null,
        ]);
    }

    public function recordSellingIncome(Selling $selling): CashMovement
    {
        $selling->loadMissing('lines');

        $amount = round((float) $selling->lines->sum(
            fn ($line): float => (float) $line->quantity * (float) $line->unit_price,
        ), 2);

        return $this->recordForSource(
            type: CashMovementType::Income,
            amount: $amount,
            description: 'Ingreso por venta '.$selling->selling_id,
            source: CashMovementSource::Selling,
            sourceable: $selling,
            occurredAt: $selling->updated_at ?? now(),
            meta: [
                'selling_id' => $selling->selling_id,
            ],
        );
    }

    public function recordPurchaseExpense(Purchase $purchase): CashMovement
    {
        $purchase->loadMissing(['lines', 'expenses', 'currency']);

        $amount = round($purchase->totalInPen(), 2);

        return $this->recordForSource(
            type: CashMovementType::Expense,
            amount: $amount,
            description: 'Egreso por compra '.$purchase->purchase_id,
            source: CashMovementSource::Purchase,
            sourceable: $purchase,
            occurredAt: $purchase->updated_at ?? now(),
            meta: [
                'purchase_id' => $purchase->purchase_id,
                'document_currency' => $purchase->currency?->code,
                'exchange_rate' => $purchase->exchange_rate,
            ],
        );
    }

    public function recordOrderIncome(Order $order): CashMovement
    {
        return $this->recordForSource(
            type: CashMovementType::Income,
            amount: round((float) $order->total, 2),
            description: 'Ingreso por pedido web '.$order->number,
            source: CashMovementSource::Order,
            sourceable: $order,
            occurredAt: $order->paid_at ?? now(),
            meta: [
                'order_number' => $order->number,
                'payment_provider' => $order->payment_provider,
                'payment_reference' => $order->payment_reference,
            ],
        );
    }

    public function removeForSource(Model $sourceable): void
    {
        CashMovement::query()
            ->where('sourceable_type', $sourceable::class)
            ->where('sourceable_id', $sourceable->getKey())
            ->delete();
    }

    /**
     * @return array{income: float, expense: float, balance: float, count: int}
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = CashMovement::query();

        if ($from !== null) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('occurred_at', '<=', $to);
        }

        $income = (float) (clone $query)->incomes()->sum('amount');
        $expense = (float) (clone $query)->expenses()->sum('amount');

        return [
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'balance' => round($income - $expense, 2),
            'count' => (int) $query->count(),
        ];
    }

    private function recordForSource(
        CashMovementType $type,
        float $amount,
        string $description,
        CashMovementSource $source,
        Model $sourceable,
        Carbon|string|null $occurredAt = null,
        ?array $meta = null,
        ?User $user = null,
    ): CashMovement {
        return DB::transaction(function () use ($type, $amount, $description, $source, $sourceable, $occurredAt, $meta, $user) {
            $existing = CashMovement::query()
                ->where('sourceable_type', $sourceable::class)
                ->where('sourceable_id', $sourceable->getKey())
                ->first();

            if ($existing) {
                return $existing;
            }

            return CashMovement::query()->create([
                'type' => $type,
                'amount' => max(0, round($amount, 2)),
                'currency' => 'PEN',
                'description' => $description,
                'occurred_at' => $occurredAt ?? now(),
                'source' => $source,
                'sourceable_type' => $sourceable::class,
                'sourceable_id' => $sourceable->getKey(),
                'user_id' => $user?->id ?? auth()->id(),
                'meta' => $meta,
            ]);
        });
    }
}
