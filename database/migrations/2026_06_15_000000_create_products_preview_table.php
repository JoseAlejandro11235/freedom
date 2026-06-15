<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products_preview', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->unsignedInteger('row_number');
            $table->string('code', 64);
            $table->string('name');
            $table->string('validation_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products_preview');
    }
};
