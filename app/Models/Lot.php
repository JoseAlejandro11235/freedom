<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $fillable = [
        'lot_number',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Lot $lot): void {
            $lot->lines->each(
                fn (LotLine $line): mixed => $line->purchaseLine?->refreshPendingQuantity(),
            );

        });

        static::deleting(function (Lot $lot): void {
            $lot->lines()->get()->each->delete();
        });

        static::deleted(function (Lot $lot): void {
            $lot->lines->each(
                fn (LotLine $line): mixed => $line->purchaseLine?->refreshPendingQuantity(),
            );
        });
    }

    public static function refreshProductStock(int $productId): void
    {
        $quantity = LotLine::query()
            ->where('product_id', $productId)
            ->sum('quantity_available');

        Product::query()
            ->whereKey($productId)
            ->update(['stock_quantity' => (int) $quantity]);
    }

    public function refreshTotalsFromLines(): void
    {
        $this->touch();
    }

    public function sellingLines(): HasMany
    {
        return $this->hasMany(SellingLine::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LotLine::class);
    }

    public function displayName(): string
    {
        $number = $this->lot_number ?? 'Lote #'.$this->id;
        $available = (int) $this->lines()->sum('quantity_available');

        return "{$number} · disponible: {$available}";
    }
}
