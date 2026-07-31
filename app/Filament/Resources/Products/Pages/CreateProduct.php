<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $categoryIds = $this->extractCategoryIdsFromFormData($data);

        $product = static::getModel()::query()->create($data);

        $product->categories()->sync($categoryIds);

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function extractCategoryIdsFromFormData(array &$data): array
    {
        $categoryIds = $data['categories'] ?? [];
        unset($data['categories']);

        if (! is_array($categoryIds)) {
            return [];
        }

        return array_values(array_filter($categoryIds, fn ($id): bool => filled($id)));
    }
}
