<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\BaseFileUpload;
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
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/jpg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                            ])
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
                                allowFilePathUsing: fn (string $file): bool => str_starts_with($file, 'sections/'),
                            )
                            ->maxSize(10240),
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
