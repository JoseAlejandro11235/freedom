<div class="space-y-3">
  <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
      <thead>
        <tr class="bg-gray-50 dark:bg-white/5">
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">#</th>
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">Código</th>
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">Descripción</th>
          <th class="px-3 py-2 text-end text-sm font-medium text-gray-700 dark:text-gray-200">Precio venta</th>
          <th class="px-3 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200">Estado</th>
          <th class="px-3 py-2 text-end text-sm font-medium text-gray-700 dark:text-gray-200"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-white/5">
        @forelse ($rows as $row)
          <tr
            wire:key="import-preview-row-{{ $row->id }}"
            @class([
              'bg-danger-50 dark:bg-danger-500/10' => filled($row->validation_error),
            ])
          >
            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $row->row_number }}</td>
            <td @class([
              'px-3 py-2 text-sm font-medium',
              'text-danger-700 dark:text-danger-400' => filled($row->validation_error),
              'text-gray-950 dark:text-white' => blank($row->validation_error),
            ])>
              {{ $row->code }}
            </td>
            <td class="px-3 py-2 text-sm text-gray-950 dark:text-white">{{ $row->name }}</td>
            <td class="px-3 py-2 text-end text-sm text-gray-950 dark:text-white">
              {{ $row->selling_price !== null ? 'S/ '.number_format((float) $row->selling_price, 2) : '-' }}
            </td>
            <td class="px-3 py-2 text-sm">
              @if ($row->validation_error)
                <span class="font-medium text-danger-600 dark:text-danger-400">{{ $row->validation_error }}</span>
              @else
                <span class="text-success-600 dark:text-success-400">Listo</span>
              @endif
            </td>
            <td class="px-3 py-2 text-end">
              @if ($row->canBeRemovedFromPreview())
                <button
                  type="button"
                  wire:click="deleteProductPreviewRow('{{ $row->id }}')"
                  wire:loading.attr="disabled"
                  wire:target="deleteProductPreviewRow('{{ $row->id }}')"
                  class="inline-flex items-center justify-center rounded-lg p-1.5 text-danger-600 transition hover:bg-danger-100 hover:text-danger-700 dark:text-danger-400 dark:hover:bg-danger-500/20 dark:hover:text-danger-300"
                  title="Eliminar de la vista previa"
                >
                  <x-filament::icon icon="heroicon-o-trash" class="h-5 w-5" />
                </button>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
              No hay filas en la vista previa.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <p class="text-sm text-gray-600 dark:text-gray-300">
    {{ $rows->count() }} fila(s) en la vista previa.
    @if ($invalidCount > 0)
      <span class="font-medium text-danger-600 dark:text-danger-400">
        {{ $invalidCount }} con conflictos. Elimínalas para poder importar.
      </span>
    @endif
  </p>
</div>
