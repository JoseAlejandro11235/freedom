<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Collection;

class StorefrontPresenter
{
    /**
     * @return list<array{label: string, href: string, highlight?: bool}>
     */
    public static function navigation(): array
    {
        $items = [
            ['label' => 'Destacados', 'href' => '/#destacados', 'highlight' => true],
            ['label' => 'Novedades', 'href' => '/#novedades'],
        ];

        $categories = Category::query()
            ->whereNull('category_father_id')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'slug']);

        foreach ($categories as $category) {
            $items[] = [
                'label' => $category->name,
                'href' => route('catalog', ['category' => $category->slug]),
            ];
        }

        $items[] = ['label' => 'Catálogo', 'href' => route('catalog')];

        return $items;
    }

    /**
     * @return list<array{title: string, subtitle: string, cta: string, href: string, image: string}>
     */
    public static function promos(int $limit = 3): array
    {
        return Category::query()
            ->whereNull('category_father_id')
            ->where('is_published', true)
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Category $category) => self::promo($category))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function brandNames(int $limit = 12): array
    {
        return Brand::query()
            ->whereHas('products', fn ($query) => $query->published())
            ->orderBy('name')
            ->limit($limit)
            ->pluck('name')
            ->all();
    }

    /**
     * @return array{title: string, subtitle: string, cta: string, href: string, image: string}
     */
    public static function promo(Category $category): array
    {
        return [
            'title' => $category->name,
            'subtitle' => '',
            'cta' => 'Ver productos',
            'href' => route('catalog', ['category' => $category->slug]),
            'image' => $category->imageUrl() ?? '',
        ];
    }

    /**
     * @return Collection<int, array{name: string, href: string, image: string}>
     */
    public static function categories(): Collection
    {
        return Category::query()
            ->whereNull('category_father_id')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => CatalogPresenter::category($category));
    }
}
