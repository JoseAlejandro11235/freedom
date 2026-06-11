<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Concerns\ManagesPurchaseExpenses;
use App\Filament\Concerns\ManagesStockDocumentLines;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Enums\StockDocumentStatus;
use App\Services\StockDocumentService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreatePurchase extends CreateRecord
{
    use ManagesPurchaseExpenses;
    use ManagesStockDocumentLines;

    protected static string $resource = PurchaseResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $lines = $this->extractLinesFromFormData($data);
        $expenses = $this->extractExpensesFromFormData($data);

        $data['user_id'] = auth()->id();
        $data['status'] = StockDocumentStatus::Draft;

        if (blank($data['purchase_id'] ?? null)) {
            $data['purchase_id'] = app(StockDocumentService::class)->generatePurchaseId();
        }

        $purchase = static::getModel()::query()->create($data);
        $this->createDocumentLines($purchase, $lines);
        $this->syncPurchaseExpenses($purchase, $expenses);

        return $purchase;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
