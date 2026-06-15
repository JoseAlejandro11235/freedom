<?php

namespace App\Models;

use App\Enums\StockDocumentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Selling extends Model
{
    use HasUlids;

    protected $fillable = [
        'selling_id',
        'customer_id',
        'status',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockDocumentStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Selling $selling) {
            if (! isset($selling->status)) {
                $selling->status = StockDocumentStatus::Draft;
            }
        });
    }

    public function isDraft(): bool
    {
        return $this->status === StockDocumentStatus::Draft;
    }

    public function isConfirmed(): bool
    {
        return $this->status === StockDocumentStatus::Confirmed;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SellingLine::class, 'selling_record_id');
    }
}
