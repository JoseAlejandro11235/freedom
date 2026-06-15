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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignUlid('size_id')->nullable()->after('slug')->constrained()->nullOnDelete();
        });

        $names = DB::table('products')
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->distinct()
            ->pluck('size');

        $sizeIdsByName = [];

        foreach ($names as $name) {
            $baseSlug = Str::slug($name) ?: 'size';
            $slug = $baseSlug;
            $suffix = 1;

            while (DB::table('sizes')->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $sizeIdsByName[$name] = (string) Str::ulid();

            DB::table('sizes')->insert([
                'id' => $sizeIdsByName[$name],
                'name' => $name,
                'slug' => $slug,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($sizeIdsByName as $name => $sizeId) {
            DB::table('products')->where('size', $name)->update(['size_id' => $sizeId]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('size')->nullable()->after('slug');
        });

        $products = DB::table('products')
            ->whereNotNull('size_id')
            ->join('sizes', 'sizes.id', '=', 'products.size_id')
            ->select('products.id', 'sizes.name')
            ->get();

        foreach ($products as $product) {
            DB::table('products')->where('id', $product->id)->update(['size' => $product->name]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('size_id');
        });
    }
};
