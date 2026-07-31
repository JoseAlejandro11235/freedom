<?php

namespace App\Enums;

enum CashMovementSource: string
{
    case Manual = 'manual';
    case Selling = 'selling';
    case Purchase = 'purchase';
    case Order = 'order';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Selling => 'Venta',
            self::Purchase => 'Compra',
            self::Order => 'Pedido web',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
