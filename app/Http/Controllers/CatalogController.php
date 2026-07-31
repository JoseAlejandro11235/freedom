<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogPresenter;
use App\Support\CatalogSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $search = trim((string) $request->query('q', ''));
        $brandSlug = $request->query('brand');
        $categorySlug = $request->query('category');

        if ($search !== '' && blank($brandSlug) && blank($categorySlug)) {
            $matchedBrand = CatalogSearch::findBrand($search);

            if ($matchedBrand !== null) {
                return redirect()->route('catalog', ['brand' => $matchedBrand->slug]);
            }

            $matchedCategory = CatalogSearch::findCategory($search);

            if ($matchedCategory !== null) {
                return redirect()->route('catalog', ['category' => $matchedCategory->slug]);
            }
        }

        $query = Product::query()
            ->with(['brand', 'categories', 'images'])
            ->published()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($search !== '') {
            $like = CatalogSearch::likePattern($search);
            $normalizedName = CatalogSearch::sqlNormalized('name');
            $normalizedCode = CatalogSearch::sqlNormalized('code');

            $query->where(function ($query) use ($like, $normalizedName, $normalizedCode) {
                $query->whereRaw("{$normalizedName} LIKE ?", [$like])
                    ->orWhereRaw("{$normalizedCode} LIKE ?", [$like])
                    ->orWhereHas('brand', fn ($query) => $query->whereRaw(
                        CatalogSearch::sqlNormalized('name').' LIKE ?',
                        [$like],
                    ))
                    ->orWhereHas('categories', fn ($query) => $query->whereRaw(
                        CatalogSearch::sqlNormalized('name').' LIKE ?',
                        [$like],
                    ));
            });
        }

        $activeBrand = null;

        if (filled($brandSlug)) {
            $activeBrand = Brand::query()->where('slug', $brandSlug)->first();

            if ($activeBrand !== null) {
                $query->where('brand_id', $activeBrand->id);
            }
        }

        $activeCategory = null;

        if (filled($categorySlug)) {
            $activeCategory = Category::query()
                ->where('slug', $categorySlug)
                ->where('is_published', true)
                ->first();

            if ($activeCategory !== null) {
                $categoryIds = array_merge(
                    [$activeCategory->id],
                    $activeCategory->descendantIds(),
                );

                $query->whereHas(
                    'categories',
                    fn ($query) => $query->whereIn('categories.id', $categoryIds),
                );
            }
        }

        $perPage = max(1, (int) config('freedom.catalog_per_page', 24));

        $products = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product) => CatalogPresenter::product($product));

        $brands = Brand::query()
            ->whereHas('products', fn ($query) => $query->published())
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (Brand $brand) => [
                'name' => $brand->name,
                'slug' => $brand->slug,
                'href' => route('catalog', ['brand' => $brand->slug]),
            ])
            ->values()
            ->all();

        $categories = Category::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'slug' => $category->slug,
                'href' => route('catalog', ['category' => $category->slug]),
            ])
            ->values()
            ->all();

        return Inertia::render('catalog', [
            'products' => $products,
            'filters' => [
                'q' => $search,
                'brand' => $activeBrand?->slug,
                'category' => $activeCategory?->slug,
            ],
            'activeBrand' => $activeBrand !== null
                ? ['name' => $activeBrand->name, 'slug' => $activeBrand->slug]
                : null,
            'activeCategory' => $activeCategory !== null
                ? ['name' => $activeCategory->name, 'slug' => $activeCategory->slug]
                : null,
            'brands' => $brands,
            'categories' => $categories,
            'meta' => [
                'title' => $this->pageTitle($search, $activeBrand, $activeCategory),
            ],
        ]);
    }

    private function pageTitle(string $search, ?Brand $brand, ?Category $category): string
    {
        if ($category !== null) {
            return "{$category->name} — Freedom";
        }

        if ($brand !== null) {
            return "{$brand->name} — Freedom";
        }

        if ($search !== '') {
            return "Resultados para \"{$search}\" — Freedom";
        }

        return 'Catálogo — Freedom';
    }
}
