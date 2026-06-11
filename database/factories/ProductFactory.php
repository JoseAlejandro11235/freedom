<?php

namespace Database\Factories;

use App\Enums\HomepageSection;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => 'products/perfume.webp',
                'sort_order' => 0,
            ]);
        });
    }

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'selling_price' => fake()->randomFloat(2, 50, 500),
            'original_price' => null,
            'badge' => null,
            'exclusive_web' => false,
            'image_fit' => 'contain',
            'href' => '#',
            'homepage_section' => HomepageSection::None,
            'sort_order' => 0,
            'is_published' => true,
            'track_inventory' => true,
            'stock_quantity' => fake()->numberBetween(0, 50),
            'low_stock_threshold' => 5,
        ];
    }

    public function flashSale(): static
    {
        return $this->state(fn () => ['homepage_section' => HomepageSection::FlashSale]);
    }
}
