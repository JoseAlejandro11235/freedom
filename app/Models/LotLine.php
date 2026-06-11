<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotLine extends Model
{
    protected $fillable = [
        'lot_id',
        'purchase_line_id',
        'product_id',
        'quantity_received',
        'quantity_available',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'integer',
            'quantity_available' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (LotLine $line): void {
            Lot::refreshProductStock((int) $line->product_id);
            $line->purchaseLine?->refreshPendingQuantity();
            $line->lot?->refreshTotalsFromLines();

            if ($line->wasChanged('product_id') && $line->getOriginal('product_id') !== null) {
                Lot::refreshProductStock((int) $line->getOriginal('product_id'));
            }

            if ($line->wasChanged('purchase_line_id') && $line->getOriginal('purchase_line_id') !== null) {
                PurchaseLine::query()
                    ->find($line->getOriginal('purchase_line_id'))
                    ?->refreshPendingQuantity();
            }
        });

        static::deleted(function (LotLine $line): void {
            Lot::refreshProductStock((int) $line->product_id);
            $line->purchaseLine?->refreshPendingQuantity();
            $line->lot?->refreshTotalsFromLines();
        });
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function purchaseLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseLine::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(): string
    {
        $lotNumber = $this->lot?->lot_number ?? 'Lote #'.$this->lot_id;
        $size = $this->purchaseLine?->size?->name;
        $sizeText = $size !== null ? " · talla: {$size}" : '';

        return "{$lotNumber} · {$this->product?->name}{$sizeText} · disponible: {$this->quantity_available}";
    }
}
