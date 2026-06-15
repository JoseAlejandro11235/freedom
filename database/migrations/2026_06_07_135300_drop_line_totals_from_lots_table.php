<?php

use App\Support\InteractsWithLegacyIds;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use InteractsWithLegacyIds;

    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id', 'quantity_available']);
            $table->dropColumn(['product_id', 'quantity_received', 'quantity_available', 'unit_cost']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('lots', 'product_id')) {
            Schema::table('lots', function (Blueprint $table) {
                $this->dropLotInventoryColumns($table);
            });
        }

        Schema::table('lots', function (Blueprint $table) {
            $this->nullableForeignTo($table, 'product_id', 'products', 'id');
            $table->unsignedInteger('quantity_received')->default(0)->after('lot_number');
            $table->unsignedInteger('quantity_available')->default(0)->after('quantity_received');
            $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity_available');

            $table->index(['product_id', 'quantity_available']);
        });
    }

    private function dropLotInventoryColumns(Blueprint $table): void
    {
        if (Schema::hasColumn('lots', 'product_id')) {
            try {
                $table->dropForeign(['product_id']);
            } catch (\Throwable) {
                // FK may be missing after a partial rollback.
            }
        }

        if (Schema::hasColumn('lots', 'product_id') && Schema::hasColumn('lots', 'quantity_available')) {
            try {
                $table->dropIndex(['product_id', 'quantity_available']);
            } catch (\Throwable) {
                // Index may be missing after a partial rollback.
            }
        }

        $columns = array_filter([
            Schema::hasColumn('lots', 'product_id') ? 'product_id' : null,
            Schema::hasColumn('lots', 'quantity_received') ? 'quantity_received' : null,
            Schema::hasColumn('lots', 'quantity_available') ? 'quantity_available' : null,
            Schema::hasColumn('lots', 'unit_cost') ? 'unit_cost' : null,
        ]);

        if ($columns !== []) {
            $table->dropColumn($columns);
        }
    }
};
