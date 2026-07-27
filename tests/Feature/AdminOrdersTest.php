<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_list_and_view_storefront_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = Product::factory()->create([
            'name' => 'Perfume Admin Order',
            'is_published' => true,
        ]);

        $order = Order::query()->create([
            'number' => 'ORD-TEST-001',
            'status' => OrderStatus::Paid,
            'customer_name' => 'Cliente Web',
            'customer_email' => 'cliente@example.com',
            'shipping_address' => 'Av. Larco 123',
            'shipping_city' => 'Lima',
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'PEN',
            'payment_provider' => 'fake',
            'paid_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.orders.index'))
            ->assertOk()
            ->assertSee('ORD-TEST-001')
            ->assertSee('Cliente Web');

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.orders.view', ['record' => $order]))
            ->assertOk()
            ->assertSee('Perfume Admin Order')
            ->assertSee('cliente@example.com');
    }
}
