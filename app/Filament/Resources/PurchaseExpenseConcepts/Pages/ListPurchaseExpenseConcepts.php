<?php

namespace App\Filament\Resources\PurchaseExpenseConcepts\Pages;

use App\Filament\Resources\PurchaseExpenseConcepts\PurchaseExpenseConceptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseExpenseConcepts extends ListRecords
{
    protected static string $resource = PurchaseExpenseConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
