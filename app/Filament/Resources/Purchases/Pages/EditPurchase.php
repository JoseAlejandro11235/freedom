<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Concerns\ManagesPurchaseExpenses;
use App\Filament\Concerns\ManagesStockDocumentLines;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Services\StockDocumentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPurchase extends EditRecord
{
    use ManagesPurchaseExpenses;
    use ManagesStockDocumentLines;

    protected static string $resource = PurchaseResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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
                'quantity' => $line->quantity,
                'unit_cost' => $line->unit_cost,
            ])
            ->all();

        $data['other_expenses'] = $this->getRecord()->expenses()
            ->get()
            ->map(fn ($expense) => [
                'purchase_expense_concept_id' => $expense->purchase_expense_concept_id,
                'amount' => $expense->amount,
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
        $expenses = $this->extractExpensesFromFormData($data);
        $record->update($data);
        $this->syncDocumentLines($record, $lines);
        $this->syncPurchaseExpenses($record, $expenses);

        return $record;
    }
}
