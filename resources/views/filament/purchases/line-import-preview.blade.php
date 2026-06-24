<div class="space-y-3">
  @if ($duplicateCount > 0 || $notFoundCount > 0)
    <div class="space-y-2">
      @if ($duplicateCount > 0)
        <div class="flex items-start gap-2 rounded-xl border border-warning-300 bg-warning-50 px-3 py-2 text-sm text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-400">
          <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
          <span>
            Hay {{ $duplicateCount }} línea(s) con códigos repetidos en el archivo. Se importarán de todos modos.
          </span>
        </div>
      @endif

      @if ($notFoundCount > 0)
        <div class="flex items-start gap-2 rounded-xl border border-danger-300 bg-danger-50 px-3 py-2 text-sm text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-400">
          <x-filament::icon icon="heroicon-o-x-circle" class="mt-0.5 h-5 w-5 shrink-0" />
          <span>
            {{ $notFoundCount }} código(s) no coinciden con ningún producto y no se importarán.
          </span>
        </div>
      @endif
    </div>
  @endif

  <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
      <thead>
        <tr class="bg-gray-50 dark:bg-white/5">
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">#</th>
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">Código</th>
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">Descripción</th>
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">Producto</th>
          <th class="px-3 py-2 text-end text-sm font-medium text-gray-700 dark:text-gray-200">Cantidad</th>
          <th class="px-3 py-2 text-end text-sm font-medium text-gray-700 dark:text-gray-200">Precio</th>
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">Estado</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-white/5">
        @forelse ($rows as $row)
          <tr
            wire:key="line-import-preview-row-{{ $row->id }}"
            @class([
              'bg-danger-50 dark:bg-danger-500/10' => ! $row->isImportable(),
              'bg-warning-50 dark:bg-warning-500/10' => $row->isImportable() && $row->is_duplicate,
            ])
          >
            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $row->row_number }}</td>
            <td @class([
              'px-3 py-2 text-sm font-medium',
              'text-danger-700 dark:text-danger-400' => ! $row->isImportable(),
              'text-gray-950 dark:text-white' => $row->isImportable(),
            ])>
              {{ $row->code !== '' ? $row->code : '—' }}
            </td>
            <td class="px-3 py-2 text-sm text-gray-950 dark:text-white">{{ $row->description !== '' ? $row->description : '—' }}</td>
            <td class="px-3 py-2 text-sm text-gray-950 dark:text-white">{{ $row->product_name ?? '—' }}</td>
            <td class="px-3 py-2 text-end text-sm text-gray-950 dark:text-white">{{ $row->quantity ?? 1 }}</td>
            <td class="px-3 py-2 text-end text-sm text-gray-950 dark:text-white">
              {{ $row->unit_cost !== null ? 'S/ '.number_format((float) $row->unit_cost, 2) : '—' }}
            </td>
            <td class="px-3 py-2 text-sm">
              @if (! $row->isImportable())
                <span class="font-medium text-danger-600 dark:text-danger-400">{{ $row->validation_error }}</span>
              @elseif ($row->is_duplicate)
                <span class="font-medium text-warning-600 dark:text-warning-400">Código repetido</span>
              @else
                <span class="text-success-600 dark:text-success-400">Listo</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
              No hay filas en la vista previa.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <p class="text-sm text-gray-600 dark:text-gray-300">
    {{ $rows->count() }} fila(s) en la vista previa, {{ $importableCount }} se importará(n) y reemplazará(n) las líneas actuales.
  </p>
</div>
