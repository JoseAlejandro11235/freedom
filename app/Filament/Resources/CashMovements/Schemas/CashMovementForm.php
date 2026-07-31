<?php

namespace App\Filament\Resources\CashMovements\Schemas;

use App\Enums\CashMovementType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movimiento de caja')
                    ->description('Registra un ingreso o egreso manual. Las ventas, compras y pedidos se registran automáticamente al confirmarse.')
                    ->schema([
                        Select::make('type')
                            ->label('Tipo')
                            ->options(CashMovementType::options())
                            ->required()
                            ->native(false),
                        TextInput::make('amount')
                            ->label('Monto')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('S/')
                            ->step(0.01),
                        DateTimePicker::make('occurred_at')
                            ->label('Fecha')
                            ->required()
                            ->seconds(false)
                            ->default(now()),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
