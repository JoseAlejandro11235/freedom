<?php

namespace App\Filament\Resources\Sizes;

use App\Filament\Concerns\AuthorizesSizeResources;
use App\Filament\Resources\Sizes\Pages\CreateSize;
use App\Filament\Resources\Sizes\Pages\EditSize;
use App\Filament\Resources\Sizes\Pages\ListSizes;
use App\Filament\Resources\Sizes\Schemas\SizeForm;
use App\Filament\Resources\Sizes\Tables\SizesTable;
use App\Models\Size;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SizeResource extends Resource
{
    use AuthorizesSizeResources;

    protected static ?string $model = Size::class;

    protected static ?string $navigationLabel = 'Tallas';

    protected static ?string $modelLabel = 'talla';

    protected static ?string $pluralModelLabel = 'tallas';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function form(Schema $schema): Schema
    {
        return SizeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SizesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSizes::route('/'),
            'create' => CreateSize::route('/create'),
            'edit' => EditSize::route('/{record}/edit'),
        ];
    }
}
