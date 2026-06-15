<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'purchase_record_id',
        'product_id',
        'size_id',
        'quantity',
        'pending_quantity',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'pending_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (PurchaseLine $line): void {
            $line->refreshPendingQuantity();
        });

        static::updated(function (PurchaseLine $line): void {
            if ($line->wasChanged('quantity')) {
                $line->refreshPendingQuantity();
            }
        });
    }

    public function refreshPendingQuantity(): void
    {
        $receivedQuantity = (int) $this->lotLines()->sum('quantity_received');

        $this->forceFill([
            'pending_quantity' => max(0, (int) $this->quantity - $receivedQuantity),
        ])->saveQuietly();
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_record_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function lot(): HasOne
    {
        return $this->hasOne(Lot::class);
    }

    public function lotLines(): HasMany
    {
        return $this->hasMany(LotLine::class);
    }
}
