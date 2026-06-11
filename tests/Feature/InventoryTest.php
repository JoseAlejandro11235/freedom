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

    public function test_out_of_stock_products_are_hidden_from_homepage_sections(): void
    {
        $brand = Brand::factory()->create();

        Product::factory()->for($brand)->flashSale()->create([
            'stock_quantity' => 0,
            'track_inventory' => true,
        ]);

        Product::factory()->for($brand)->flashSale()->create([
            'stock_quantity' => 5,
            'track_inventory' => true,
        ]);

        $visible = Product::query()
            ->forHomepageSection(HomepageSection::FlashSale)
            ->get();

        $this->assertCount(1, $visible);
        $this->assertSame(5, $visible->first()->stock_quantity);
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
