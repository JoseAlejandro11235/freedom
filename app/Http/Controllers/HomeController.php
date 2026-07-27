<?php

namespace App\Http\Controllers;

use App\Enums\HomepageSection;
use App\Models\Product;
use App\Support\CatalogPresenter;
use App\Support\StorefrontPresenter;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $featuredProducts = Product::query()
            ->with(['brand', 'images'])
            ->forHomepageSection(HomepageSection::Featured)
            ->get()
            ->map(fn (Product $product) => CatalogPresenter::product($product))
            ->values()
            ->all();

        $newArrivalsProducts = Product::query()
            ->with(['brand', 'images'])
            ->forHomepageSection(HomepageSection::NewArrivals)
            ->get()
            ->map(fn (Product $product) => CatalogPresenter::product($product))
            ->values()
            ->all();

        $categories = StorefrontPresenter::categories()
            ->values()
            ->all();

        return Inertia::render('welcome', [
            'meta' => [
                'title' => 'Freedom — Perfumes, Maquillaje y Skincare Online en Perú',
                'description' => 'Compra perfumes, maquillaje y skincare de las mejores marcas de lujo.',
            ],
            'featuredProducts' => $featuredProducts,
            'newArrivalsProducts' => $newArrivalsProducts,
            'categories' => $categories,
            'promos' => StorefrontPresenter::promos(),
            'brands' => StorefrontPresenter::brandNames(),
        ]);
    }
}
