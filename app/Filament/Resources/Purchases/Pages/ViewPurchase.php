<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Services\StockDocumentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewPurchase extends ViewRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => $this->getRecord()->isDraft()),
            Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->isDraft())
                ->action(function (): void {
                    try {
                        app(StockDocumentService::class)->confirmPurchase($this->getRecord());
                        Notification::make()->title('Compra aprobada')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
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
                ->visible(fn (): bool => $this->getRecord()->isApproved())
                ->action(function (): void {
                    try {
                        app(StockDocumentService::class)->payPurchase($this->getRecord());
                        Notification::make()->title('Compra pagada')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->title('No se pudo cancelar')
                            ->body(collect($exception->errors())->flatten()->first())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
