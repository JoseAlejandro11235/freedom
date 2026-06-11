<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('personas')
            ->whereNotNull('document_number')
            ->where('document_number', '!=', '')
            ->select('document_number')
            ->groupBy('document_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('document_number')
            ->each(function (string $documentNumber): void {
                $personas = DB::table('personas')
                    ->where('document_number', $documentNumber)
                    ->orderBy('id')
                    ->get();

                $target = $personas->first();

                if ($target === null) {
                    return;
                }

                foreach ($personas->skip(1) as $duplicate) {
                    DB::table('customers')
                        ->where('persona_id', $duplicate->id)
                        ->update(['persona_id' => $target->id]);

                    DB::table('providers')
                        ->where('persona_id', $duplicate->id)
                        ->update(['persona_id' => $target->id]);

                    $updates = [];

                    foreach (['first_name', 'last_name', 'razon_social', 'phone', 'email'] as $field) {
                        if (filled($duplicate->{$field})) {
                            $updates[$field] = $duplicate->{$field};
                        }
                    }

                    if ($updates !== []) {
                        $updates['updated_at'] = now();

                        DB::table('personas')
                            ->where('id', $target->id)
                            ->update($updates);
                    }

                    DB::table('personas')
                        ->where('id', $duplicate->id)
                        ->delete();
                }
            });

        Schema::table('personas', function (Blueprint $table) {
            $table->dropIndex(['document_number']);
            $table->unique('document_number');
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
            $table->index('document_number');
        });
    }
};
