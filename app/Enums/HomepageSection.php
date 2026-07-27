<?php

namespace App\Enums;

enum HomepageSection: string
{
    case None = 'none';
    case Featured = 'featured';
    case NewArrivals = 'new_arrivals';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Catálogo general',
            self::Featured => 'Inicio — Destacados',
            self::NewArrivals => 'Inicio — Novedades',
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
