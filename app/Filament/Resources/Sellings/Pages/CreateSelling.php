<?php

namespace App\Filament\Resources\Sellings\Pages;

use App\Filament\Concerns\ManagesStockDocumentLines;
use App\Filament\Resources\Sellings\SellingResource;
use App\Enums\StockDocumentStatus;
use App\Services\StockDocumentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSelling extends CreateRecord
{
    use ManagesStockDocumentLines;

    protected static string $resource = SellingResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $lines = $this->extractLinesFromFormData($data);

        $data['user_id'] = auth()->id();
        $data['status'] = StockDocumentStatus::Draft;

        if (blank($data['selling_id'] ?? null)) {
            $data['selling_id'] = app(StockDocumentService::class)->generateSellingId();
        }

        $selling = static::getModel()::query()->create($data);
        $this->createDocumentLines($selling, $lines);

        return $selling;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
