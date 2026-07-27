<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('homepage_section', 'flash_sale')
            ->update(['homepage_section' => 'featured']);
    }

    public function down(): void
    {
        DB::table('products')
            ->where('homepage_section', 'featured')
            ->update(['homepage_section' => 'flash_sale']);
    }
};
