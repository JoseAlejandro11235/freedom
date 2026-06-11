<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\ManagesProductImages;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    use ManagesProductImages;

    protected static string $resource = ProductResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $images = $this->extractProductImagesFromFormData($data);

        $product = static::getModel()::query()->create($data);

        $this->syncProductImages($product, $images);

        return $product;
    }
}
