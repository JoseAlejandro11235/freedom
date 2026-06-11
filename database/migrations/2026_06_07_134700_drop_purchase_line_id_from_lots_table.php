<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_line_id');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('purchase_line_id')
                ->nullable()
                ->after('id')
                ->constrained('purchase_lines')
                ->nullOnDelete();
        });
    }
};
