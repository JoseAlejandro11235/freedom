<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_can_add_product_to_cart(): void
    {
        $product = Product::factory()->create([
            'is_published' => true,
            'track_inventory' => true,
            'stock_quantity' => 5,
            'selling_price' => 99.5,
        ]);

        $response = $this->from('/')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');
        $this->assertSame(2, session('cart.items')[$product->id]);
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        $product = Product::factory()->create([
            'is_published' => true,
            'track_inventory' => true,
            'stock_quantity' => 0,
        ]);

        $response = $this->from('/')->post(route('cart.items.store'), [
            'product_id' => $product->id,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
        $this->assertEmpty(session('cart.items', []));
    }

    public function test_cannot_exceed_available_stock(): void
    {
        $product = Product::factory()->create([
            'is_published' => true,
            'track_inventory' => true,
            'stock_quantity' => 1,
        ]);

        $this->from('/')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect('/');

        $response = $this->from('/')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
        $this->assertSame(1, session('cart.items')[$product->id]);
    }

    public function test_can_update_and_remove_cart_items(): void
    {
        $product = Product::factory()->create([
            'is_published' => true,
            'track_inventory' => true,
            'stock_quantity' => 10,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->from('/')
            ->patch(route('cart.items.update', $product), ['quantity' => 4])
            ->assertRedirect('/');

        $this->assertSame(4, session('cart.items')[$product->id]);

        $this->from('/')
            ->delete(route('cart.items.destroy', $product))
            ->assertRedirect('/');

        $this->assertArrayNotHasKey($product->id, session('cart.items', []));
    }

    public function test_homepage_shares_cart_summary(): void
    {
        $product = Product::factory()->create([
            'name' => 'Cart Fragrance',
            'is_published' => true,
            'track_inventory' => true,
            'stock_quantity' => 3,
            'selling_price' => 50,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->where('cart.count', 2)
                ->where('cart.subtotal', 100)
                ->has('cart.items', 1)
                ->where('cart.items.0.name', 'Cart Fragrance'));
    }
}
