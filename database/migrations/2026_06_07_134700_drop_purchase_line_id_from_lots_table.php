<?php

use App\Support\InteractsWithLegacyIds;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use InteractsWithLegacyIds;

    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_line_id');
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $this->nullableForeignTo($table, 'purchase_line_id', 'purchase_lines', 'id');
        });
    }
};
