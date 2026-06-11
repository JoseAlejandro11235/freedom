<?php

namespace App\Filament\Resources\Sellings\Tables;

use App\Enums\StockDocumentStatus;
use App\Services\StockDocumentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class SellingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('selling_id')
                    ->label('Nº venta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_display')
                    ->label('Cliente')
                    ->state(fn ($record): string => $record->customer?->displayName() ?? '—')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'customer.persona',
                        fn ($query) => $query
                            ->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"),
                    )),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (StockDocumentStatus $state): string => $state->label())
                    ->color(fn (StockDocumentStatus $state): string => $state->color()),
                TextColumn::make('lines_count')
                    ->counts('lines')
                    ->label('Líneas'),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(StockDocumentStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record): bool => $record->isDraft()),
                Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar venta')
                    ->modalDescription('Se descontará el stock de cada producto en las líneas.')
                    ->visible(fn ($record): bool => $record->isDraft())
                    ->action(function ($record): void {
                        try {
                            app(StockDocumentService::class)->confirmSelling($record);
                            Notification::make()
                                ->title('Venta confirmada')
                                ->success()
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('No se pudo confirmar')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar venta')
                    ->modalDescription(fn ($record): string => $record->isConfirmed()
                        ? 'Se devolverá el stock descontado por esta venta.'
                        : 'El documento quedará marcado como cancelado.')
                    ->visible(fn ($record): bool => $record->isDraft() || $record->isConfirmed())
                    ->action(function ($record): void {
                        try {
                            app(StockDocumentService::class)->cancelSelling($record);
                            Notification::make()
                                ->title('Venta cancelada')
                                ->success()
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('No se pudo cancelar')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->visible(fn ($record): bool => $record->isDraft()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
