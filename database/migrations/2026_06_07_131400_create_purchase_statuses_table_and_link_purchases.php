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
        Schema::create('purchase_statuses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('color')->default('gray');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('purchase_statuses')->insert([
            [
                'id' => (string) Str::ulid(),
                'code' => 'DRAFT',
                'name' => 'Borrador',
                'color' => 'gray',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'APPROVED',
                'name' => 'Aprobado',
                'color' => 'success',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'PAID',
                'name' => 'Pagado',
                'color' => 'info',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignUlid('purchase_status_id')->nullable()->after('purchase_id')->constrained('purchase_statuses')->nullOnDelete();
        });

        $statusIds = DB::table('purchase_statuses')->pluck('id', 'code');

        DB::table('purchases')
            ->select('id', 'status')
            ->orderBy('id')
            ->get()
            ->each(function (object $purchase) use ($statusIds): void {
                $code = match ($purchase->status) {
                    'confirmed' => 'APPROVED',
                    'paid' => 'PAID',
                    default => 'DRAFT',
                };

                DB::table('purchases')
                    ->where('id', $purchase->id)
                    ->update(['purchase_status_id' => $statusIds[$code]]);
            });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('status', 32)->default('draft')->after('purchase_id');
            $table->index('status');
        });

        DB::table('purchases')
            ->leftJoin('purchase_statuses', 'purchase_statuses.id', '=', 'purchases.purchase_status_id')
            ->select('purchases.id', 'purchase_statuses.code')
            ->orderBy('purchases.id')
            ->get()
            ->each(function (object $purchase): void {
                $status = match ($purchase->code) {
                    'APPROVED' => 'confirmed',
                    'PAID' => 'confirmed',
                    default => 'draft',
                };

                DB::table('purchases')
                    ->where('id', $purchase->id)
                    ->update(['status' => $status]);
            });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_status_id');
        });

        Schema::dropIfExists('purchase_statuses');
    }
};
