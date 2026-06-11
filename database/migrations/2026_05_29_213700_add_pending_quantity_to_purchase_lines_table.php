<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->unsignedInteger('pending_quantity')->default(0)->after('quantity');
        });

        DB::table('purchase_lines')
            ->leftJoin('lots', 'lots.purchase_line_id', '=', 'purchase_lines.id')
            ->selectRaw('purchase_lines.id, purchase_lines.quantity, COALESCE(SUM(lots.quantity_received), 0) as received_quantity')
            ->groupBy('purchase_lines.id', 'purchase_lines.quantity')
            ->get()
            ->each(function (object $line): void {
                DB::table('purchase_lines')
                    ->where('id', $line->id)
                    ->update([
                        'pending_quantity' => max(0, (int) $line->quantity - (int) $line->received_quantity),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('pending_quantity');
        });
    }
};
