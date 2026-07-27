<?php

namespace Tests\Feature;

use App\Enums\HomepageSection;
use App\Models\Brand;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_out_of_stock_products_still_appear_on_homepage_sections(): void
    {
        $brand = Brand::factory()->create();

        Product::factory()->for($brand)->featured()->create([
            'name' => 'Out of stock featured',
            'stock_quantity' => 0,
            'track_inventory' => true,
            'is_published' => true,
        ]);

        Product::factory()->for($brand)->featured()->create([
            'name' => 'In stock featured',
            'stock_quantity' => 5,
            'track_inventory' => true,
            'is_published' => true,
        ]);

        $visible = Product::query()
            ->forHomepageSection(HomepageSection::Featured)
            ->orderBy('name')
            ->get();

        $this->assertCount(2, $visible);
        $this->assertFalse($visible->firstWhere('name', 'Out of stock featured')->isInStock());
        $this->assertTrue($visible->firstWhere('name', 'In stock featured')->isInStock());
    }

    public function test_product_without_inventory_tracking_is_always_in_stock(): void
    {
        $product = Product::factory()->create([
            'track_inventory' => false,
            'stock_quantity' => 0,
        ]);

        $this->assertTrue($product->isInStock());
    }

    public function test_low_stock_scope(): void
    {
        Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
        ]);

        Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
        ]);

        $this->assertCount(1, Product::query()->lowStock()->get());
    }
}
