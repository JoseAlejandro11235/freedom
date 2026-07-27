<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('homepage_section', 'for_him')
            ->update(['homepage_section' => 'new_arrivals']);
    }

    public function down(): void
    {
        DB::table('products')
            ->where('homepage_section', 'new_arrivals')
            ->update(['homepage_section' => 'for_him']);
    }
};
