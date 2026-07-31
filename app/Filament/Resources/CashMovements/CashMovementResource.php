<?php

namespace App\Filament\Resources\CashMovements;

use App\Filament\Concerns\AuthorizesAdminResources;
use App\Filament\Resources\CashMovements\Pages\CreateCashMovement;
use App\Filament\Resources\CashMovements\Pages\EditCashMovement;
use App\Filament\Resources\CashMovements\Pages\ListCashMovements;
use App\Filament\Resources\CashMovements\Schemas\CashMovementForm;
use App\Filament\Resources\CashMovements\Tables\CashMovementsTable;
use App\Models\CashMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashMovementResource extends Resource
{
    use AuthorizesAdminResources;

    protected static ?string $model = CashMovement::class;

    protected static ?string $navigationLabel = 'Flujo de caja';

    protected static ?string $modelLabel = 'movimiento';

    protected static ?string $pluralModelLabel = 'movimientos de caja';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Caja';

    protected static ?int $navigationSort = 1;

    protected static function permission(): string
    {
        return 'manage-products';
    }

    public static function form(Schema $schema): Schema
    {
        return CashMovementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashMovementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashMovements::route('/'),
            'create' => CreateCashMovement::route('/create'),
            'edit' => EditCashMovement::route('/{record}/edit'),
        ];
    }

    public static function canEdit($record): bool
    {
        return parent::canEdit($record) && $record->isManual();
    }

    public static function canDelete($record): bool
    {
        return parent::canDelete($record) && $record->isManual();
    }
}
