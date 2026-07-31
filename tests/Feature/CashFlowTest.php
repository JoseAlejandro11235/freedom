<?php

namespace Tests\Feature;

use App\Enums\CashMovementSource;
use App\Enums\CashMovementType;
use App\Enums\OrderStatus;
use App\Enums\SellingLineStatus;
use App\Enums\StockDocumentStatus;
use App\Models\CashMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\Selling;
use App\Models\SellingLine;
use App\Services\CashService;
use App\Services\CheckoutService;
use App\Services\StockDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_income_and_expense_update_balance(): void
    {
        $cash = app(CashService::class);

        $cash->createManual([
            'type' => CashMovementType::Income,
            'amount' => 100,
            'description' => 'Aporte inicial',
        ]);

        $cash->createManual([
            'type' => CashMovementType::Expense,
            'amount' => 40,
            'description' => 'Gasto oficina',
        ]);

        $summary = $cash->summary();

        $this->assertSame(100.0, $summary['income']);
        $this->assertSame(40.0, $summary['expense']);
        $this->assertSame(60.0, $summary['balance']);
        $this->assertSame(2, $summary['count']);
    }

    public function test_confirming_selling_creates_income(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'track_inventory' => true,
        ]);

        $selling = Selling::query()->create([
            'selling_id' => 'VEN-CASH-001',
            'status' => StockDocumentStatus::Draft,
        ]);

        SellingLine::query()->create([
            'selling_record_id' => $selling->id,
            'product_id' => $product->id,
            'state' => SellingLineStatus::Pending,
            'quantity' => 2,
            'unit_price' => 50,
        ]);

        app(StockDocumentService::class)->confirmSelling($selling->fresh('lines'));

        $movement = CashMovement::query()->first();

        $this->assertNotNull($movement);
        $this->assertSame(CashMovementType::Income, $movement->type);
        $this->assertSame(CashMovementSource::Selling, $movement->source);
        $this->assertSame(100.0, (float) $movement->amount);
    }

    public function test_paying_purchase_creates_expense(): void
    {
        $product = Product::factory()->create();

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-CASH-001',
            'status' => StockDocumentStatus::Draft,
            'exchange_rate' => 1,
        ]);

        PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_cost' => 20,
        ]);

        $service = app(StockDocumentService::class);
        $service->confirmPurchase($purchase->fresh('lines'));
        $service->payPurchase($purchase->fresh(['lines', 'expenses', 'currency', 'status']));

        $movement = CashMovement::query()->first();

        $this->assertNotNull($movement);
        $this->assertSame(CashMovementType::Expense, $movement->type);
        $this->assertSame(CashMovementSource::Purchase, $movement->source);
        $this->assertSame(60.0, (float) $movement->amount);
    }

    public function test_marking_order_paid_creates_income(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-CASH-1',
            'status' => OrderStatus::PendingPayment,
            'customer_name' => 'Ana',
            'customer_email' => 'ana@example.com',
            'shipping_address' => 'Calle 1',
            'shipping_city' => 'Lima',
            'subtotal' => 150,
            'total' => 150,
            'currency' => 'PEN',
            'payment_provider' => 'fake',
        ]);

        app(CheckoutService::class)->markPaid($order);

        $movement = CashMovement::query()->first();

        $this->assertNotNull($movement);
        $this->assertSame(CashMovementType::Income, $movement->type);
        $this->assertSame(CashMovementSource::Order, $movement->source);
        $this->assertSame(150.0, (float) $movement->amount);
    }

    public function test_cancelling_confirmed_selling_removes_income(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'track_inventory' => true,
        ]);

        $selling = Selling::query()->create([
            'selling_id' => 'VEN-CASH-002',
            'status' => StockDocumentStatus::Draft,
        ]);

        SellingLine::query()->create([
            'selling_record_id' => $selling->id,
            'product_id' => $product->id,
            'state' => SellingLineStatus::Pending,
            'quantity' => 1,
            'unit_price' => 80,
        ]);

        $service = app(StockDocumentService::class);
        $service->confirmSelling($selling->fresh('lines'));
        $this->assertSame(1, CashMovement::query()->count());

        $service->cancelSelling($selling->fresh('lines'));
        $this->assertSame(0, CashMovement::query()->count());
    }

    public function test_recording_same_source_twice_is_idempotent(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-CASH-2',
            'status' => OrderStatus::Paid,
            'customer_name' => 'Ana',
            'customer_email' => 'ana@example.com',
            'shipping_address' => 'Calle 1',
            'shipping_city' => 'Lima',
            'subtotal' => 90,
            'total' => 90,
            'currency' => 'PEN',
            'paid_at' => now(),
        ]);

        $cash = app(CashService::class);
        $cash->recordOrderIncome($order);
        $cash->recordOrderIncome($order);

        $this->assertSame(1, CashMovement::query()->count());
    }
}
