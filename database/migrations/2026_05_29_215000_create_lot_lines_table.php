<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignUlid('purchase_line_id')->nullable()->constrained('purchase_lines')->nullOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_available');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'quantity_available']);
        });

        Schema::table('selling_lines', function (Blueprint $table) {
            $table->foreignUlid('lot_line_id')
                ->nullable()
                ->after('lot_id')
                ->constrained('lot_lines')
                ->nullOnDelete();
        });

        DB::table('lots')
            ->whereNotNull('purchase_line_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $lot): void {
                $lotLineId = (string) Str::ulid();

                DB::table('lot_lines')->insert([
                    'id' => $lotLineId,
                    'lot_id' => $lot->id,
                    'purchase_line_id' => $lot->purchase_line_id,
                    'product_id' => $lot->product_id,
                    'quantity_received' => $lot->quantity_received,
                    'quantity_available' => $lot->quantity_available,
                    'unit_cost' => $lot->unit_cost,
                    'created_at' => $lot->created_at,
                    'updated_at' => $lot->updated_at,
                ]);

                DB::table('selling_lines')
                    ->where('lot_id', $lot->id)
                    ->update(['lot_line_id' => $lotLineId]);
            });
    }

    public function down(): void
    {
        Schema::table('selling_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lot_line_id');
        });

        Schema::dropIfExists('lot_lines');
    }
};
