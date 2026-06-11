<?php

namespace App\Enums;

enum HomepageSection: string
{
    case None = 'none';
    case FlashSale = 'flash_sale';
    case ForHim = 'for_him';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Catálogo general',
            self::FlashSale => 'Inicio — Ofertas 24 h',
            self::ForHim => 'Inicio — Para él',
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
}
