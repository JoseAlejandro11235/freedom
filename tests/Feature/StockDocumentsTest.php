<?php

namespace Tests\Feature;

use App\Enums\SellingLineStatus;
use App\Enums\StockDocumentStatus;
use App\Models\Currency;
use App\Models\Lot;
use App\Models\LotLine;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseExpense;
use App\Models\PurchaseLine;
use App\Models\PurchaseStatus;
use App\Models\Size;
use App\Support\PurchaseTotals;
use App\Models\Selling;
use App\Models\SellingLine;
use App\Services\StockDocumentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_draft_purchase_does_not_change_stock(): void
    {
        $product = $this->trackedProduct(stock: 10);

        Purchase::query()->create([
            'purchase_id' => 'PUR-000001',
            'status' => StockDocumentStatus::Draft,
        ])->lines()->create([
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_confirming_purchase_does_not_change_stock(): void
    {
        $product = $this->trackedProduct(stock: 10);
        $purchase = $this->draftPurchase($product, quantity: 5);

        app(StockDocumentService::class)->confirmPurchase($purchase);

        $purchase->refresh();
        $product->refresh();

        $this->assertTrue($purchase->status->isCode(PurchaseStatus::APPROVED));
        $this->assertSame(10, $product->stock_quantity);
    }

    public function test_confirming_purchase_does_not_create_product_lot_automatically(): void
    {
        $product = $this->trackedProduct(stock: 10);
        $purchase = $this->draftPurchase($product, quantity: 5);

        app(StockDocumentService::class)->confirmPurchase($purchase);

        $this->assertDatabaseMissing('lots', [
            'product_id' => $product->id,
        ]);
    }

    public function test_paid_purchase_does_not_change_stock(): void
    {
        $product = $this->trackedProduct(stock: 10);
        $purchase = $this->draftPurchase($product, quantity: 5);
        $service = app(StockDocumentService::class);

        $service->confirmPurchase($purchase);
        $service->payPurchase($purchase->fresh());

        $purchase->refresh();
        $product->refresh();

        $this->assertTrue($purchase->status->isCode(PurchaseStatus::PAID));
        $this->assertSame(10, $product->stock_quantity);
    }

    public function test_creating_lot_refreshes_product_stock_from_lots(): void
    {
        $product = $this->trackedProduct(stock: 0);
        $purchase = $this->draftPurchase($product, quantity: 5);
        $purchaseLine = $purchase->lines()->firstOrFail();

        $lot = Lot::query()->create([
            'lot_number' => 'LOT-STOCK-001',
            'received_at' => now(),
        ]);

        LotLine::query()->create([
            'lot_id' => $lot->id,
            'purchase_line_id' => $purchaseLine->id,
            'product_id' => $product->id,
            'quantity_received' => 5,
            'quantity_available' => 5,
            'unit_cost' => $purchaseLine->unit_cost,
        ]);

        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_creating_lot_refreshes_purchase_line_pending_quantity(): void
    {
        $product = $this->trackedProduct(stock: 0);
        $purchase = $this->draftPurchase($product, quantity: 5);
        $purchaseLine = $purchase->lines()->firstOrFail();

        $this->assertSame(5, $purchaseLine->pending_quantity);

        $lot = Lot::query()->create([
            'lot_number' => 'LOT-PENDING-001',
            'received_at' => now(),
        ]);

        LotLine::query()->create([
            'lot_id' => $lot->id,
            'purchase_line_id' => $purchaseLine->id,
            'product_id' => $product->id,
            'quantity_received' => 5,
            'quantity_available' => 5,
            'unit_cost' => $purchaseLine->unit_cost,
        ]);

        $this->assertSame(0, $purchaseLine->fresh()->pending_quantity);
    }

    public function test_lot_can_have_multiple_purchase_lines(): void
    {
        $firstProduct = $this->trackedProduct(stock: 0);
        $secondProduct = $this->trackedProduct(stock: 0);

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-MULTI-LOT',
            'status' => PurchaseStatus::APPROVED,
        ]);

        $firstLine = PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $firstProduct->id,
            'quantity' => 3,
            'unit_cost' => 10,
        ]);

        $secondLine = PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $secondProduct->id,
            'quantity' => 4,
            'unit_cost' => 20,
        ]);

        $lot = Lot::query()->create([
            'lot_number' => 'LOT-MULTI-001',
            'received_at' => now(),
        ]);

        LotLine::query()->create([
            'lot_id' => $lot->id,
            'purchase_line_id' => $firstLine->id,
            'product_id' => $firstProduct->id,
            'quantity_received' => 3,
            'quantity_available' => 3,
            'unit_cost' => $firstLine->unit_cost,
        ]);

        LotLine::query()->create([
            'lot_id' => $lot->id,
            'purchase_line_id' => $secondLine->id,
            'product_id' => $secondProduct->id,
            'quantity_received' => 4,
            'quantity_available' => 4,
            'unit_cost' => $secondLine->unit_cost,
        ]);

        $this->assertSame(3, $firstProduct->fresh()->stock_quantity);
        $this->assertSame(4, $secondProduct->fresh()->stock_quantity);
        $this->assertSame(0, $firstLine->fresh()->pending_quantity);
        $this->assertSame(0, $secondLine->fresh()->pending_quantity);
        $this->assertSame(7, (int) $lot->lines()->sum('quantity_received'));
        $this->assertSame(7, (int) $lot->lines()->sum('quantity_available'));
    }

    public function test_confirming_selling_decreases_stock(): void
    {
        $product = $this->trackedProduct(stock: 10);
        $selling = $this->draftSelling($product, quantity: 3);

        app(StockDocumentService::class)->confirmSelling($selling);

        $this->assertSame(7, $product->fresh()->stock_quantity);
    }

    public function test_cannot_confirm_selling_without_enough_stock(): void
    {
        $product = $this->trackedProduct(stock: 2);
        $selling = $this->draftSelling($product, quantity: 5);

        $this->expectException(ValidationException::class);

        app(StockDocumentService::class)->confirmSelling($selling);
    }

    public function test_cancelling_confirmed_selling_restores_stock(): void
    {
        $product = $this->trackedProduct(stock: 10);
        $selling = $this->draftSelling($product, quantity: 4);
        $service = app(StockDocumentService::class);

        $service->confirmSelling($selling);
        $service->cancelSelling($selling->fresh());

        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_confirming_selling_with_lot_decreases_lot_available_quantity(): void
    {
        $product = $this->trackedProduct(stock: 0);
        $purchase = $this->draftPurchase($product, quantity: 5);
        $service = app(StockDocumentService::class);

        $service->confirmPurchase($purchase);
        $purchaseLine = $purchase->lines()->firstOrFail();
        $lot = Lot::query()->create([
            'lot_number' => 'LOT-TEST-001',
            'received_at' => now(),
        ]);
        $lotLine = LotLine::query()->create([
            'lot_id' => $lot->id,
            'purchase_line_id' => $purchaseLine->id,
            'product_id' => $product->id,
            'quantity_received' => 5,
            'quantity_available' => 5,
            'unit_cost' => $purchaseLine->unit_cost,
        ]);

        $this->assertSame(5, $product->fresh()->stock_quantity);

        $selling = Selling::query()->create([
            'selling_id' => 'VEN-LOT-001',
            'status' => StockDocumentStatus::Draft,
        ]);

        $line = SellingLine::query()->create([
            'selling_record_id' => $selling->id,
            'product_id' => $product->id,
            'lot_id' => $lot->id,
            'lot_line_id' => $lotLine->id,
            'state' => SellingLineStatus::Assigned,
            'quantity' => 3,
        ]);

        $service->confirmSelling($selling);

        $this->assertSame(2, $lotLine->fresh()->quantity_available);
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertSame(SellingLineStatus::Confirmed, $line->fresh()->state);
    }

    public function test_purchase_total_includes_lines_and_other_expenses(): void
    {
        $product = $this->trackedProduct(stock: 0);

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-TOTAL-001',
            'status' => StockDocumentStatus::Draft,
        ]);

        PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 50.00,
        ]);

        PurchaseExpense::query()->create([
            'purchase_record_id' => $purchase->id,
            'description' => 'Flete',
            'amount' => 15.50,
            'sort_order' => 0,
        ]);

        PurchaseExpense::query()->create([
            'purchase_record_id' => $purchase->id,
            'description' => 'Embalaje',
            'amount' => 4.50,
            'sort_order' => 1,
        ]);

        $purchase->load(['lines', 'expenses']);

        $this->assertSame(100.0, $purchase->linesSubtotal());
        $this->assertSame(20.0, $purchase->expensesSubtotal());
        $this->assertSame(120.0, $purchase->total());
        $this->assertSame('S/ 120.00', PurchaseTotals::formattedTotalForPurchase($purchase));
    }

    public function test_purchase_total_uses_selected_currency_symbol(): void
    {
        $product = $this->trackedProduct(stock: 0);

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-USD-001',
            'status' => StockDocumentStatus::Draft,
            'currency_id' => Currency::query()->where('code', 'USD')->firstOrFail()->id,
        ]);

        PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_cost' => 100.00,
        ]);

        $purchase->load(['lines', 'expenses']);

        $this->assertSame('$ 100.00', PurchaseTotals::formattedTotalForPurchase($purchase));
    }

    public function test_purchase_total_in_pen_uses_exchange_rate(): void
    {
        $product = $this->trackedProduct(stock: 0);

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-TC-001',
            'status' => StockDocumentStatus::Draft,
            'currency_id' => Currency::query()->where('code', 'USD')->firstOrFail()->id,
            'exchange_rate' => 3.75,
        ]);

        PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 50.00,
        ]);

        $purchase->load(['lines', 'expenses']);

        $this->assertSame(100.0, $purchase->total());
        $this->assertSame(375.0, $purchase->totalInPen());
        $this->assertSame('S/ 375.00', PurchaseTotals::formattedTotalInPenForPurchase($purchase));
    }

    public function test_purchase_belongs_to_currency_table(): void
    {
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-CURRENCY-001',
            'status' => StockDocumentStatus::Draft,
            'currency_id' => $usd->id,
        ]);

        $this->assertTrue($usd->is($purchase->currency));
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'currency_id' => $usd->id,
        ]);
    }

    public function test_cannot_confirm_purchase_twice(): void
    {
        $product = $this->trackedProduct(stock: 5);
        $purchase = $this->draftPurchase($product, quantity: 1);
        $service = app(StockDocumentService::class);

        $service->confirmPurchase($purchase);

        $this->expectException(ValidationException::class);

        $service->confirmPurchase($purchase->fresh());
    }

    public function test_same_product_can_be_used_with_different_sizes_in_document_lines(): void
    {
        $product = $this->trackedProduct(stock: 0);
        $small = Size::factory()->create(['name' => 'S']);
        $medium = Size::factory()->create(['name' => 'M']);

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-SIZES-001',
            'status' => StockDocumentStatus::Draft,
        ]);

        PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $product->id,
            'size_id' => $small->id,
            'quantity' => 2,
        ]);

        PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $product->id,
            'size_id' => $medium->id,
            'quantity' => 3,
        ]);

        $this->assertSame(2, $purchase->lines()->count());
    }

    private function trackedProduct(int $stock): Product
    {
        return Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => $stock,
        ]);
    }

    private function draftPurchase(Product $product, int $quantity): Purchase
    {
        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-'.fake()->unique()->numerify('######'),
            'status' => StockDocumentStatus::Draft,
        ]);

        PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return $purchase->fresh('lines');
    }

    private function draftSelling(Product $product, int $quantity): Selling
    {
        $selling = Selling::query()->create([
            'selling_id' => 'VEN-'.fake()->unique()->numerify('######'),
            'status' => StockDocumentStatus::Draft,
        ]);

        SellingLine::query()->create([
            'selling_record_id' => $selling->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return $selling->fresh('lines');
    }
}
