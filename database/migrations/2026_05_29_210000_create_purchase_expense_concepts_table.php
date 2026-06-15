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
        Schema::create('purchase_expense_concepts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('purchase_expenses', function (Blueprint $table) {
            $table->foreignUlid('purchase_expense_concept_id')
                ->nullable()
                ->after('purchase_record_id')
                ->constrained('purchase_expense_concepts')
                ->nullOnDelete();
        });

        DB::table('purchase_expenses')
            ->select('description')
            ->whereNotNull('description')
            ->distinct()
            ->orderBy('description')
            ->get()
            ->each(function (object $expense): void {
                $conceptId = (string) Str::ulid();

                DB::table('purchase_expense_concepts')->insert([
                    'id' => $conceptId,
                    'name' => $expense->description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('purchase_expenses')
                    ->where('description', $expense->description)
                    ->update(['purchase_expense_concept_id' => $conceptId]);
            });
    }

    public function down(): void
    {
        Schema::table('purchase_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_expense_concept_id');
        });

        Schema::dropIfExists('purchase_expense_concepts');
    }
};
