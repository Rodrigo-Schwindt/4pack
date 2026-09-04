@props(['label', 'grupo', 'modelo', 'opciones', 'creando', 'sufijo' => ''])

{{-- Select alimentado por Configuración > Ajustes, con alta al vuelo del valor. --}}
@php
    $chico = 'rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    <label for="{{ $modelo }}" class="text-sm text-slate-600">{{ $label }}</label>

    @if ($creando === $grupo)
        <div class="flex items-center gap-2">
            <input
                id="{{ $modelo }}"
                autofocus
                type="number"
                step="0.01"
                min="0"
                wire:model="nuevoValor"
                wire:keydown.enter.prevent="guardarAlta"
                wire:keydown.escape="cancelarAlta"
                placeholder="Nuevo valor"
                class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
            />
            <button type="button" wire:click="guardarAlta" aria-label="Guardar valor" class="rounded-md bg-[#1c5480] p-2 text-white hover:bg-[#174567]">
                <x-icon name="check" class="h-4 w-4" />
            </button>
            <button type="button" wire:click="cancelarAlta" aria-label="Cancelar" class="{{ $chico }}">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>

        @error('nuevoValor') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    @else
        <div class="flex items-center gap-2">
            <div class="relative flex-1">
                <select
                    id="{{ $modelo }}"
                    wire:model="{{ $modelo }}"
                    class="h-10 w-full appearance-none rounded-md border border-slate-200 bg-white pr-9 pl-3 text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                >
                    <option value="">-</option>
                    @foreach ($opciones as $opcion)
                        <option value="{{ $opcion }}">{{ $opcion }}{{ $sufijo }}</option>
                    @endforeach
                </select>
                <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
            </div>

            <button type="button" wire:click="abrirAlta('{{ $grupo }}')" title="Agregar valor" aria-label="Agregar valor a {{ $label }}" class="{{ $chico }} hover:text-[#1c5480]">
                <x-icon name="plus" class="h-4 w-4" />
            </button>
        </div>
    @endif
</div>
