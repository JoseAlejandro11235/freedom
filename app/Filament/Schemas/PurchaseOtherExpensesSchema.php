<?php

namespace App\Filament\Schemas;

use App\Models\Currency;
use App\Models\PurchaseExpenseConcept;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class PurchaseOtherExpensesSchema
{
    /**
     * @return array<int, Repeater>
     */
    public static function repeater(): array
    {
        return [
            Repeater::make('other_expenses')
                ->label('Conceptos')
                ->columnSpanFull()
                ->live()
                ->schema([
                    Select::make('purchase_expense_concept_id')
                        ->label('Concepto')
                        ->options(fn (): array => PurchaseExpenseConcept::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->placeholder('Selecciona un concepto')
                        ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft()),
                    TextInput::make('amount')
                        ->label('Importe')
                        ->numeric()
                        ->prefix(fn (Get $get): string => Currency::symbolFromFormState(
                            $get('currency_id') ?? $get('../../currency_id'),
                        ))
                        ->minValue(0)
                        ->required()
                        ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft()),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->addActionLabel('Añadir gasto')
                ->deletable(fn (?object $record): bool => $record === null || $record->isDraft())
                ->addable(fn (?object $record): bool => $record === null || $record->isDraft())
                ->reorderable(fn (?object $record): bool => $record === null || $record->isDraft()),
        ];
    }
}
