<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseStatus extends Model
{
    public const DRAFT = 'DRAFT';

    public const APPROVED = 'APPROVED';

    public const PAID = 'PAID';

    protected $fillable = [
        'code',
        'name',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function label(): string
    {
        return $this->name;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function isCode(string $code): bool
    {
        return $this->code === $code;
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (self $status): array => [$status->id => $status->label()])
            ->all();
    }

    public static function findByCode(string $code): self
    {
        return static::query()->where('code', strtoupper($code))->firstOrFail();
    }

    public static function resolve(self|\App\Enums\StockDocumentStatus|string|int|null $status): self
    {
        if ($status instanceof self) {
            return $status;
        }

        if ($status instanceof \App\Enums\StockDocumentStatus) {
            $status = match ($status) {
                \App\Enums\StockDocumentStatus::Draft => self::DRAFT,
                \App\Enums\StockDocumentStatus::Confirmed => self::APPROVED,
                \App\Enums\StockDocumentStatus::Cancelled => self::DRAFT,
            };
        }

        if (is_numeric($status)) {
            $resolved = static::query()->find((int) $status);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        $code = match (strtolower((string) $status)) {
            'confirmed', 'approved' => self::APPROVED,
            'paid' => self::PAID,
            default => self::DRAFT,
        };

        return static::findByCode($code);
    }
}
