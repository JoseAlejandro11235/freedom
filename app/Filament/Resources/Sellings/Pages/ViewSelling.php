<?php

namespace App\Filament\Resources\Sellings\Pages;

use App\Filament\Resources\Sellings\SellingResource;
use App\Services\StockDocumentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewSelling extends ViewRecord
{
    protected static string $resource = SellingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => $this->getRecord()->isDraft()),
            Action::make('confirm')
                ->label('Confirmar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->isDraft())
                ->action(function (): void {
                    try {
                        app(StockDocumentService::class)->confirmSelling($this->getRecord());
                        Notification::make()->title('Venta confirmada')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
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
                ->visible(fn (): bool => $this->getRecord()->isDraft() || $this->getRecord()->isConfirmed())
                ->action(function (): void {
                    try {
                        app(StockDocumentService::class)->cancelSelling($this->getRecord());
                        Notification::make()->title('Venta cancelada')->success()->send();
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
