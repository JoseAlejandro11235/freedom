<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPreview extends Model
{
    use HasUlids;

    protected $table = 'products_preview';

    protected $fillable = [
        'user_id',
        'row_number',
        'code',
        'name',
        'validation_error',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return blank($this->validation_error);
    }

    public function hasExistingProductConflict(): bool
    {
        return $this->validation_error === ProductImportService::ERROR_CODE_EXISTS_IN_PRODUCTS;
    }

    public function canBeRemovedFromPreview(): bool
    {
        return filled($this->validation_error);
    }

    /**
     * @param  Builder<ProductPreview>  $query
     * @return Builder<ProductPreview>
     */
    public function scopeForUser(Builder $query, User|string $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }
}
