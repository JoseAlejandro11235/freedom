<?php

namespace App\Http\Controllers;

use App\Enums\HomepageSection;
use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogPresenter;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $flashSaleProducts = Product::query()
            ->with(['brand', 'images'])
            ->forHomepageSection(HomepageSection::FlashSale)
            ->get()
            ->map(fn (Product $product) => CatalogPresenter::product($product))
            ->values()
            ->all();

        $forHimProducts = Product::query()
            ->with(['brand', 'images'])
            ->forHomepageSection(HomepageSection::ForHim)
            ->get()
            ->map(fn (Product $product) => CatalogPresenter::product($product))
            ->values()
            ->all();

        $categories = Category::query()
            ->whereNull('category_father_id')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => CatalogPresenter::category($category))
            ->values()
            ->all();

        return Inertia::render('welcome', [
            'meta' => [
                'title' => 'Freedom — Perfumes, Maquillaje y Skincare Online en Perú',
                'description' => 'Compra perfumes, maquillaje y skincare de las mejores marcas de lujo.',
            ],
            'flashSaleProducts' => $flashSaleProducts,
            'forHimProducts' => $forHimProducts,
            'categories' => $categories,
        ]);
    }
}
