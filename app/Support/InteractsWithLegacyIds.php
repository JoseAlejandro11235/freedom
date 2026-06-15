<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait InteractsWithLegacyIds
{
    protected function referencedIdUsesBigInt(string $table, string $column = 'id'): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return false;
        }

        $dataType = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('DATA_TYPE');

        return in_array(strtolower((string) $dataType), ['bigint', 'int', 'mediumint', 'smallint', 'tinyint'], true);
    }

    /**
     * @param  'cascade'|'restrict'|'set null'|'no action'  $onDelete
     */
    protected function nullableForeignTo(
        Blueprint $table,
        string $column,
        string $referencedTable,
        ?string $after = null,
        string $onDelete = 'set null',
    ): void {
        $foreign = $this->referencedIdUsesBigInt($referencedTable)
            ? $table->foreignId($column)->nullable()
            : $table->foreignUlid($column)->nullable();

        if ($after !== null) {
            $foreign->after($after);
        }

        $constraint = $foreign->constrained($referencedTable);

        match ($onDelete) {
            'cascade' => $constraint->cascadeOnDelete(),
            'restrict' => $constraint->restrictOnDelete(),
            'no action' => $constraint->noActionOnDelete(),
            default => $constraint->nullOnDelete(),
        };
    }
}
