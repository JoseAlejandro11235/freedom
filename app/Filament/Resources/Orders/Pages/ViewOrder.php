<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\CheckoutService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markPaid')
                ->label('Marcar pagado')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Marcar pedido como pagado')
                ->modalDescription('Se descontará el stock de los productos del pedido.')
                ->visible(fn (): bool => $this->getRecord()->isPendingPayment())
                ->action(function (): void {
                    try {
                        app(CheckoutService::class)->markPaid($this->getRecord());
                        Notification::make()
                            ->title('Pedido marcado como pagado')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
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
                ->visible(fn (): bool => $this->getRecord()->isPendingPayment()
                    || $this->getRecord()->status === OrderStatus::Failed)
                ->action(function (): void {
                    $this->getRecord()->update(['status' => OrderStatus::Cancelled]);
                    Notification::make()
                        ->title('Pedido cancelado')
                        ->success()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                }),
        ];
    }
}
