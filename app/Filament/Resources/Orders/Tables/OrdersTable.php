<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\CheckoutService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Nº pedido')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->color()),
                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn (Order $record): string => $record->currency ?: 'PEN')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Ítems'),
                TextColumn::make('payment_provider')
                    ->label('Pasarela')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'fake' => 'Prueba',
                        'culqi' => 'Culqi',
                        'mercadopago' => 'Mercado Pago',
                        default => $state ?: '—',
                    })
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(OrderStatus::options()),
                SelectFilter::make('payment_provider')
                    ->label('Pasarela')
                    ->options([
                        'fake' => 'Prueba local',
                        'culqi' => 'Culqi',
                        'mercadopago' => 'Mercado Pago',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markPaid')
                    ->label('Marcar pagado')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Marcar pedido como pagado')
                    ->modalDescription('Se descontará el stock de los productos del pedido.')
                    ->visible(fn (Order $record): bool => $record->isPendingPayment())
                    ->action(function (Order $record): void {
                        try {
                            app(CheckoutService::class)->markPaid($record);
                            Notification::make()
                                ->title('Pedido marcado como pagado')
                                ->success()
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('No se pudo marcar como pagado')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('markCancelled')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->isPendingPayment() || $record->status === OrderStatus::Failed)
                    ->action(function (Order $record): void {
                        $record->update(['status' => OrderStatus::Cancelled]);
                        Notification::make()
                            ->title('Pedido cancelado')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (Order $record): bool => ! $record->isPaid()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records): void {
                            $records
                                ->reject(fn (Order $record): bool => $record->isPaid())
                                ->each->delete();
                        }),
                ]),
            ]);
    }
}
