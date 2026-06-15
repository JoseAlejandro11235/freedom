<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('purchase_id')->unique();
            $table->string('note')->nullable();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_record_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['purchase_record_id', 'product_id']);
        });

        Schema::create('sellings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('selling_id')->unique();
            $table->string('note')->nullable();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('selling_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('selling_record_id')->constrained('sellings')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['selling_record_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selling_lines');
        Schema::dropIfExists('sellings');
        Schema::dropIfExists('purchase_lines');
        Schema::dropIfExists('purchases');
    }
};
