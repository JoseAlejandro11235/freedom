<?php

namespace App\Filament\Resources\Sellings\Pages;

use App\Filament\Resources\Sellings\SellingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSellings extends ListRecords
{
    protected static string $resource = SellingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
