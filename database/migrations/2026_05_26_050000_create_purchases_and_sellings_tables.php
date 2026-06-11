<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_id')->unique();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_record_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['purchase_record_id', 'product_id']);
        });

        Schema::create('sellings', function (Blueprint $table) {
            $table->id();
            $table->string('selling_id')->unique();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('selling_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selling_record_id')->constrained('sellings')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
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
