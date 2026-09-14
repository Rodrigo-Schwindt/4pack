{{-- Fila de alta / edicion de una variable. Espera $etiqueta y, para editar, $clave. --}}
@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $placeholder = match ($tipo) {
        \App\Models\VariableCosto::VOLUMEN => 'Hasta kg, o "resto"',
        \App\Models\VariableCosto::FINANCIACION => 'Días',
        \App\Models\VariableCosto::CATEGORIA => 'A, B, C...',
        default => 'Nombre',
    };
@endphp

<tr @isset($clave) wire:key="{{ $clave }}" @endisset class="border-b border-slate-100 bg-slate-50/60">
    <td class="px-6 py-3">
        <input autofocus wire:model="clave" wire:keydown.enter.prevent="guardar" placeholder="{{ $placeholder }}" aria-label="{{ $tipos[$tipo][1] }}" class="{{ $campo }} max-w-xs" />
        @error('clave') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </td>
    <td class="px-6 py-3">
        <div class="ml-auto w-36">
            <input type="number" step="any" wire:model="valor" wire:keydown.enter.prevent="guardar" placeholder="%" aria-label="Porcentaje de {{ $etiqueta }}" class="{{ $campo }} text-right" />
        </div>
        @error('valor') <p class="mt-1 text-right text-xs text-red-600">{{ $message }}</p> @enderror
    </td>
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
