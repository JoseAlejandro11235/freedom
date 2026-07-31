<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('category_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['category_id', 'product_id']);
        });

        if (Schema::hasColumn('products', 'category_id')) {
            $rows = DB::table('products')
                ->whereNotNull('category_id')
                ->get(['id', 'category_id']);

            foreach ($rows as $row) {
                DB::table('category_product')->insertOrIgnore([
                    'category_id' => $row->category_id,
                    'product_id' => $row->id,
                ]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignUlid('category_id')
                ->nullable()
                ->after('brand_id')
                ->constrained()
                ->nullOnDelete();
        });

        $firstCategories = DB::table('category_product')
            ->select('product_id', DB::raw('MIN(category_id) as category_id'))
            ->groupBy('product_id')
            ->get();

        foreach ($firstCategories as $row) {
            DB::table('products')
                ->where('id', $row->product_id)
                ->update(['category_id' => $row->category_id]);
        }

        Schema::dropIfExists('category_product');
    }
};
