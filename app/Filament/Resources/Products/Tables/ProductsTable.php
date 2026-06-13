<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\HomepageSection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('Imagen')
                    ->state(fn ($record): ?string => $record->imageUrl())
                    ->checkFileExistence(false),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('brand.name')->label('Marca')->sortable()->searchable(),
                TextColumn::make('selling_price')
                    ->label('Precio de venta')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => match (true) {
                        ! $record->track_inventory => 'gray',
                        $record->stock_quantity <= 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state, $record): string => $record->track_inventory
                        ? (string) $state
                        : '—'),
                TextColumn::make('homepage_section')
                    ->label('Sección')
                    ->badge()
                    ->formatStateUsing(fn (?HomepageSection $state): string => $state?->label() ?? '—'),
                IconColumn::make('is_published')->boolean()->label('Publicado'),
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('homepage_section')
                    ->options(HomepageSection::options()),
                TernaryFilter::make('is_published')->label('Publicado'),
                TernaryFilter::make('track_inventory')
                    ->label('Controla inventario'),
                SelectFilter::make('stock_status')
                    ->label('Estado de stock')
                    ->options([
                        'in_stock' => 'Con stock',
                        'low_stock' => 'Stock bajo',
                        'out_of_stock' => 'Agotado',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'in_stock' => $query->inStock()->where('track_inventory', true),
                            'low_stock' => $query->lowStock(),
                            'out_of_stock' => $query
                                ->where('track_inventory', true)
                                ->where('stock_quantity', '<=', 0),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
