<?php

namespace App\Enums;

enum SellingLineStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Assigned => 'Asignado a lote',
            self::Confirmed => 'Confirmado',
            self::Cancelled => 'Cancelado',
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
