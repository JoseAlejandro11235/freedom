<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseLineImport;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Reader;

class PurchaseLineImportService
{
    public const ERROR_CODE_REQUIRED = 'El código es obligatorio.';

    public const ERROR_PRODUCT_NOT_FOUND = 'No existe un producto con ese código.';

    public function stageFromFile(User $user, mixed $uploadedFile): int
    {
        $absolutePath = $this->resolveImportFilePath($uploadedFile);

        if (! is_readable($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo leer el archivo de importación.',
            ]);
        }

        if (! $this->isXlsxFile($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => 'Solo se permiten archivos Excel (.xlsx).',
            ]);
        }

        $parsedRows = $this->parseFile($absolutePath);

        if ($parsedRows === []) {
            throw ValidationException::withMessages([
                'file' => 'El archivo no contiene filas para importar.',
            ]);
        }

        $productsByCode = Product::query()
            ->whereNotNull('code')
            ->get(['id', 'code', 'name'])
            ->keyBy(fn (Product $product): string => $this->normalizeCode((string) $product->code));

        $this->clearForUser($user);

        $seenCodes = [];
        $rowNumber = 0;

        foreach ($parsedRows as $parsedRow) {
            $rowNumber++;

            $code = $this->normalizeCode($parsedRow['code']);
            $product = $code !== '' ? $productsByCode->get($code) : null;

            $isDuplicate = $code !== '' && isset($seenCodes[$code]);

            if ($code !== '') {
                $seenCodes[$code] = true;
            }

            $validationError = match (true) {
                $code === '' => self::ERROR_CODE_REQUIRED,
                $product === null => self::ERROR_PRODUCT_NOT_FOUND,
                default => null,
            };

            PurchaseLineImport::query()->create([
                'user_id' => $user->id,
                'row_number' => $rowNumber,
                'code' => $code,
                'description' => $parsedRow['description'],
                'quantity' => $parsedRow['quantity'],
                'unit_cost' => $parsedRow['unit_cost'],
                'product_id' => $product?->id,
                'product_name' => $product?->name,
                'is_duplicate' => $isDuplicate,
                'validation_error' => $validationError,
            ]);
        }

        return $rowNumber;
    }

    public function clearForUser(User|string $user): void
    {
        PurchaseLineImport::query()
            ->forUser($user)
            ->delete();
    }

    /**
     * @return Collection<int, PurchaseLineImport>
     */
    public function rowsForUser(User|string $user): Collection
    {
        return PurchaseLineImport::query()
            ->forUser($user)
            ->orderBy('row_number')
            ->get();
    }

    public function duplicateCountForUser(User|string $user): int
    {
        return PurchaseLineImport::query()
            ->forUser($user)
            ->where('is_duplicate', true)
            ->count();
    }

    public function notFoundCountForUser(User|string $user): int
    {
        return PurchaseLineImport::query()
            ->forUser($user)
            ->whereNull('product_id')
            ->count();
    }

    /**
     * Build the repeater state used to replace the purchase lines.
     *
     * @return array<string, array<string, mixed>>
     */
    public function toRepeaterItems(User|string $user): array
    {
        $items = [];

        foreach ($this->rowsForUser($user) as $row) {
            if (! $row->isImportable()) {
                continue;
            }

            $items[(string) Str::uuid()] = [
                'product_id' => $row->product_id,
                'size_id' => null,
                'quantity' => $row->quantity !== null && $row->quantity > 0 ? $row->quantity : 1,
                'unit_cost' => $row->unit_cost,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{code: string, description: string, quantity: ?int, unit_cost: ?string}>
     */
    public function parseFile(string $absolutePath): array
    {
        if (! $this->isXlsxFile($absolutePath)) {
            throw ValidationException::withMessages([
                'file' => 'Solo se permiten archivos Excel (.xlsx).',
            ]);
        }

        $reader = new Reader;

        try {
            $reader->open($absolutePath);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo leer el archivo Excel.',
            ]);
        }

        $rows = [];
        $codeIndex = 0;
        $nameIndex = 1;
        $quantityIndex = 2;
        $priceIndex = 3;
        $isFirstRow = true;
        $lastResolvedCode = null;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = array_map(
                        fn (Cell $cell): string => $this->cellToString($cell),
                        $row->getCells(),
                    );

                    if ($values === [] || $this->rowIsEmpty($values)) {
                        continue;
                    }

                    if ($isFirstRow) {
                        $headers = array_map(
                            fn (string $header): string => $this->normalizeHeader($header),
                            $values,
                        );
                        $codeIndex = array_search('codigo', $headers, true);
                        $nameIndex = array_search('descripcion', $headers, true);
                        $quantityIndex = array_search('cantidad', $headers, true);
                        $priceIndex = array_search('precio', $headers, true);
                        $hasHeaderRow = $codeIndex !== false && $nameIndex !== false;
                        $isFirstRow = false;

                        if ($hasHeaderRow) {
                            $quantityIndex = $quantityIndex !== false ? $quantityIndex : 2;
                            $priceIndex = $priceIndex !== false ? $priceIndex : 3;

                            continue;
                        }

                        $codeIndex = 0;
                        $nameIndex = 1;
                        $quantityIndex = 2;
                        $priceIndex = 3;
                    }

                    $code = $this->resolveCode(
                        trim($values[$codeIndex] ?? ''),
                        $lastResolvedCode,
                    );
                    $description = trim($values[$nameIndex] ?? '');
                    $quantity = $this->normalizeQuantity($values[$quantityIndex] ?? '');
                    $unitCost = $this->normalizePrice($values[$priceIndex] ?? '');

                    if ($code === '' && $description === '') {
                        continue;
                    }

                    if ($code !== '') {
                        $lastResolvedCode = $code;
                    }

                    $rows[] = [
                        'code' => $code,
                        'description' => $description,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                    ];
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    public function resolveImportFilePath(mixed $uploadedFile): string
    {
        $uploadedFile = Arr::first(
            array_filter(Arr::wrap($uploadedFile), fn (mixed $file): bool => filled($file)),
        );

        if ($uploadedFile instanceof TemporaryUploadedFile) {
            $path = $uploadedFile->getRealPath();

            if (is_string($path) && is_readable($path)) {
                return $path;
            }

            throw ValidationException::withMessages([
                'file' => 'No se pudo leer el archivo subido.',
            ]);
        }

        if (! is_string($uploadedFile) || blank($uploadedFile)) {
            throw ValidationException::withMessages([
                'file' => 'Debes seleccionar un archivo.',
            ]);
        }

        if (is_readable($uploadedFile)) {
            return $uploadedFile;
        }

        $disk = Storage::disk('local');

        if ($disk->exists($uploadedFile)) {
            return $disk->path($uploadedFile);
        }

        $livewirePath = storage_path('app/livewire-tmp/'.$uploadedFile);

        if (is_readable($livewirePath)) {
            return $livewirePath;
        }

        throw ValidationException::withMessages([
            'file' => 'No se pudo leer el archivo subido. Vuelve a intentarlo.',
        ]);
    }

    /**
     * @param  list<string>  $values
     */
    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cellToString(Cell $cell): string
    {
        if ($cell instanceof FormulaCell) {
            $computedValue = $cell->getComputedValue();

            if ($computedValue !== null) {
                return $this->valueToString($computedValue);
            }

            return '';
        }

        return $this->valueToString($cell->getValue());
    }

    private function valueToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return fmod($value, 1.0) === 0.0
                ? (string) (int) $value
                : rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $stringValue = trim((string) $value);

        if (str_starts_with($stringValue, '=')) {
            return '';
        }

        return $stringValue;
    }

    private function resolveCode(string $code, ?string $lastResolvedCode): string
    {
        if ($code !== '' && ! str_starts_with($code, '=')) {
            return $code;
        }

        if ($lastResolvedCode !== null && preg_match('/^\d+$/', $lastResolvedCode) === 1) {
            return (string) ((int) $lastResolvedCode + 1);
        }

        return '';
    }

    private function normalizeHeader(string $header): string
    {
        $header = Str::ascii(mb_strtolower(trim($header)));

        return match ($header) {
            'codigo', 'code', 'cod' => 'codigo',
            'descripcion', 'description', 'nombre', 'name' => 'descripcion',
            'cantidad', 'cant', 'quantity', 'qty' => 'cantidad',
            'precio', 'precio compra', 'precio_compra', 'precio de compra', 'costo', 'costo unitario', 'unit cost', 'cost', 'price' => 'precio',
            default => $header,
        };
    }

    private function normalizeQuantity(string $quantity): ?int
    {
        $quantity = trim($quantity);

        if ($quantity === '' || str_starts_with($quantity, '=')) {
            return null;
        }

        $quantity = str_replace([' ', ','], '', $quantity);

        if (! is_numeric($quantity)) {
            return null;
        }

        return max(0, (int) round((float) $quantity));
    }

    private function normalizePrice(string $price): ?string
    {
        $price = trim($price);

        if ($price === '' || str_starts_with($price, '=')) {
            return null;
        }

        $price = str_replace(['S/', 's/', 'S/.', 's/.', '$', ' '], '', $price);
        $price = str_replace(',', '.', $price);

        if (! is_numeric($price)) {
            return null;
        }

        return number_format((float) $price, 2, '.', '');
    }

    private function normalizeCode(string $code): string
    {
        return trim($code);
    }

    private function isXlsxFile(string $absolutePath): bool
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 4);
        fclose($handle);

        return $header === "PK\x03\x04";
    }
}
