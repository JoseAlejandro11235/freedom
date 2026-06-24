<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseLineImport extends Model
{
    use HasUlids;

    protected $table = 'purchase_lineas_imports';

    protected $fillable = [
        'user_id',
        'row_number',
        'code',
        'description',
        'quantity',
        'unit_cost',
        'product_id',
        'product_name',
        'is_duplicate',
        'validation_error',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'is_duplicate' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isValid(): bool
    {
        return blank($this->validation_error);
    }

    public function isImportable(): bool
    {
        return $this->isValid() && filled($this->product_id);
    }

    /**
     * @param  Builder<PurchaseLineImport>  $query
     * @return Builder<PurchaseLineImport>
     */
    public function scopeForUser(Builder $query, User|string $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }
}
