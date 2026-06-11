<?php

namespace App\Filament\Resources\Purchases;

use App\Filament\Concerns\AuthorizesAdminResources;
use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Resources\Purchases\Pages\ViewPurchase;
use App\Filament\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Resources\Purchases\Tables\PurchasesTable;
use App\Models\Purchase;
use App\Support\PurchaseTotals;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    use AuthorizesAdminResources;

    protected static ?string $model = Purchase::class;

    protected static ?string $navigationLabel = 'Compras';

    protected static ?string $modelLabel = 'compra';

    protected static ?string $pluralModelLabel = 'compras';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compra')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('purchase_id')->label('Nº compra'),
                        TextEntry::make('provider_display')
                            ->label('Proveedor')
                            ->state(fn (Purchase $record): string => $record->provider?->displayName() ?? '—'),
                        TextEntry::make('currency.name')
                            ->label('Moneda')
                            ->state(fn (Purchase $record): string => $record->currency?->label() ?? '—'),
                        TextEntry::make('exchange_rate')
                            ->label('Tipo de cambio')
                            ->formatStateUsing(fn (Purchase $record): string => ($record->currency?->isBase() ?? true)
                                ? '1.0000 (soles)'
                                : '1 '.$record->currency?->code.' = '.PurchaseTotals::formatExchangeRate($record->exchange_rate).' PEN'),
                        TextEntry::make('status.name')
                            ->label('Estado')
                            ->badge()
                            ->state(fn (Purchase $record): string => $record->status?->label() ?? '—')
                            ->color(fn (Purchase $record): string => $record->status?->color() ?? 'gray'),
                        TextEntry::make('user.name')->label('Usuario')->placeholder('—'),
                        TextEntry::make('created_at')->label('Creado')->dateTime(),
                        TextEntry::make('note')->label('Nota')->columnSpanFull()->placeholder('—'),
                    ]),
                Section::make('Líneas')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label('')
                            ->schema([
                                TextEntry::make('product.name')->label('Producto'),
                                TextEntry::make('size.name')->label('Talla')->placeholder('—'),
                                TextEntry::make('quantity')->label('Cantidad'),
                                TextEntry::make('unit_cost')
                                    ->label('Costo unitario')
                                    ->formatStateUsing(fn ($state, $record): string => PurchaseTotals::formatMoney(
                                        (float) ($state ?? 0),
                                        $record->purchase->currency,
                                    )),
                                TextEntry::make('pending_quantity')
                                    ->label('Pendiente')
                                    ->state(fn ($record): string => (string) $record->pending_quantity),
                            ])
                            ->columns(5),
                    ]),
                Section::make('Otros gastos')
                    ->schema([
                        RepeatableEntry::make('expenses')
                            ->label('')
                            ->schema([
                                TextEntry::make('concept_name')
                                    ->label('Concepto')
                                    ->state(fn ($record): string => $record->concept?->name ?? $record->description),
                                TextEntry::make('amount')
                                    ->label('Importe')
                                    ->formatStateUsing(fn ($state, $record): string => PurchaseTotals::formatMoney(
                                        (float) ($state ?? 0),
                                        $record->purchase->currency,
                                    )),
                            ])
                            ->columns(2)
                            ->placeholder('Sin otros gastos'),
                    ]),
                Section::make('Total')
                    ->schema([
                        TextEntry::make('lines_subtotal')
                            ->label('Subtotal productos')
                            ->state(fn (Purchase $record): string => PurchaseTotals::formatMoney(
                                $record->linesSubtotal(),
                                $record->currency,
                            )),
                        TextEntry::make('expenses_subtotal')
                            ->label('Subtotal otros gastos')
                            ->state(fn (Purchase $record): string => PurchaseTotals::formatMoney(
                                $record->expensesSubtotal(),
                                $record->currency,
                            )),
                        TextEntry::make('total')
                            ->label('Total')
                            ->state(fn (Purchase $record): string => PurchaseTotals::formattedTotalForPurchase($record))
                            ->weight('bold'),
                        TextEntry::make('total_pen')
                            ->label('Total en soles (PEN)')
                            ->state(fn (Purchase $record): string => PurchaseTotals::formattedTotalInPenForPurchase($record))
                            ->visible(fn (Purchase $record): bool => ! ($record->currency?->isBase() ?? true))
                            ->weight('bold'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchases::route('/'),
            'create' => CreatePurchase::route('/create'),
            'view' => ViewPurchase::route('/{record}'),
            'edit' => EditPurchase::route('/{record}/edit'),
        ];
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny() && $record->isDraft();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny() && $record->isDraft();
    }
}
