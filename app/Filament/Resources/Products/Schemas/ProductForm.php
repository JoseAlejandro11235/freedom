<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\HomepageSection;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $disk = config('freedom.catalog_disk');

        return $schema
            ->columns(1)
            ->components([
                Grid::make(2)->schema([
                    Section::make('Datos del producto')
                        ->columnSpan(1)
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('brand_id')
                                    ->label('Marca')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        return Brand::query()->create($data)->id;
                                    }),
                                Select::make('category_id')
                                    ->label('Categoría')
                                    ->relationship(
                                        name: 'category',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->with('father')->orderBy('name'),
                                    )
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Category $record): string => $record->father
                                            ? "{$record->father->name} → {$record->name}"
                                            : $record->name,
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),
                            TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->hiddenOn('create')
                                ->helperText('Déjalo vacío para generarlo automáticamente.'),
                        ]),
                    Section::make('Precio y visibilidad')
                        ->columnSpan(1)
                        ->schema([
                            TextInput::make('selling_price')
                                ->label('Precio de venta')
                                ->required()
                                ->numeric()
                                ->prefix('S/')
                                ->minValue(0),
                            Toggle::make('is_published')
                                ->label('Publicado en la tienda')
                                ->default(true),
                            Select::make('homepage_section')
                                ->label('Sección en inicio')
                                ->options(HomepageSection::options())
                                ->default(HomepageSection::None->value)
                                ->required(),
                            TextInput::make('sort_order')
                                ->label('Orden')
                                ->numeric()
                                ->default(fn (): int => Product::query()->count() + 1)
                                ->minValue(0),
                        ]),
                    Section::make('Inventario')
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('stock_quantity')
                                ->label('Stock actual')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->helperText('El stock se actualiza al confirmar compras o ventas en Inventario.'),
                        ]),
                    Section::make('Imágenes del producto')
                        ->columnSpanFull()
                        ->schema([
                            FileUpload::make('product_images')
                                ->label('Imágenes')
                                ->disk($disk)
                                ->directory('products')
                                ->visibility('public')
                                ->image()
                                ->multiple()
                                ->reorderable()
                                ->fetchFileInformation(false)
                                ->preventFilePathTampering(
                                    allowFilePathUsing: function (string $file, FileUpload $component): bool {
                                        $record = $component->getRecord();

                                        if (! $record instanceof Model) {
                                            return false;
                                        }

                                        return $record->images()->where('path', $file)->exists();
                                    },
                                )
                                ->maxSize(5120)
                                ->helperText('La primera imagen se usa en la tienda. Arrastra para reordenar.'),
                            Select::make('image_fit')
                                ->label('Ajuste de imagen')
                                ->options([
                                    'contain' => 'Contener',
                                    'cover' => 'Cubrir',
                                ])
                                ->default('contain')
                                ->required(),
                        ]),
                ]),
            ]);
    }
}
