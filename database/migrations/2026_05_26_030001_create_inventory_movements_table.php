<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replaced by purchases / sellings; table removed in 2026_05_26_060000_drop_inventory_movements_table.
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
