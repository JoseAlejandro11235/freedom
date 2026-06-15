<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPreview;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Reader;

class ProductImportService
{
    public const ERROR_CODE_REQUIRED = 'El código es obligatorio.';

    public const ERROR_NAME_REQUIRED = 'La descripción es obligatoria.';

    public const ERROR_DUPLICATE_IN_FILE = 'El código está duplicado en el archivo.';

    public const ERROR_CODE_EXISTS_IN_PRODUCTS = 'El código ya existe en productos.';

    /**
     * @return array{created: int}
     */
    public function commit(User|string $user): array
    {
        $userId = $user instanceof User ? $user->id : $user;

        $previewRows = ProductPreview::query()
            ->forUser($userId)
            ->orderBy('row_number')
            ->get();

        if ($previewRows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'No hay productos en la vista previa para importar.',
            ]);
        }

        $invalidRows = $previewRows->filter(fn (ProductPreview $row): bool => ! $row->isValid());

        if ($invalidRows->isNotEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'Corrige los errores de la vista previa antes de confirmar la importación.',
            ]);
        }

        $created = 0;

        DB::transaction(function () use ($previewRows, &$created): void {
            foreach ($previewRows as $previewRow) {
                $this->createImportedProduct($previewRow->code, $previewRow->name);

                $created++;
            }

            ProductPreview::query()
                ->whereIn('id', $previewRows->pluck('id'))
                ->delete();
        });

        return ['created' => $created];
    }

    public function clearForUser(User|string $user): void
    {
        ProductPreview::query()
            ->forUser($user)
            ->delete();
    }

    public function deletePreviewRow(User|string $user, string $previewId): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        ProductPreview::query()
            ->forUser($userId)
            ->whereKey($previewId)
            ->delete();

        $this->revalidatePreviewForUser($user);
    }

    public function revalidatePreviewForUser(User|string $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        $existingCodes = Product::query()
            ->whereNotNull('code')
            ->pluck('code')
            ->map(fn (string $code): string => $this->normalizeCode($code))
            ->all();

        $seenCodes = [];

        $rows = ProductPreview::query()
            ->forUser($userId)
            ->orderBy('row_number')
            ->get();

        foreach ($rows as $row) {
            $code = $this->normalizeCode($row->code);
            $name = trim($row->name);

            $validationError = match (true) {
                blank($code) => self::ERROR_CODE_REQUIRED,
                blank($name) => self::ERROR_NAME_REQUIRED,
                isset($seenCodes[$code]) => self::ERROR_DUPLICATE_IN_FILE,
                in_array($code, $existingCodes, true) => self::ERROR_CODE_EXISTS_IN_PRODUCTS,
                default => null,
            };

            if ($validationError === null) {
                $seenCodes[$code] = true;
            }

            if ($row->validation_error !== $validationError) {
                $row->update(['validation_error' => $validationError]);
            }
        }
    }

    public function userHasInvalidPreviewRows(User|string $user): bool
    {
        return ProductPreview::query()
            ->forUser($user)
            ->whereNotNull('validation_error')
            ->exists();
    }

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

        $existingCodes = Product::query()
            ->whereNotNull('code')
            ->pluck('code')
            ->map(fn (string $code): string => $this->normalizeCode($code))
            ->all();

        $seenCodes = [];

        $this->clearForUser($user);

        $rowNumber = 0;

        foreach ($parsedRows as $parsedRow) {
            $rowNumber++;

            $code = $this->normalizeCode($parsedRow['code']);
            $name = trim($parsedRow['name']);

            $validationError = match (true) {
                blank($code) => self::ERROR_CODE_REQUIRED,
                blank($name) => self::ERROR_NAME_REQUIRED,
                isset($seenCodes[$code]) => self::ERROR_DUPLICATE_IN_FILE,
                in_array($code, $existingCodes, true) => self::ERROR_CODE_EXISTS_IN_PRODUCTS,
                default => null,
            };

            if ($validationError === null) {
                $seenCodes[$code] = true;
            }

            ProductPreview::query()->create([
                'user_id' => $user->id,
                'row_number' => $rowNumber,
                'code' => $code,
                'name' => $name,
                'validation_error' => $validationError,
            ]);
        }

        return $rowNumber;
    }

    private function createImportedProduct(string $code, string $name): void
    {
        $idColumnType = Schema::getColumnType('products', 'id');

        if (in_array($idColumnType, ['bigint', 'integer', 'int'], true)) {
            DB::table('products')->insert([
                'code' => $code,
                'name' => $name,
                'slug' => $this->generateUniqueSlug($name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        Product::query()->create([
            'code' => $code,
            'name' => $name,
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;

        while (DB::table('products')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    /**
     * @return list<array{code: string, name: string}>
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
                        $hasHeaderRow = $codeIndex !== false && $nameIndex !== false;
                        $isFirstRow = false;

                        if ($hasHeaderRow) {
                            continue;
                        }

                        $codeIndex = 0;
                        $nameIndex = 1;
                    }

                    $code = $this->resolveCode(
                        trim($values[$codeIndex] ?? ''),
                        $lastResolvedCode,
                    );
                    $name = trim($values[$nameIndex] ?? '');

                    if ($code === '' && $name === '') {
                        continue;
                    }

                    if ($code !== '') {
                        $lastResolvedCode = $code;
                    }

                    $rows[] = [
                        'code' => $code,
                        'name' => $name,
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
            default => $header,
        };
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
