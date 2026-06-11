<?php

namespace App\Filament\Resources\PurchaseExpenseConcepts;

use App\Filament\Concerns\AuthorizesAdminResources;
use App\Filament\Resources\PurchaseExpenseConcepts\Pages\CreatePurchaseExpenseConcept;
use App\Filament\Resources\PurchaseExpenseConcepts\Pages\EditPurchaseExpenseConcept;
use App\Filament\Resources\PurchaseExpenseConcepts\Pages\ListPurchaseExpenseConcepts;
use App\Filament\Resources\PurchaseExpenseConcepts\Schemas\PurchaseExpenseConceptForm;
use App\Filament\Resources\PurchaseExpenseConcepts\Tables\PurchaseExpenseConceptsTable;
use App\Models\PurchaseExpenseConcept;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseExpenseConceptResource extends Resource
{
    use AuthorizesAdminResources;

    protected static ?string $model = PurchaseExpenseConcept::class;

    protected static ?string $navigationLabel = 'Conceptos de gastos';

    protected static ?string $modelLabel = 'concepto de gasto';

    protected static ?string $pluralModelLabel = 'conceptos de gastos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 13;

    protected static function permission(): string
    {
        return 'manage-purchase-expense-concepts';
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseExpenseConceptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseExpenseConceptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseExpenseConcepts::route('/'),
            'create' => CreatePurchaseExpenseConcept::route('/create'),
            'edit' => EditPurchaseExpenseConcept::route('/{record}/edit'),
        ];
    }
}
