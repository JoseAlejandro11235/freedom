<?php

namespace App\Filament\Resources\PurchaseExpenseConcepts\Pages;

use App\Filament\Resources\PurchaseExpenseConcepts\PurchaseExpenseConceptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseExpenseConcept extends EditRecord
{
    protected static string $resource = PurchaseExpenseConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
