<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Filament\Schemas\PurchaseOtherExpensesSchema;
use App\Filament\Schemas\StockDocumentLinesSchema;
use App\Models\Currency;
use App\Models\Provider;
use App\Services\StockDocumentService;
use App\Support\PurchaseTotals;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Compra')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('purchase_id')
                            ->label('Nº de compra')
                            ->default(fn (): string => app(StockDocumentService::class)->generatePurchaseId())
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->dehydrated()
                            ->columnSpan(1),
                        Select::make('provider_id')
                            ->label('Proveedor')
                            ->options(fn (): array => Provider::query()
                                ->with('persona')
                                ->get()
                                ->sortBy(fn (Provider $provider): string => $provider->displayName())
                                ->mapWithKeys(fn (Provider $provider): array => [$provider->id => $provider->displayName()])
                                ->all())
                            ->searchable()
                            ->native(false)
                            ->placeholder('Selecciona un proveedor')
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->columnSpan(1),
                        Select::make('currency_id')
                            ->label('Moneda')
                            ->options(Currency::options())
                            ->default(fn (): string => Currency::base()->id)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (Currency::resolve($state)->isBase()) {
                                    $set('exchange_rate', 1);
                                }
                            })
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->columnSpan(1),
                        TextInput::make('exchange_rate')
                            ->label('Tipo de cambio')
                            ->numeric()
                            ->minValue(0.0001)
                            ->step(0.0001)
                            ->default(1)
                            ->required()
                            ->live()
                            ->suffix('PEN')
                            ->helperText(fn (Get $get): string => '1 '.Currency::resolve($get('currency_id'))->code.' = X soles (PEN)')
                            ->disabled(fn (Get $get, ?object $record): bool => (
                                Currency::resolve($get('currency_id'))->isBase()
                                || ($record !== null && ! $record->isDraft())
                            ))
                            ->dehydrated()
                            ->columnSpan(1),
                        TextInput::make('status_display')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state, ?object $record): string => $record?->status?->label() ?? 'Borrador')
                            ->visible(fn (?object $record): bool => $record !== null)
                            ->columnSpan(1),
                        Textarea::make('note')
                            ->label('Nota')
                            ->rows(2)
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->columnSpanFull(),
                    ]),
                Section::make('Líneas')
                    ->columnSpanFull()
                    ->schema(StockDocumentLinesSchema::repeater(
                        'unit_cost',
                        'Costo unitario',
                        live: true,
                        currencyField: 'currency_id',
                    )),
                Section::make('Otros gastos')
                    ->columnSpanFull()
                    ->schema(PurchaseOtherExpensesSchema::repeater()),
                Section::make('Total')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('subtotal_lines')
                            ->label('Subtotal productos')
                            ->content(fn (Get $get): string => PurchaseTotals::formatMoney(
                                PurchaseTotals::linesSubtotal($get('lines')),
                                $get('currency_id'),
                            )),
                        Placeholder::make('subtotal_expenses')
                            ->label('Subtotal otros gastos')
                            ->content(fn (Get $get): string => PurchaseTotals::formatMoney(
                                PurchaseTotals::expensesSubtotal($get('other_expenses')),
                                $get('currency_id'),
                            )),
                        Placeholder::make('purchase_total')
                            ->label('Total')
                            ->content(fn (Get $get): string => PurchaseTotals::formattedTotalFromGet($get))
                            ->extraAttributes(['class' => 'text-lg font-semibold']),
                        Placeholder::make('purchase_total_pen')
                            ->label('Total en soles (PEN)')
                            ->content(fn (Get $get): string => PurchaseTotals::formattedTotalInPenFromGet($get))
                            ->visible(fn (Get $get): bool => ! Currency::resolve($get('currency_id'))->isBase())
                            ->extraAttributes(['class' => 'text-lg font-semibold']),
                    ]),
            ]);
    }
}
