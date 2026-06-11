<?php

namespace App\Filament\Resources\Sellings\Pages;

use App\Filament\Concerns\ManagesStockDocumentLines;
use App\Filament\Resources\Sellings\SellingResource;
use App\Services\StockDocumentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditSelling extends EditRecord
{
    use ManagesStockDocumentLines;

    protected static string $resource = SellingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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
                ->label('Cancelar documento')
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
            DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->isDraft()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['lines'] = $this->getRecord()->lines()
            ->get()
            ->map(fn ($line) => [
                'product_id' => $line->product_id,
                'size_id' => $line->size_id,
                'lot_line_id' => $line->lot_line_id,
                'state' => $line->state?->value,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
            ])
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record->isDraft()) {
            return $record;
        }

        $lines = $this->extractLinesFromFormData($data);
        $record->update($data);
        $this->syncDocumentLines($record, $lines);

        return $record;
    }
}
