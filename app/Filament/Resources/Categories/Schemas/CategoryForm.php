<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $disk = config('freedom.catalog_disk');

        return $schema
            ->columns(1)
            ->components([
                Section::make('Categoría')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('category_father_id')
                            ->label('Categoría padre')
                            ->relationship(
                                'father',
                                'name',
                                function (Builder $query, Select $component): Builder {
                                    $record = $component->getRecord();

                                    if ($record instanceof Category && $record->exists) {
                                        $query->whereNotIn('categories.id', array_merge(
                                            [$record->getKey()],
                                            $record->descendantIds(),
                                        ));
                                    }

                                    return $query;
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Vacío = categoría de primer nivel (aparece en la página de inicio).'),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('image_path')
                            ->label('Imagen')
                            ->disk($disk)
                            ->directory('sections')
                            ->visibility('public')
                            ->image()
                            ->fetchFileInformation(false)
                            ->preventFilePathTampering()
                            ->maxSize(5120),
                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Toggle::make('is_published')
                            ->label('Publicada en la tienda')
                            ->default(true),
                    ]),
            ]);
    }
}
