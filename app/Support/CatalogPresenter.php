<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;

class CatalogPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function product(Product $product): array
    {
        $product->loadMissing(['brand', 'images']);

        return [
            'id' => (string) $product->id,
            'brand' => $product->brand->name,
            'name' => $product->name,
            'size' => null,
            'price' => (float) $product->selling_price,
            'originalPrice' => $product->original_price !== null ? (float) $product->original_price : null,
            'discount' => $product->discountPercent(),
            'badge' => $product->badge,
            'exclusiveWeb' => $product->exclusive_web,
            'image' => $product->imageUrl() ?? '',
            'imageFit' => $product->image_fit === 'cover' ? 'cover' : 'contain',
            'href' => $product->href ?: '#',
            'inStock' => $product->isInStock(),
            'stockQuantity' => $product->track_inventory ? $product->stock_quantity : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function category(Category $category): array
    {
        return [
            'name' => $category->name,
            'href' => $category->href ?: '#',
            'image' => $category->imageUrl() ?? '',
        ];
    }
}
