<?php

namespace App\Support;

use App\Models\Currency;
use App\Models\Purchase;
use Filament\Schemas\Components\Utilities\Get;

class PurchaseTotals
{
    /**
     * @param  array<int, array<string, mixed>>|null  $lines
     */
    public static function linesSubtotal(?array $lines): float
    {
        return (float) collect($lines ?? [])
            ->sum(function (array $line): float {
                $quantity = (float) ($line['quantity'] ?? 0);
                $unitCost = (float) ($line['unit_cost'] ?? 0);

                return $quantity * $unitCost;
            });
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $expenses
     */
    public static function expensesSubtotal(?array $expenses): float
    {
        return (float) collect($expenses ?? [])
            ->sum(fn (array $expense): float => (float) ($expense['amount'] ?? 0));
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $lines
     * @param  array<int, array<string, mixed>>|null  $expenses
     */
    public static function total(?array $lines, ?array $expenses): float
    {
        return self::linesSubtotal($lines) + self::expensesSubtotal($expenses);
    }

    public static function totalFromGet(Get $get): float
    {
        return self::total($get('lines'), $get('other_expenses'));
    }

    public static function normalizedExchangeRate(
        float|string|null $exchangeRate,
        Currency|\App\Enums\Currency|string|null $currency = null,
    ): float {
        $currency = Currency::resolve($currency);

        if ($currency->isBase()) {
            return 1.0;
        }

        $rate = (float) ($exchangeRate ?? 1);

        return $rate > 0 ? $rate : 1.0;
    }

    public static function totalInPen(
        float $total,
        Currency|\App\Enums\Currency|string|null $currency,
        float|string|null $exchangeRate,
    ): float {
        $currency = Currency::resolve($currency);

        if ($currency->isBase()) {
            return $total;
        }

        return $total * self::normalizedExchangeRate($exchangeRate, $currency);
    }

    public static function totalInPenFromGet(Get $get): float
    {
        return self::totalInPen(
            self::totalFromGet($get),
            $get('currency_id'),
            $get('exchange_rate'),
        );
    }

    public static function formatMoney(float $amount, Currency|\App\Enums\Currency|string|null $currency = null): string
    {
        $currency = Currency::resolve($currency);

        return $currency->symbol.' '.number_format($amount, 2, '.', ',');
    }

    public static function formatExchangeRate(float|string|null $exchangeRate): string
    {
        return number_format(self::normalizedExchangeRate($exchangeRate), 4, '.', ',');
    }

    public static function formattedTotalFromGet(Get $get): string
    {
        return self::formatMoney(self::totalFromGet($get), $get('currency_id'));
    }

    public static function formattedTotalInPenFromGet(Get $get): string
    {
        return self::formatMoney(self::totalInPenFromGet($get), Currency::base());
    }

    public static function formattedTotalForPurchase(Purchase $purchase): string
    {
        $purchase->loadMissing(['lines', 'expenses']);

        $lines = $purchase->lines
            ->map(fn ($line) => [
                'quantity' => $line->quantity,
                'unit_cost' => $line->unit_cost,
            ])
            ->all();

        $expenses = $purchase->expenses
            ->map(fn ($expense) => ['amount' => $expense->amount])
            ->all();

        return self::formatMoney(self::total($lines, $expenses), $purchase->currency);
    }

    public static function formattedTotalInPenForPurchase(Purchase $purchase): string
    {
        return self::formatMoney($purchase->totalInPen(), Currency::base());
    }
}
