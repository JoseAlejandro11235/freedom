<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_line_id')->nullable()->constrained('purchase_lines')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number')->nullable()->unique();
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_available');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'quantity_available']);
        });

        Schema::table('selling_lines', function (Blueprint $table) {
            $table->foreignId('lot_id')->nullable()->after('product_id')->constrained('lots')->nullOnDelete();
            $table->string('state')->default('pending')->after('lot_id');
        });
    }

    public function down(): void
    {
        Schema::table('selling_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lot_id');
            $table->dropColumn('state');
        });

        Schema::dropIfExists('lots');
    }
};
