<?php

namespace App\Filament\Concerns;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Arr;

trait ManagesProductImages
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractProductImagesFromFormData(array &$data): array
    {
        $images = $data['product_images'] ?? [];
        unset($data['product_images']);

        return is_array($images) ? array_values($images) : [];
    }

    /**
     * @param  list<string|list<string>>  $paths
     */
    protected function syncProductImages(Product $product, array $paths): void
    {
        $normalized = [];

        foreach ($paths as $path) {
            $resolved = is_array($path) ? Arr::first($path) : $path;

            if (filled($resolved)) {
                $normalized[] = $resolved;
            }
        }

        $product->images()->delete();

        foreach ($normalized as $sortOrder => $path) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
