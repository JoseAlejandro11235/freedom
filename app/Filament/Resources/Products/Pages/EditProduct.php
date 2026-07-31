<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['categories'] = $this->getRecord()
            ->categories()
            ->pluck('categories.id')
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $categoryIds = $data['categories'] ?? [];
        unset($data['categories']);

        $record->update($data);

        $record->categories()->sync(
            is_array($categoryIds)
                ? array_values(array_filter($categoryIds, fn ($id): bool => filled($id)))
                : [],
        );

        return $record;
    }
}
