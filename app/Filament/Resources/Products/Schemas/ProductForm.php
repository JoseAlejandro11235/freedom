<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\HomepageSection;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                                    ->nullable()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        return Brand::query()->create($data)->id;
                                    }),
                                Select::make('categories')
                                    ->label('Categorías')
                                    ->relationship(
                                        name: 'categories',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->with('father')->orderBy('name'),
                                    )
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Category $record): string => $record->father
                                            ? "{$record->father->name} → {$record->name}"
                                            : $record->name,
                                    )
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),
                            TextInput::make('code')
                                ->label('Código')
                                ->maxLength(64)
                                ->unique(ignoreRecord: true)
                                ->nullable(),
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
                            Repeater::make('images')
                                ->label('Imágenes')
                                ->relationship(
                                    modifyQueryUsing: fn ($query) => $query->orderBy('sort_order'),
                                )
                                ->schema([
                                    FileUpload::make('path')
                                        ->label('Imagen')
                                        ->disk($disk)
                                        ->directory('products')
                                        ->visibility('public')
                                        ->image()
                                        ->imagePreviewHeight('200')
                                        ->acceptedFileTypes([
                                            'image/jpeg',
                                            'image/jpg',
                                            'image/png',
                                            'image/webp',
                                            'image/gif',
                                        ])
                                        ->required()
                                        // Avoid S3 exists()/mimeType() round-trips; set type from extension so
                                        // FilePond can preview .jpg/.jpeg correctly.
                                        ->fetchFileInformation(false)
                                        ->getUploadedFileUsing(function (BaseFileUpload $component, string $file, string | array | null $storedFileNames): ?array {
                                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                            $type = match ($extension) {
                                                'jpg', 'jpeg' => 'image/jpeg',
                                                'png' => 'image/png',
                                                'webp' => 'image/webp',
                                                'gif' => 'image/gif',
                                                default => 'image/jpeg',
                                            };

                                            return [
                                                'name' => is_array($storedFileNames)
                                                    ? ($storedFileNames[$file] ?? basename($file))
                                                    : ($storedFileNames ?? basename($file)),
                                                'size' => 0,
                                                'type' => $type,
                                                'url' => $component->getDisk()->url($file),
                                            ];
                                        })
                                        ->preventFilePathTampering(
                                            allowFilePathUsing: function (string $file): bool {
                                                return str_starts_with($file, 'products/');
                                            },
                                        )
                                        ->maxSize(10240),
                                ])
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->itemNumbers()
                                ->defaultItems(0)
                                ->addActionLabel('Añadir imagen')
                                ->helperText('La primera imagen se usa en la tienda. Usa las flechas o arrastra para cambiar el orden.'),
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
