<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'track_inventory')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('track_inventory')->default(true)->after('is_published');
            $table->unsignedInteger('stock_quantity')->default(0)->after('track_inventory');
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock_quantity');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'track_inventory')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['track_inventory', 'stock_quantity', 'low_stock_threshold']);
        });
    }
};
