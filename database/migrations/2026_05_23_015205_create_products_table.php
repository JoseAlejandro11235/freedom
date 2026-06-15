<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->string('size')->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('badge')->nullable();
            $table->boolean('exclusive_web')->default(false);
            $table->string('image_path')->nullable();
            $table->string('image_fit', 16)->default('contain');
            $table->string('href')->default('#');
            $table->string('homepage_section', 32)->default('none');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->boolean('track_inventory')->default(true);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->timestamps();

            $table->index(['homepage_section', 'is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
