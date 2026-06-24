<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_lineas_imports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->unsignedInteger('row_number');
            $table->string('code', 64)->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->ulid('product_id')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->string('validation_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_lineas_imports');
    }
};
