<?php

namespace App\Filament\Concerns;

use App\Enums\SellingLineStatus;
use App\Models\LotLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait ManagesStockDocumentLines
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractLinesFromFormData(array &$data): array
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        return is_array($lines) ? $lines : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function syncDocumentLines(Model $record, array $lines, string $relation = 'lines'): void
    {
        /** @var HasMany $relationQuery */
        $relationQuery = $record->{$relation}();
        $relationQuery->delete();

        foreach ($lines as $line) {
            if ((empty($line['product_id']) && empty($line['lot_line_id'])) || empty($line['quantity'])) {
                continue;
            }

            $relationQuery->create($this->lineAttributes($line));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function createDocumentLines(Model $record, array $lines, string $relation = 'lines'): void
    {
        foreach ($lines as $line) {
            if ((empty($line['product_id']) && empty($line['lot_line_id'])) || empty($line['quantity'])) {
                continue;
            }

            $record->{$relation}()->create($this->lineAttributes($line));
        }
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    protected function lineAttributes(array $line): array
    {
        $lotLine = null;

        if (array_key_exists('lot_line_id', $line) && $line['lot_line_id'] !== null && $line['lot_line_id'] !== '') {
            $lotLine = LotLine::query()->with('purchaseLine')->find((int) $line['lot_line_id']);
        }

        $attributes = [
            'product_id' => (int) ($line['product_id'] ?? $lotLine?->product_id),
            'quantity' => (int) $line['quantity'],
        ];

        $sizeId = $line['size_id'] ?? $lotLine?->purchaseLine?->size_id;

        if ($sizeId !== null && $sizeId !== '') {
            $attributes['size_id'] = (int) $sizeId;
        }

        if (array_key_exists('unit_cost', $line) && $line['unit_cost'] !== null && $line['unit_cost'] !== '') {
            $attributes['unit_cost'] = $line['unit_cost'];
        }

        if (array_key_exists('unit_price', $line) && $line['unit_price'] !== null && $line['unit_price'] !== '') {
            $attributes['unit_price'] = $line['unit_price'];
        }

        if (array_key_exists('lot_line_id', $line) && $line['lot_line_id'] !== null && $line['lot_line_id'] !== '') {
            $attributes['lot_line_id'] = (int) $line['lot_line_id'];
            $attributes['lot_id'] = $lotLine?->lot_id;
            $attributes['state'] = SellingLineStatus::Assigned;
        } elseif (array_key_exists('lot_id', $line) && $line['lot_id'] !== null && $line['lot_id'] !== '') {
            $attributes['lot_id'] = (int) $line['lot_id'];
            $attributes['state'] = SellingLineStatus::Assigned;
        } elseif (array_key_exists('state', $line)) {
            $attributes['state'] = SellingLineStatus::Pending;
        }

        return $attributes;
    }
}
