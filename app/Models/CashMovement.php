<?php

namespace App\Models;

use App\Enums\CashMovementSource;
use App\Enums\CashMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashMovement extends Model
{
    use HasUlids;

    protected $fillable = [
        'type',
        'amount',
        'currency',
        'description',
        'occurred_at',
        'source',
        'sourceable_type',
        'sourceable_id',
        'user_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'source' => CashMovementSource::class,
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isManual(): bool
    {
        return $this->source === CashMovementSource::Manual;
    }

    public function isIncome(): bool
    {
        return $this->type === CashMovementType::Income;
    }

    public function signedAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->isIncome() ? $amount : -$amount;
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeIncomes(Builder $query): Builder
    {
        return $query->where('type', CashMovementType::Income);
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', CashMovementType::Expense);
    }
}
