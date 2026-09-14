{{-- Fila de alta / edicion de un sector. Espera $etiqueta y, para editar, $clave. --}}
@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $numeros = [
        ['valor_hora', 'Valor hora', '0.01'],
        ['produccion_mts_hora', 'Producción mts/h', '1'],
        ['setup_horas', 'Setup hs', '0.01'],
        ['scrap_pct', 'Scrap %', '0.01'],
    ];
@endphp

<tr @isset($clave) wire:key="{{ $clave }}" @endisset class="border-b border-slate-100 bg-slate-50/60">
    <td class="px-6 py-3">
        <input autofocus wire:model="sector" placeholder="Nombre del sector" aria-label="Nombre del sector" class="{{ $campo }}" />
        @error('sector') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </td>
    @foreach ($numeros as [$modelo, $label, $paso])
        <td class="px-6 py-3">
            <input type="number" step="{{ $paso }}" min="0" wire:model="{{ $modelo }}" placeholder="-" aria-label="{{ $label }} de {{ $etiqueta }}" class="{{ $campo }} text-right" />
            @error($modelo) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </td>
    @endforeach
    <td class="px-6 py-3">
        <div class="flex items-center justify-end gap-3">
            <button type="button" wire:click="guardar" aria-label="Guardar {{ $etiqueta }}" class="text-[#1c5480] hover:text-[#174567]">
                <x-icon name="check" class="h-4 w-4" />
            </button>
            <button type="button" wire:click="cancelar" aria-label="Cancelar" class="text-slate-400 hover:text-slate-600">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    </td>
</tr>
