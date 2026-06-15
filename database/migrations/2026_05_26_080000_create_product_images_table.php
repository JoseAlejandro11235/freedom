<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        if (Schema::hasColumn('products', 'image_path')) {
            $products = DB::table('products')
                ->whereNotNull('image_path')
                ->where('image_path', '!=', '')
                ->get(['id', 'image_path']);

            foreach ($products as $product) {
                DB::table('product_images')->insert([
                    'id' => (string) Str::ulid(),
                    'product_id' => $product->id,
                    'path' => $product->image_path,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_images')) {
            return;
        }

        if (! Schema::hasColumn('products', 'image_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('badge');
            });

            $images = DB::table('product_images')
                ->orderBy('product_id')
                ->orderBy('sort_order')
                ->get();

            $seen = [];
            foreach ($images as $image) {
                if (isset($seen[$image->product_id])) {
                    continue;
                }

                DB::table('products')
                    ->where('id', $image->product_id)
                    ->update(['image_path' => $image->path]);

                $seen[$image->product_id] = true;
            }
        }

        Schema::dropIfExists('product_images');
    }
};
