<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        config(['services.payments.driver' => 'fake']);
    }

    public function test_guest_can_checkout_and_pay_with_fake_gateway(): void
    {
        $product = Product::factory()->create([
            'name' => 'Perfume Checkout',
            'is_published' => true,
            'track_inventory' => true,
            'stock_quantity' => 5,
            'selling_price' => 100,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Ana Pérez',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '999888777',
            'shipping_address' => 'Av. Larco 123',
            'shipping_city' => 'Lima',
            'shipping_district' => 'Miraflores',
        ]);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame('fake', $order->payment_provider);
        $this->assertCount(1, $order->items);

        $response->assertRedirect(route('checkout.fake.pay', $order));

        $this->post(route('checkout.fake.confirm', $order), ['approved' => true])
            ->assertRedirect(route('checkout.success', $order));

        $order->refresh();
        $product->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(3, $product->stock_quantity);
        $this->assertEmpty(session('cart.items', []));
    }

    public function test_fake_payment_can_be_rejected(): void
    {
        $product = Product::factory()->create([
            'is_published' => true,
            'track_inventory' => true,
            'stock_quantity' => 2,
            'selling_price' => 50,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Ana Pérez',
            'customer_email' => 'ana@example.com',
            'shipping_address' => 'Av. Larco 123',
            'shipping_city' => 'Lima',
        ]);

        $order = Order::query()->firstOrFail();

        $this->post(route('checkout.fake.confirm', $order), ['approved' => false])
            ->assertRedirect(route('checkout.failure', $order));

        $order->refresh();
        $product->refresh();

        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertSame(2, $product->stock_quantity);
    }

    public function test_checkout_page_requires_cart_items(): void
    {
        $this->get(route('checkout.create'))
            ->assertRedirect(route('home'));
    }
}
