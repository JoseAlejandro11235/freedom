<?php

namespace Tests\Feature;

use App\Enums\StockDocumentStatus;
use App\Models\Lot;
use App\Models\LotLine;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchaseStatus;
use App\Support\LotPurchaseImporter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotPurchaseImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_options_only_include_paid_purchases_with_pending_lines(): void
    {
        $product = $this->trackedProduct();

        $paid = $this->purchaseWithLine($product, 'PUR-PAID-001', PurchaseStatus::PAID, quantity: 5);
        $this->purchaseWithLine($product, 'PUR-DRAFT-001', StockDocumentStatus::Draft, quantity: 5);
        $this->purchaseWithLine($product, 'PUR-APPROVED-001', PurchaseStatus::APPROVED, quantity: 5);

        $options = LotPurchaseImporter::options();

        $this->assertArrayHasKey($paid->id, $options);
        $this->assertCount(1, $options);
        $this->assertStringContainsString('líneas pendientes: 1', $options[$paid->id]);
    }

    public function test_options_exclude_paid_purchases_without_pending_lines(): void
    {
        $product = $this->trackedProduct();
        $purchase = $this->purchaseWithLine($product, 'PUR-PAID-DONE', PurchaseStatus::PAID, quantity: 4);
        $purchaseLine = $purchase->lines()->firstOrFail();

        $lot = Lot::query()->create(['lot_number' => 'LOT-DONE-001', 'received_at' => now()]);
        LotLine::query()->create([
            'lot_id' => $lot->id,
            'purchase_line_id' => $purchaseLine->id,
            'product_id' => $product->id,
            'quantity_received' => 4,
            'quantity_available' => 4,
            'unit_cost' => $purchaseLine->unit_cost,
        ]);

        $this->assertSame(0, $purchaseLine->fresh()->pending_quantity);
        $this->assertArrayNotHasKey($purchase->id, LotPurchaseImporter::options());
    }

    public function test_pending_line_items_map_all_pending_lines(): void
    {
        $firstProduct = $this->trackedProduct();
        $secondProduct = $this->trackedProduct();

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-IMPORT-001',
            'status' => PurchaseStatus::PAID,
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
            'quantity' => 7,
            'unit_cost' => 20,
        ]);

        $items = LotPurchaseImporter::pendingLineItems($purchase->id);

        $this->assertCount(2, $items);

        $byPurchaseLine = collect($items)->keyBy('purchase_line_id');
        $this->assertSame(3, $byPurchaseLine[$firstLine->id]['quantity_received']);
        $this->assertSame(7, $byPurchaseLine[$secondLine->id]['quantity_received']);
    }

    public function test_pending_line_items_exclude_fully_received_lines(): void
    {
        $pendingProduct = $this->trackedProduct();
        $receivedProduct = $this->trackedProduct();

        $purchase = Purchase::query()->create([
            'purchase_id' => 'PUR-IMPORT-002',
            'status' => PurchaseStatus::PAID,
        ]);

        $pendingLine = PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $pendingProduct->id,
            'quantity' => 2,
            'unit_cost' => 10,
        ]);
        $receivedLine = PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $receivedProduct->id,
            'quantity' => 6,
            'unit_cost' => 20,
        ]);

        $lot = Lot::query()->create(['lot_number' => 'LOT-PARTIAL-001', 'received_at' => now()]);
        LotLine::query()->create([
            'lot_id' => $lot->id,
            'purchase_line_id' => $receivedLine->id,
            'product_id' => $receivedProduct->id,
            'quantity_received' => 6,
            'quantity_available' => 6,
            'unit_cost' => $receivedLine->unit_cost,
        ]);

        $items = LotPurchaseImporter::pendingLineItems($purchase->id);

        $this->assertCount(1, $items);
        $this->assertSame($pendingLine->id, array_values($items)[0]['purchase_line_id']);
    }

    public function test_pending_line_items_returns_empty_for_missing_purchase(): void
    {
        $this->assertSame([], LotPurchaseImporter::pendingLineItems(null));
        $this->assertSame([], LotPurchaseImporter::pendingLineItems('non-existent-id'));
    }

    private function trackedProduct(): Product
    {
        return Product::factory()->create([
            'track_inventory' => true,
            'stock_quantity' => 0,
        ]);
    }

    private function purchaseWithLine(Product $product, string $purchaseId, $status, int $quantity): Purchase
    {
        $purchase = Purchase::query()->create([
            'purchase_id' => $purchaseId,
            'status' => $status,
        ]);

        PurchaseLine::query()->create([
            'purchase_record_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_cost' => 10,
        ]);

        return $purchase->fresh('lines');
    }
}
