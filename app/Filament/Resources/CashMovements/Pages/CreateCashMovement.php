<?php

namespace App\Filament\Resources\CashMovements\Pages;

use App\Enums\CashMovementSource;
use App\Filament\Resources\CashMovements\CashMovementResource;
use App\Services\CashService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCashMovement extends CreateRecord
{
    protected static string $resource = CashMovementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CashService::class)->createManual([
            ...$data,
            'source' => CashMovementSource::Manual,
            'user_id' => auth()->id(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
