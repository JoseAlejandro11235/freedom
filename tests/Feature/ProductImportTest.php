<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPreview;
use App\Models\User;
use App\Services\ProductImportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\ProductImportXlsxFactory;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_it_stages_rows_in_products_preview_from_xlsx(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = Storage::disk('local')->path('imports/products/test.xlsx');

        ProductImportXlsxFactory::create($path, [
            ['CODIGO', 'DESCRIPCION', 'PRECIO VENTA'],
            ['201', 'Cartera TH Blanca', 144.80],
            ['202', 'Labial bebe', 52.21],
        ]);

        $service = app(ProductImportService::class);
        $count = $service->stageFromFile($user, $path);

        $this->assertSame(2, $count);
        $this->assertDatabaseCount('products_preview', 2);
        $this->assertDatabaseHas('products_preview', [
            'user_id' => $user->id,
            'code' => '201',
            'name' => 'Cartera TH Blanca',
            'selling_price' => 144.80,
            'validation_error' => null,
        ]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_resolves_array_file_state_from_filament_upload(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = 'imports/products/test.xlsx';

        ProductImportXlsxFactory::create(Storage::disk('local')->path($path), [
            ['CODIGO', 'DESCRIPCION'],
            ['201', 'Cartera TH Blanca'],
        ]);

        $service = app(ProductImportService::class);
        $count = $service->stageFromFile($user, [$path]);

        $this->assertSame(1, $count);
    }

    public function test_it_resolves_sequential_codes_for_formula_like_rows(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = Storage::disk('local')->path('imports/products/formula-like.xlsx');

        ProductImportXlsxFactory::create($path, [
            ['CODIGO', 'DESCRIPCION'],
            [201, 'Cartera TH Blanca'],
            ['', 'Labial bebe'],
            ['', 'Brocha BB'],
            [204, 'Cartera JC'],
        ]);

        app(ProductImportService::class)->stageFromFile($user, $path);

        $this->assertDatabaseHas('products_preview', [
            'code' => '201',
            'name' => 'Cartera TH Blanca',
            'validation_error' => null,
        ]);
        $this->assertDatabaseHas('products_preview', [
            'code' => '202',
            'name' => 'Labial bebe',
            'validation_error' => null,
        ]);
        $this->assertDatabaseHas('products_preview', [
            'code' => '203',
            'name' => 'Brocha BB',
            'validation_error' => null,
        ]);
        $this->assertDatabaseHas('products_preview', [
            'code' => '204',
            'name' => 'Cartera JC',
            'validation_error' => null,
        ]);
    }

    public function test_it_rejects_non_xlsx_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $path = Storage::disk('local')->path('imports/products/test.csv');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, "CODIGO,DESCRIPCION\n201,Cartera TH Blanca\n");

        $this->expectException(ValidationException::class);

        app(ProductImportService::class)->stageFromFile($user, $path);
    }

    public function test_it_commits_preview_rows_to_products_and_clears_preview(): void
    {
        $user = User::factory()->create();

        ProductPreview::query()->create([
            'user_id' => $user->id,
            'row_number' => 1,
            'code' => '201',
            'name' => 'Cartera TH Blanca',
            'selling_price' => 144.80,
        ]);

        $result = app(ProductImportService::class)->commit($user);

        $this->assertSame(['created' => 1], $result);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'brand_id' => null,
            'code' => '201',
            'name' => 'Cartera TH Blanca',
            'selling_price' => 144.80,
        ]);
        $this->assertDatabaseCount('products_preview', 0);
    }

    public function test_it_marks_duplicate_codes_as_invalid_in_preview(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        Product::factory()->create([
            'brand_id' => $brand->id,
            'code' => '201',
            'name' => 'Producto existente',
        ]);

        $path = Storage::disk('local')->path('imports/products/duplicate.xlsx');

        ProductImportXlsxFactory::create($path, [
            ['CODIGO', 'DESCRIPCION'],
            ['201', 'Nuevo producto'],
        ]);

        app(ProductImportService::class)->stageFromFile($user, $path);

        $this->assertDatabaseHas('products_preview', [
            'code' => '201',
        ]);

        $preview = ProductPreview::query()->first();

        $this->assertNotNull($preview?->validation_error);

        $this->expectException(ValidationException::class);

        app(ProductImportService::class)->commit($user);
    }

    public function test_it_deletes_conflicting_preview_rows_and_revalidates_remaining_rows(): void
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create();

        Product::factory()->create([
            'brand_id' => $brand->id,
            'code' => '201',
            'name' => 'Producto existente',
        ]);

        $conflictingRow = ProductPreview::query()->create([
            'user_id' => $user->id,
            'row_number' => 1,
            'code' => '201',
            'name' => 'Duplicado en productos',
            'validation_error' => ProductImportService::ERROR_CODE_EXISTS_IN_PRODUCTS,
        ]);

        ProductPreview::query()->create([
            'user_id' => $user->id,
            'row_number' => 2,
            'code' => '202',
            'name' => 'Nuevo producto',
            'validation_error' => null,
        ]);

        app(ProductImportService::class)->deletePreviewRow($user, $conflictingRow->id);

        $this->assertDatabaseMissing('products_preview', ['id' => $conflictingRow->id]);
        $this->assertFalse(app(ProductImportService::class)->userHasInvalidPreviewRows($user));

        $result = app(ProductImportService::class)->commit($user);

        $this->assertSame(['created' => 1], $result);
    }

    public function test_it_revalidates_duplicate_rows_in_file_after_one_is_deleted(): void
    {
        $user = User::factory()->create();

        $firstDuplicate = ProductPreview::query()->create([
            'user_id' => $user->id,
            'row_number' => 1,
            'code' => '301',
            'name' => 'Primero',
            'validation_error' => null,
        ]);

        ProductPreview::query()->create([
            'user_id' => $user->id,
            'row_number' => 2,
            'code' => '301',
            'name' => 'Segundo',
            'validation_error' => ProductImportService::ERROR_DUPLICATE_IN_FILE,
        ]);

        app(ProductImportService::class)->deletePreviewRow($user, $firstDuplicate->id);

        $this->assertDatabaseHas('products_preview', [
            'code' => '301',
            'name' => 'Segundo',
            'validation_error' => null,
        ]);
    }
}
