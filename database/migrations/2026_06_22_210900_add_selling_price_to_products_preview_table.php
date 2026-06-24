<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products_preview') && ! Schema::hasColumn('products_preview', 'selling_price')) {
            Schema::table('products_preview', function (Blueprint $table) {
                $table->decimal('selling_price', 10, 2)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products_preview') && Schema::hasColumn('products_preview', 'selling_price')) {
            Schema::table('products_preview', function (Blueprint $table) {
                $table->dropColumn('selling_price');
            });
        }
    }
};
