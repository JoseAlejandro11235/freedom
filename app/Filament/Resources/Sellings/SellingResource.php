<?php

namespace App\Filament\Resources\Sellings;

use App\Filament\Concerns\AuthorizesAdminResources;
use App\Filament\Resources\Sellings\Pages\CreateSelling;
use App\Filament\Resources\Sellings\Pages\EditSelling;
use App\Filament\Resources\Sellings\Pages\ListSellings;
use App\Filament\Resources\Sellings\Pages\ViewSelling;
use App\Filament\Resources\Sellings\Schemas\SellingForm;
use App\Filament\Resources\Sellings\Tables\SellingsTable;
use App\Enums\StockDocumentStatus;
use App\Enums\SellingLineStatus;
use App\Models\Selling;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SellingResource extends Resource
{
    use AuthorizesAdminResources;

    protected static ?string $model = Selling::class;

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $modelLabel = 'venta';

    protected static ?string $pluralModelLabel = 'ventas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return SellingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Venta')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('selling_id')->label('Nº venta'),
                        TextEntry::make('customer_display')
                            ->label('Cliente')
                            ->state(fn (Selling $record): string => $record->customer?->displayName() ?? '—'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (StockDocumentStatus $state): string => $state->label())
                            ->color(fn (StockDocumentStatus $state): string => $state->color()),
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
                                TextEntry::make('lotLine.lot.lot_number')->label('Lote')->placeholder('—'),
                                TextEntry::make('state')
                                    ->label('Estado línea')
                                    ->formatStateUsing(fn (SellingLineStatus $state): string => $state->label()),
                                TextEntry::make('quantity')->label('Cantidad'),
                                TextEntry::make('unit_price')->label('Precio unitario')->money('PEN'),
                            ])
                            ->columns(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return SellingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSellings::route('/'),
            'create' => CreateSelling::route('/create'),
            'view' => ViewSelling::route('/{record}'),
            'edit' => EditSelling::route('/{record}/edit'),
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
