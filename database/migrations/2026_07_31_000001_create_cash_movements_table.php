<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type', 16)->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('PEN');
            $table->string('description');
            $table->timestamp('occurred_at')->index();
            $table->string('source', 32)->default('manual')->index();
            $table->string('sourceable_type')->nullable();
            $table->ulid('sourceable_id')->nullable();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['sourceable_type', 'sourceable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
