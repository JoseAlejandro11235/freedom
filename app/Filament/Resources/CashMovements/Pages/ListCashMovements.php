<?php

namespace App\Filament\Resources\CashMovements\Pages;

use App\Filament\Resources\CashMovements\CashMovementResource;
use App\Filament\Widgets\CashFlowStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashMovements extends ListRecords
{
    protected static string $resource = CashMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo movimiento'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            CashFlowStatsWidget::class,
        ];
    }
}
