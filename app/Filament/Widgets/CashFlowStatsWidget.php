<?php

namespace App\Filament\Widgets;

use App\Services\CashService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashFlowStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Flujo de caja';

    protected function getStats(): array
    {
        $cash = app(CashService::class);
        $month = $cash->summary(now()->startOfMonth(), now()->endOfMonth());
        $all = $cash->summary();

        return [
            Stat::make('Ingresos del mes', 'S/ '.number_format($month['income'], 2))
                ->description('Movimientos de ingreso este mes')
                ->color('success'),
            Stat::make('Egresos del mes', 'S/ '.number_format($month['expense'], 2))
                ->description('Movimientos de egreso este mes')
                ->color('danger'),
            Stat::make('Saldo del mes', 'S/ '.number_format($month['balance'], 2))
                ->description('Saldo acumulado total: S/ '.number_format($all['balance'], 2))
                ->color($month['balance'] >= 0 ? 'success' : 'danger'),
        ];
    }
}
