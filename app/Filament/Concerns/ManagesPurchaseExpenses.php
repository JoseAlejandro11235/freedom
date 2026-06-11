<?php

namespace App\Filament\Concerns;

use App\Models\Purchase;
use App\Models\PurchaseExpenseConcept;

trait ManagesPurchaseExpenses
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function extractExpensesFromFormData(array &$data): array
    {
        $expenses = $data['other_expenses'] ?? [];
        unset($data['other_expenses']);

        return is_array($expenses) ? array_values($expenses) : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $expenses
     */
    protected function syncPurchaseExpenses(Purchase $purchase, array $expenses): void
    {
        $purchase->expenses()->delete();

        foreach ($expenses as $sortOrder => $expense) {
            $conceptId = $expense['purchase_expense_concept_id'] ?? null;
            $amount = $expense['amount'] ?? null;

            if (($conceptId === null || $conceptId === '') && ($amount === null || $amount === '')) {
                continue;
            }

            $concept = $conceptId !== null && $conceptId !== ''
                ? PurchaseExpenseConcept::query()->find($conceptId)
                : null;

            $purchase->expenses()->create([
                'purchase_expense_concept_id' => $concept?->id,
                'description' => $concept?->name ?? 'Gasto',
                'amount' => $amount ?? 0,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
