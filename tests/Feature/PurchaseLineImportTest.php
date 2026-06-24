<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseLineImport;
use App\Models\User;
use App\Services\PurchaseLineImportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ProductImportXlsxFactory;
use Tests\TestCase;

class PurchaseLineImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_it_stages_lines_and_resolves_products_by_code(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Product::factory()->create(['code' => '201', 'name' => 'Cartera TH']);
        Product::factory()->create(['code' => '202', 'name' => 'Labial bebe']);

        $path = Storage::disk('local')->path('imports/purchase-lines/test.xlsx');

        ProductImportXlsxFactory::create($path, [
            ['CODIGO', 'DESCRIPCION', 'CANTIDAD', 'PRECIO'],
            ['201', 'Cartera Th', 3, 23.99],
            ['202', 'Labial bebe', 5, 5.99],
        ]);

        $count = app(PurchaseLineImportService::class)->stageFromFile($user, $path);

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('purchase_lineas_imports', [
            'user_id' => $user->id,
            'code' => '201',
            'quantity' => 3,
            'unit_cost' => 23.99,
            'product_name' => 'Cartera TH',
            'is_duplicate' => false,
            'validation_error' => null,
        ]);
    }

    public function test_it_flags_duplicate_codes_but_keeps_them_importable(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Product::factory()->create(['code' => '201', 'name' => 'Cartera TH']);

        $path = Storage::disk('local')->path('imports/purchase-lines/dup.xlsx');

        ProductImportXlsxFactory::create($path, [
            ['CODIGO', 'DESCRIPCION', 'CANTIDAD', 'PRECIO'],
            ['201', 'Cartera Th', 1, 23.99],
            ['201', 'Cartera Th otra vez', 2, 23.99],
        ]);

        $service = app(PurchaseLineImportService::class);
        $service->stageFromFile($user, $path);

        $this->assertSame(1, $service->duplicateCountForUser($user));
        $this->assertCount(2, $service->toRepeaterItems($user));
    }

    public function test_it_marks_unknown_codes_as_not_found_and_excludes_them_from_items(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Product::factory()->create(['code' => '201', 'name' => 'Cartera TH']);

        $path = Storage::disk('local')->path('imports/purchase-lines/missing.xlsx');

        ProductImportXlsxFactory::create($path, [
            ['CODIGO', 'DESCRIPCION', 'CANTIDAD', 'PRECIO'],
            ['201', 'Cartera Th', 1, 23.99],
            ['999', 'Producto inexistente', 4, 10.00],
        ]);

        $service = app(PurchaseLineImportService::class);
        $service->stageFromFile($user, $path);

        $this->assertSame(1, $service->notFoundCountForUser($user));

        $missing = PurchaseLineImport::query()->where('code', '999')->first();
        $this->assertNotNull($missing);
        $this->assertSame(PurchaseLineImportService::ERROR_PRODUCT_NOT_FOUND, $missing->validation_error);
        $this->assertFalse($missing->isImportable());

        $items = $service->toRepeaterItems($user);
        $this->assertCount(1, $items);
        $this->assertSame('201', Product::query()->find(array_values($items)[0]['product_id'])->code);
    }

    public function test_it_defaults_missing_quantity_to_one_in_repeater_items(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Product::factory()->create(['code' => '201', 'name' => 'Cartera TH']);

        $path = Storage::disk('local')->path('imports/purchase-lines/no-qty.xlsx');

        ProductImportXlsxFactory::create($path, [
            ['CODIGO', 'DESCRIPCION', 'CANTIDAD', 'PRECIO'],
            ['201', 'Cartera Th', '', 23.99],
        ]);

        $service = app(PurchaseLineImportService::class);
        $service->stageFromFile($user, $path);

        $items = array_values($service->toRepeaterItems($user));

        $this->assertSame(1, $items[0]['quantity']);
        $this->assertSame('23.99', $items[0]['unit_cost']);
    }
}
