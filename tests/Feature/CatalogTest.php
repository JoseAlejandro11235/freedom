<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_catalog_page_lists_published_products(): void
    {
        Product::factory()->create([
            'name' => 'Visible Product',
            'is_published' => true,
        ]);

        Product::factory()->create([
            'name' => 'Hidden Product',
            'is_published' => false,
        ]);

        $response = $this->get('/catalog');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('catalog')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Visible Product')
            ->where('products.total', 1)
            ->where('products.current_page', 1));
    }

    public function test_catalog_search_matches_product_name(): void
    {
        Product::factory()->create([
            'name' => 'Flowerbomb Eau de Parfum',
            'is_published' => true,
        ]);

        Product::factory()->create([
            'name' => 'Other Product',
            'is_published' => true,
        ]);

        $response = $this->get('/catalog?q=Flowerbomb');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('catalog')
            ->has('products.data', 1)
            ->where('filters.q', 'Flowerbomb')
            ->where('products.data.0.name', 'Flowerbomb Eau de Parfum'));
    }

    public function test_catalog_search_redirects_to_brand_filter_when_brand_matches(): void
    {
        $brand = Brand::factory()->create([
            'name' => "Victoria's Secret",
            'slug' => 'victorias-secret',
        ]);

        Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Bombshell Eau de Parfum',
            'is_published' => true,
        ]);

        $response = $this->get('/catalog?q=victorias secret');

        $response->assertRedirect(route('catalog', ['brand' => 'victorias-secret']));
    }

    public function test_catalog_search_matches_brand_name(): void
    {
        $brand = Brand::factory()->create(['name' => 'Dior']);

        Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Sauvage',
            'is_published' => true,
        ]);

        Product::factory()->create([
            'name' => 'Unrelated Product',
            'is_published' => true,
        ]);

        $response = $this->get('/catalog?q=Dior');

        $response->assertRedirect(route('catalog', ['brand' => $brand->slug]));

        $this->get(route('catalog', ['brand' => $brand->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('catalog')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Sauvage')
                ->where('activeBrand.name', 'Dior'));
    }

    public function test_catalog_search_matches_category_name(): void
    {
        $category = Category::query()->create([
            'name' => 'Skincare',
            'slug' => 'skincare',
            'is_published' => true,
        ]);

        $faceCream = Product::factory()->create([
            'name' => 'Face Cream',
            'is_published' => true,
        ]);
        $faceCream->categories()->attach($category->id);

        Product::factory()->create([
            'name' => 'Perfume',
            'is_published' => true,
        ]);

        $response = $this->get('/catalog?q=Skincare');

        $response->assertRedirect(route('catalog', ['category' => 'skincare']));

        $this->get(route('catalog', ['category' => 'skincare']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('catalog')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Face Cream')
                ->where('activeCategory.name', 'Skincare'));
    }

    public function test_catalog_filters_by_brand_slug(): void
    {
        $brand = Brand::factory()->create(['name' => 'Chanel', 'slug' => 'chanel']);

        Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'No 5',
            'is_published' => true,
        ]);

        Product::factory()->create([
            'name' => 'Other Product',
            'is_published' => true,
        ]);

        $response = $this->get('/catalog?brand=chanel');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('catalog')
            ->has('products.data', 1)
            ->where('activeBrand.name', 'Chanel')
            ->where('products.data.0.name', 'No 5'));
    }

    public function test_catalog_filters_by_category_slug(): void
    {
        $category = Category::query()->create([
            'name' => 'Maquillaje',
            'slug' => 'maquillaje',
            'is_published' => true,
        ]);

        $labial = Product::factory()->create([
            'name' => 'Labial',
            'is_published' => true,
        ]);
        $labial->categories()->attach($category->id);

        Product::factory()->create([
            'name' => 'Perfume',
            'is_published' => true,
        ]);

        $response = $this->get('/catalog?category=maquillaje');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('catalog')
            ->has('products.data', 1)
            ->where('activeCategory.name', 'Maquillaje')
            ->where('products.data.0.name', 'Labial'));
    }

    public function test_catalog_product_can_belong_to_multiple_categories(): void
    {
        $skincare = Category::query()->create([
            'name' => 'Skincare',
            'slug' => 'skincare-multi',
            'is_published' => true,
        ]);
        $makeup = Category::query()->create([
            'name' => 'Maquillaje',
            'slug' => 'maquillaje-multi',
            'is_published' => true,
        ]);

        $product = Product::factory()->create([
            'name' => 'Tinted Moisturizer',
            'is_published' => true,
        ]);
        $product->categories()->attach([$skincare->id, $makeup->id]);

        $this->get('/catalog?category=skincare-multi')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('catalog')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Tinted Moisturizer'));

        $this->get('/catalog?category=maquillaje-multi')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('catalog')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Tinted Moisturizer'));
    }

    public function test_catalog_lists_products_without_brand(): void
    {
        Product::factory()->create([
            'brand_id' => null,
            'name' => 'VS Body Mist',
            'code' => 'VS-001',
            'is_published' => true,
        ]);

        $this->get('/catalog?q=vs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('catalog')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'VS Body Mist')
                ->where('products.data.0.brand', ''));
    }

    public function test_catalog_paginates_products(): void
    {
        config(['freedom.catalog_per_page' => 2]);

        Product::factory()->count(5)->create([
            'is_published' => true,
        ]);

        $this->get('/catalog')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('catalog')
                ->has('products.data', 2)
                ->where('products.total', 5)
                ->where('products.per_page', 2)
                ->where('products.current_page', 1)
                ->where('products.last_page', 3));

        $this->get('/catalog?page=2')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('catalog')
                ->has('products.data', 2)
                ->where('products.current_page', 2));
    }
}
