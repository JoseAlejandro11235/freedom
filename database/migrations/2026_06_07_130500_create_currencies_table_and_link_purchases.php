<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 8);
            $table->boolean('is_base')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('currencies')->insert([
            [
                'code' => 'PEN',
                'name' => 'Soles',
                'symbol' => 'S/',
                'is_base' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'USD',
                'name' => 'Dólares',
                'symbol' => '$',
                'is_base' => false,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euros',
                'symbol' => '€',
                'is_base' => false,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('note')->constrained('currencies')->nullOnDelete();
        });

        $baseCurrencyId = DB::table('currencies')->where('code', 'PEN')->value('id');

        DB::table('purchases')
            ->select('id', 'currency')
            ->orderBy('id')
            ->get()
            ->each(function (object $purchase) use ($baseCurrencyId): void {
                $currencyId = DB::table('currencies')
                    ->where('code', $purchase->currency ?? 'PEN')
                    ->value('id') ?? $baseCurrencyId;

                DB::table('purchases')
                    ->where('id', $purchase->id)
                    ->update(['currency_id' => $currencyId]);
            });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('currency', 3)->default('PEN')->after('note');
        });

        DB::table('purchases')
            ->leftJoin('currencies', 'currencies.id', '=', 'purchases.currency_id')
            ->select('purchases.id', 'currencies.code')
            ->orderBy('purchases.id')
            ->get()
            ->each(function (object $purchase): void {
                DB::table('purchases')
                    ->where('id', $purchase->id)
                    ->update(['currency' => $purchase->code ?? 'PEN']);
            });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
        });

        Schema::dropIfExists('currencies');
    }
};
