<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            $table->string('status', 32)->default('pending_payment')->index();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 64)->nullable();
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_district')->nullable();
            $table->string('shipping_notes', 500)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('PEN');
            $table->string('payment_provider', 32)->nullable();
            $table->string('payment_reference')->nullable()->index();
            $table->string('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('brand_name')->nullable();
            $table->string('product_code', 64)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
