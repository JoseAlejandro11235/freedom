<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products_preview') && Schema::hasColumn('products_preview', 'brand_id')) {
            Schema::table('products_preview', function (Blueprint $table) {
                $table->dropColumn('brand_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products_preview') && ! Schema::hasColumn('products_preview', 'brand_id')) {
            Schema::table('products_preview', function (Blueprint $table) {
                $table->ulid('brand_id')->nullable()->index();
            });
        }
    }
};
