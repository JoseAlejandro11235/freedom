<?php

namespace App\Enums;

enum Currency: string
{
    case Pen = 'PEN';
    case Usd = 'USD';
    case Eur = 'EUR';

    public function label(): string
    {
        return match ($this) {
            self::Pen => 'Soles (PEN)',
            self::Usd => 'Dólares (USD)',
            self::Eur => 'Euros (EUR)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Pen => 'S/',
            self::Usd => '$',
            self::Eur => '€',
        };
    }

  /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    public static function resolve(Currency|string|null $currency): self
    {
        if ($currency instanceof self) {
            return $currency;
        }

        return self::tryFrom((string) $currency) ?? self::Pen;
    }

    public static function symbolFromFormState(?string $currency): string
    {
        return self::resolve($currency)->symbol();
    }

    public static function base(): self
    {
        return self::Pen;
    }

    public function isBase(): bool
    {
        return $this === self::base();
    }
}
