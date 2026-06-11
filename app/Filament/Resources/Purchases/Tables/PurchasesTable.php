<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Models\PurchaseStatus;
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

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_id')
                    ->label('Nº compra')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider_display')
                    ->label('Proveedor')
                    ->state(fn ($record): string => $record->provider?->displayName() ?? '—')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'provider.persona',
                        fn ($query) => $query
                            ->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"),
                    )),
                TextColumn::make('currency.code')
                    ->label('Moneda')
                    ->state(fn ($record): string => $record->currency?->label() ?? '—')
                    ->toggleable(),
                TextColumn::make('status.name')
                    ->label('Estado')
                    ->badge()
                    ->state(fn ($record): string => $record->status?->label() ?? '—')
                    ->color(fn ($record): string => $record->status?->color() ?? 'gray'),
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
                SelectFilter::make('purchase_status_id')
                    ->label('Estado')
                    ->options(PurchaseStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record): bool => $record->isDraft()),
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar compra')
                    ->modalDescription('La compra pasará al estado APROBADO.')
                    ->visible(fn ($record): bool => $record->isDraft())
                    ->action(function ($record): void {
                        try {
                            app(StockDocumentService::class)->confirmPurchase($record);
                            Notification::make()
                                ->title('Compra aprobada')
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
                Action::make('pay')
                    ->label('Marcar pagada')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Marcar compra como pagada')
                    ->modalDescription('La compra pasará al estado PAGADO.')
                    ->visible(fn ($record): bool => $record->isApproved())
                    ->action(function ($record): void {
                        try {
                            app(StockDocumentService::class)->payPurchase($record);
                            Notification::make()
                                ->title('Compra pagada')
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
