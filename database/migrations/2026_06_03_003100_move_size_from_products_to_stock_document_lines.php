<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureIndex('purchase_lines', 'purchase_lines_purchase_record_id_index', ['purchase_record_id']);
        $this->ensureIndex('purchase_lines', 'purchase_lines_product_id_index', ['product_id']);

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropUnique('purchase_lines_purchase_record_id_product_id_unique');
            $table->foreignId('size_id')->nullable()->after('product_id')->constrained('sizes')->nullOnDelete();
            $table->unique(['purchase_record_id', 'product_id', 'size_id'], 'purchase_lines_document_product_size_unique');
        });

        $this->ensureIndex('selling_lines', 'selling_lines_selling_record_id_index', ['selling_record_id']);
        $this->ensureIndex('selling_lines', 'selling_lines_product_id_index', ['product_id']);

        Schema::table('selling_lines', function (Blueprint $table) {
            $table->dropUnique('selling_lines_selling_record_id_product_id_unique');
            $table->foreignId('size_id')->nullable()->after('product_id')->constrained('sizes')->nullOnDelete();
            $table->unique(['selling_record_id', 'product_id', 'size_id'], 'selling_lines_document_product_size_unique');
        });

        if (Schema::hasColumn('products', 'size_id')) {
            DB::table('purchase_lines')
                ->join('products', 'products.id', '=', 'purchase_lines.product_id')
                ->whereNotNull('products.size_id')
                ->select('purchase_lines.id', 'products.size_id')
                ->get()
                ->each(function (object $line): void {
                    DB::table('purchase_lines')
                        ->where('id', $line->id)
                        ->update(['size_id' => $line->size_id]);
                });

            DB::table('selling_lines')
                ->join('products', 'products.id', '=', 'selling_lines.product_id')
                ->whereNotNull('products.size_id')
                ->select('selling_lines.id', 'products.size_id')
                ->get()
                ->each(function (object $line): void {
                    DB::table('selling_lines')
                        ->where('id', $line->id)
                        ->update(['size_id' => $line->size_id]);
                });

            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('size_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('size_id')->nullable()->after('slug')->constrained('sizes')->nullOnDelete();
        });

        DB::table('purchase_lines')
            ->whereNotNull('size_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $line): void {
                DB::table('products')
                    ->where('id', $line->product_id)
                    ->whereNull('size_id')
                    ->update(['size_id' => $line->size_id]);
            });

        Schema::table('selling_lines', function (Blueprint $table) {
            $table->dropUnique('selling_lines_document_product_size_unique');
            $table->dropConstrainedForeignId('size_id');
            $table->unique(['selling_record_id', 'product_id']);
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropUnique('purchase_lines_document_product_size_unique');
            $table->dropConstrainedForeignId('size_id');
            $table->unique(['purchase_record_id', 'product_id']);
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function ensureIndex(string $table, string $index, array $columns): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table($table, function (Blueprint $table) use ($columns, $index): void {
                $table->index($columns, $index);
            });

            return;
        }

        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();

        if ($exists) {
            return;
        }

        $columnsSql = collect($columns)
            ->map(fn (string $column): string => "`{$column}`")
            ->implode(', ');

        DB::statement("CREATE INDEX `{$index}` ON `{$table}` ({$columnsSql})");
    }
};
