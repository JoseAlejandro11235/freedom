<?php

namespace App\Filament\Resources\Lots\Tables;

use App\Models\Lot;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lot_number')
                    ->label('Lote')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase_ids')
                    ->label('Compras')
                    ->state(fn (Lot $record): string => $record->lines()
                        ->with('purchaseLine.purchase')
                        ->get()
                        ->map(fn ($line): ?string => $line->purchaseLine?->purchase?->purchase_id)
                        ->filter()
                        ->unique()
                        ->implode(', '))
                    ->placeholder('—'),
                TextColumn::make('products')
                    ->label('Productos')
                    ->state(fn (Lot $record): string => $record->lines()
                        ->with('product')
                        ->get()
                        ->map(fn ($line): ?string => $line->product?->name)
                        ->filter()
                        ->unique()
                        ->implode(', '))
                    ->placeholder('—'),
                TextColumn::make('quantity_received')
                    ->label('Recibido')
                    ->state(fn (Lot $record): int => (int) $record->lines()->sum('quantity_received')),
                TextColumn::make('quantity_available')
                    ->label('Disponible')
                    ->state(fn (Lot $record): int => (int) $record->lines()->sum('quantity_available')),
                TextColumn::make('received_at')
                    ->label('Recibido el')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('received_at', 'desc')
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
