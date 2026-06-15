<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasUlids;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_base',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_base' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function label(): string
    {
        return "{$this->name} ({$this->code})";
    }

    public function isBase(): bool
    {
        return $this->is_base;
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
            ->mapWithKeys(fn (self $currency): array => [$currency->id => $currency->label()])
            ->all();
    }

    public static function base(): self
    {
        return static::query()->where('is_base', true)->first()
            ?? static::query()->where('code', 'PEN')->first()
            ?? static::query()->firstOrFail();
    }

    public static function resolve(self|\App\Enums\Currency|string|null $currency): self
    {
        if ($currency instanceof self) {
            return $currency;
        }

        if ($currency instanceof \App\Enums\Currency) {
            $currency = $currency->value;
        }

        if (filled($currency)) {
            $resolved = static::query()->whereKey((string) $currency)->first()
                ?? static::query()->where('code', (string) $currency)->first();

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return static::base();
    }

    public static function symbolFromFormState(?string $currencyId): string
    {
        return static::resolve($currencyId)->symbol;
    }
}
