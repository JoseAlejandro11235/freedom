<?php

namespace App\Models;

use App\Support\PurchaseTotals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_id',
        'provider_id',
        'status',
        'purchase_status_id',
        'note',
        'currency',
        'currency_id',
        'exchange_rate',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Purchase $purchase) {
            if (! isset($purchase->purchase_status_id)) {
                $purchase->purchase_status_id = PurchaseStatus::findByCode(PurchaseStatus::DRAFT)->id;
            }

            if (! isset($purchase->currency_id)) {
                $purchase->currency_id = Currency::base()->id;
            }

            if (! isset($purchase->exchange_rate)) {
                $purchase->exchange_rate = 1;
            }
        });
    }

    public function isDraft(): bool
    {
        return $this->status?->isCode(PurchaseStatus::DRAFT) ?? false;
    }

    public function isConfirmed(): bool
    {
        return $this->isApproved();
    }

    public function isApproved(): bool
    {
        return $this->status?->isCode(PurchaseStatus::APPROVED) ?? false;
    }

    public function isPaid(): bool
    {
        return $this->status?->isCode(PurchaseStatus::PAID) ?? false;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(PurchaseStatus::class, 'purchase_status_id');
    }

    public function setStatusAttribute(PurchaseStatus|\App\Enums\StockDocumentStatus|string|int|null $status): void
    {
        $this->attributes['purchase_status_id'] = PurchaseStatus::resolve($status)->id;
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function setCurrencyAttribute(Currency|\App\Enums\Currency|string|int|null $currency): void
    {
        $this->attributes['currency_id'] = Currency::resolve($currency)->id;
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class, 'purchase_record_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(PurchaseExpense::class, 'purchase_record_id')->orderBy('sort_order');
    }

    public function linesSubtotal(): float
    {
        return (float) $this->lines->sum(
            fn (PurchaseLine $line): float => (float) $line->quantity * (float) ($line->unit_cost ?? 0),
        );
    }

    public function expensesSubtotal(): float
    {
        return (float) $this->expenses->sum('amount');
    }

    public function total(): float
    {
        return $this->linesSubtotal() + $this->expensesSubtotal();
    }

    public function totalInPen(): float
    {
        return PurchaseTotals::totalInPen(
            $this->total(),
            $this->currency,
            (float) $this->exchange_rate,
        );
    }
}
