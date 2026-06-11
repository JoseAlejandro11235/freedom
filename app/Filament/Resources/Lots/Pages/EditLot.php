<?php

namespace App\Filament\Resources\Lots\Pages;

use App\Filament\Resources\Lots\LotResource;
use App\Models\PurchaseLine;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditLot extends EditRecord
{
    protected static string $resource = LotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['lines'] = $this->getRecord()->lines()
            ->get()
            ->map(fn ($line) => [
                'id' => $line->id,
                'purchase_line_id' => $line->purchase_line_id,
                'quantity_received' => $line->quantity_received,
            ])
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $lines = $this->extractLines($data);
        return DB::transaction(function () use ($record, $data, $lines): Model {
            $record->update($data);
            $this->syncLines($record, $lines);

            return $record->fresh('lines');
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
        $keptIds = [];

        foreach ($lines as $line) {
            if (empty($line['purchase_line_id'])) {
                continue;
            }

            $purchaseLine = PurchaseLine::query()->findOrFail($line['purchase_line_id']);
            $existingLotLine = filled($line['id'] ?? null)
                ? $lot->lines()->whereKey($line['id'])->first()
                : null;
            $alreadyReceived = (int) ($existingLotLine?->quantity_received ?? 0);
            $alreadyConsumed = max(0, $alreadyReceived - (int) ($existingLotLine?->quantity_available ?? 0));
            $maxReceivable = max(1, (int) $purchaseLine->pending_quantity + $alreadyReceived);
            $quantityReceived = min(
                max(1, (int) ($line['quantity_received'] ?? $maxReceivable)),
                $maxReceivable,
            );

            $attributes = [
                'purchase_line_id' => $purchaseLine->id,
                'product_id' => $purchaseLine->product_id,
                'quantity_received' => $quantityReceived,
                'quantity_available' => max(0, $quantityReceived - $alreadyConsumed),
                'unit_cost' => $purchaseLine->unit_cost,
            ];

            $lotLine = $existingLotLine;

            if ($lotLine === null) {
                $lotLine = $lot->lines()->create($attributes);
            } else {
                $lotLine->update($attributes);
            }

            $keptIds[] = $lotLine->id;
        }

        $lot->lines()->whereKeyNot($keptIds)->delete();
        $lot->refreshTotalsFromLines();
    }
}
