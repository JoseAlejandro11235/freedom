<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\ManagesProductImages;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    use ManagesProductImages;

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
        $data['product_images'] = $this->getRecord()
            ->images()
            ->orderBy('sort_order')
            ->pluck('path')
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $images = $this->extractProductImagesFromFormData($data);

        $record->update($data);

        $this->syncProductImages($record, $images);

        return $record;
    }
}
