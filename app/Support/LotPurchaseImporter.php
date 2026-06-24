<?php

namespace App\Support;

use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchaseStatus;
use Illuminate\Support\Str;

class LotPurchaseImporter
{
    /**
     * Paid purchases that still have pending lines to receive.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return Purchase::query()
            ->whereHas('status', fn ($query) => $query->where('code', PurchaseStatus::PAID))
            ->whereHas('lines', fn ($query) => $query->where('pending_quantity', '>', 0))
            ->withCount(['lines as pending_lines_count' => fn ($query) => $query->where('pending_quantity', '>', 0)])
            ->with('provider.persona')
            ->orderByDesc('created_at')
            ->get()
            ->mapWithKeys(fn (Purchase $purchase): array => [
                $purchase->id => self::optionLabel($purchase),
            ])
            ->all();
    }

    /**
     * Build the lot lines repeater state from a purchase's pending lines.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function pendingLineItems(?string $purchaseId): array
    {
        if (blank($purchaseId)) {
            return [];
        }

        $purchase = Purchase::query()
            ->with(['lines' => fn ($query) => $query->where('pending_quantity', '>', 0)])
            ->find($purchaseId);

        if ($purchase === null) {
            return [];
        }

        $items = [];

        foreach ($purchase->lines as $line) {
            /** @var PurchaseLine $line */
            $items[(string) Str::uuid()] = [
                'id' => null,
                'purchase_line_id' => $line->id,
                'quantity_received' => max(1, (int) $line->pending_quantity),
            ];
        }

        return $items;
    }

    private static function optionLabel(Purchase $purchase): string
    {
        $provider = $purchase->provider?->displayName();
        $providerText = filled($provider) ? " · {$provider}" : '';

        return "{$purchase->purchase_id}{$providerText} · líneas pendientes: {$purchase->pending_lines_count}";
    }
}
