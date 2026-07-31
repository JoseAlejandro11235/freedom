<?php

namespace App\Filament\Resources\CashMovements\Pages;

use App\Filament\Resources\CashMovements\CashMovementResource;
use App\Models\CashMovement;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCashMovement extends EditRecord
{
    protected static string $resource = CashMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->isManual()),
        ];
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var CashMovement $record */
        $record = $this->getRecord();

        abort_unless($record->isManual(), 403);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['source'], $data['sourceable_type'], $data['sourceable_id']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var CashMovement $record */
        abort_unless($record->isManual(), 403);

        $record->update([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'occurred_at' => $data['occurred_at'],
        ]);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
