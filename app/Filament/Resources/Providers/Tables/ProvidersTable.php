<?php

namespace App\Filament\Resources\Providers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('persona.first_name')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('persona.last_name')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('persona.razon_social')
                    ->label('Razón social')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('persona.document_number')
                    ->label('Documento')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('persona.phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('persona.email')
                    ->label('Correo')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
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
