<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Concerns\AuthorizesAdminResources;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    use AuthorizesAdminResources;

    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Pedidos web';

    protected static ?string $modelLabel = 'pedido';

    protected static ?string $pluralModelLabel = 'pedidos';

    protected static ?string $recordTitleAttribute = 'number';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Tienda';

    protected static ?int $navigationSort = 1;

    protected static function permission(): string
    {
        return 'manage-products';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pedido')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('number')->label('Nº pedido'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                            ->color(fn (OrderStatus $state): string => $state->color()),
                        TextEntry::make('total')
                            ->label('Total')
                            ->money(fn (Order $record): string => $record->currency ?: 'PEN'),
                        TextEntry::make('payment_provider')
                            ->label('Pasarela')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'fake' => 'Prueba local',
                                'culqi' => 'Culqi',
                                'mercadopago' => 'Mercado Pago',
                                default => $state ?: '—',
                            }),
                        TextEntry::make('payment_reference')
                            ->label('Referencia de pago')
                            ->placeholder('—')
                            ->copyable(),
                        TextEntry::make('paid_at')
                            ->label('Pagado el')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime(),
                    ]),
                Section::make('Cliente')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer_name')->label('Nombre'),
                        TextEntry::make('customer_email')->label('Correo'),
                        TextEntry::make('customer_phone')->label('Teléfono')->placeholder('—'),
                        TextEntry::make('shipping_city')->label('Ciudad'),
                        TextEntry::make('shipping_district')->label('Distrito')->placeholder('—'),
                        TextEntry::make('shipping_address')
                            ->label('Dirección')
                            ->columnSpanFull(),
                        TextEntry::make('shipping_notes')
                            ->label('Notas')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Productos')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('product_name')->label('Producto'),
                                TextEntry::make('brand_name')->label('Marca')->placeholder('—'),
                                TextEntry::make('product_code')->label('Código')->placeholder('—'),
                                TextEntry::make('quantity')->label('Cantidad'),
                                TextEntry::make('unit_price')->label('Precio unit.')->money('PEN'),
                                TextEntry::make('line_total')->label('Subtotal')->money('PEN'),
                            ])
                            ->columns(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny() && ! $record->isPaid();
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }
}
