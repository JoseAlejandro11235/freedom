<?php

namespace App\Filament\Resources\Lots\Pages;

use App\Filament\Resources\Lots\LotResource;
use App\Models\PurchaseLine;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateLot extends CreateRecord
{
    protected static string $resource = LotResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $lines = $this->extractLines($data);
        $firstLine = $lines[0] ?? [];
        $purchaseLine = PurchaseLine::query()->findOrFail($firstLine['purchase_line_id']);

        $data['lot_number'] = filled($data['lot_number'] ?? null)
            ? $data['lot_number']
            : sprintf('LOT-%06d', $purchaseLine->id);
        $data['received_at'] ??= now();

        return DB::transaction(function () use ($data, $lines): Model {
            $lot = static::getModel()::query()->create($data);
            $this->syncLines($lot, $lines);

            return $lot->fresh('lines');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function extractLines(array &$data): array
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        return is_array($lines) ? array_values($lines) : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncLines(Model $lot, array $lines): void
    {
        foreach ($lines as $line) {
            if (empty($line['purchase_line_id'])) {
                continue;
            }

            $purchaseLine = PurchaseLine::query()->findOrFail($line['purchase_line_id']);
            $quantityReceived = min(
                max(1, (int) ($line['quantity_received'] ?? $purchaseLine->pending_quantity)),
                max(1, (int) $purchaseLine->pending_quantity),
            );

            $lot->lines()->create([
                'purchase_line_id' => $purchaseLine->id,
                'product_id' => $purchaseLine->product_id,
                'quantity_received' => $quantityReceived,
                'quantity_available' => $quantityReceived,
                'unit_cost' => $purchaseLine->unit_cost,
            ]);
        }

        $lot->refreshTotalsFromLines();
    }
}
