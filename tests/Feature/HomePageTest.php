<?php

namespace Tests\Feature;

use App\Enums\HomepageSection;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_homepage_renders_products_from_database(): void
    {
        Product::factory()->create([
            'name' => 'Test Fragrance',
            'homepage_section' => HomepageSection::Featured,
            'is_published' => true,
            'stock_quantity' => 10,
            'track_inventory' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->has('featuredProducts', 1)
            ->where('featuredProducts.0.name', 'Test Fragrance')
            ->has('promos')
            ->has('brands')
            ->has('navigation'));
    }
}
