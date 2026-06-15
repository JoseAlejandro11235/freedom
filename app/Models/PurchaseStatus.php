<?php

namespace App\Models;

use App\Enums\StockDocumentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseStatus extends Model
{
    use HasUlids;

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
     * @return array<string, string>
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

    public static function resolve(self|StockDocumentStatus|string|null $status): self
    {
        if ($status instanceof self) {
            return $status;
        }

        if ($status instanceof StockDocumentStatus) {
            $status = match ($status) {
                StockDocumentStatus::Draft => self::DRAFT,
                StockDocumentStatus::Confirmed => self::APPROVED,
                StockDocumentStatus::Cancelled => self::DRAFT,
            };
        }

        if (filled($status)) {
            $resolved = static::query()->whereKey((string) $status)->first();

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
