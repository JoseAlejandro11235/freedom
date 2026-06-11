<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'price') || Schema::hasColumn('products', 'selling_price')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('price', 'selling_price');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'selling_price') || Schema::hasColumn('products', 'price')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('selling_price', 'price');
        });
    }
};
