<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inventory_movement_lines');
        Schema::dropIfExists('inventory_movements');
    }

    public function down(): void
    {
        // Restored only via the original create migration if needed.
    }
};
