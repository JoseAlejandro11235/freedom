<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;

class CatalogSearch
{
    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(["'", '´', '`', '’'], '', $value);
        $value = preg_replace('/[^a-z0-9\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    public static function likePattern(string $value): string
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return '%';
        }

        return '%'.str_replace(' ', '%', $normalized).'%';
    }

    public static function sqlNormalized(string $column): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE({$column}, '''', ''), '´', ''), '’', ''), '-', ' '))";
    }

    public static function findBrand(string $search): ?Brand
    {
        $normalized = self::normalize($search);

        if ($normalized === '') {
            return null;
        }

        $brands = Brand::query()->orderBy('name')->get();

        $exact = $brands->first(
            fn (Brand $brand): bool => self::normalize($brand->name) === $normalized,
        );

        if ($exact !== null) {
            return $exact;
        }

        return $brands->first(
            fn (Brand $brand): bool => str_contains(self::normalize($brand->name), $normalized)
                || str_contains($normalized, self::normalize($brand->name)),
        );
    }

    public static function findCategory(string $search): ?Category
    {
        $normalized = self::normalize($search);

        if ($normalized === '') {
            return null;
        }

        $categories = Category::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->get();

        $exact = $categories->first(
            fn (Category $category): bool => self::normalize($category->name) === $normalized,
        );

        if ($exact !== null) {
            return $exact;
        }

        return $categories->first(
            fn (Category $category): bool => str_contains(self::normalize($category->name), $normalized)
                || str_contains($normalized, self::normalize($category->name)),
        );
    }
}
