<?php

namespace App\Filament\Resources\Sellings\Schemas;

use App\Filament\Schemas\StockDocumentLinesSchema;
use App\Models\Customer;
use App\Services\StockDocumentService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SellingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Venta')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('selling_id')
                            ->label('Nº de venta')
                            ->default(fn (): string => app(StockDocumentService::class)->generateSellingId())
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->dehydrated(),
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->options(fn (): array => Customer::query()
                                ->with('persona')
                                ->get()
                                ->sortBy(fn (Customer $customer): string => $customer->displayName())
                                ->mapWithKeys(fn (Customer $customer): array => [$customer->id => $customer->displayName()])
                                ->all())
                            ->searchable()
                            ->native(false)
                            ->placeholder('Selecciona un cliente')
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft()),
                        TextInput::make('status_display')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state, ?object $record): string => $record?->status?->label() ?? 'Borrador')
                            ->visible(fn (?object $record): bool => $record !== null),
                        Textarea::make('note')
                            ->label('Nota')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft()),
                    ]),
                Section::make('Líneas')
                    ->columnSpanFull()
                    ->schema(StockDocumentLinesSchema::repeater(
                        'unit_price',
                        'Precio unitario',
                        withLotSelection: true,
                    )),
            ]);
    }
}
