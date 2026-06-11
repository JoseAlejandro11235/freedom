<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('provider_id')
                ->nullable()
                ->after('purchase_id')
                ->constrained('providers')
                ->nullOnDelete();
        });

        Schema::table('sellings', function (Blueprint $table) {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('selling_id')
                ->constrained('customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sellings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_id');
        });
    }
};
