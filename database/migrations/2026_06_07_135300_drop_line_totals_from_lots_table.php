<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
            $table->unsignedInteger('quantity_received')->default(0)->after('lot_number');
            $table->unsignedInteger('quantity_available')->default(0)->after('quantity_received');
            $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity_available');

            $table->index(['product_id', 'quantity_available']);
        });
    }
};
