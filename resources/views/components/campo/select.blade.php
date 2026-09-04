@props(['label' => null, 'modelo', 'opciones' => [], 'vacio' => '-', 'live' => false, 'deshabilitado' => false, 'ayuda' => null])

@php
    // Lista simple: el value es el propio texto. Mapa (id => nombre, clave => titulo): el value es la clave.
    $items = $opciones instanceof \Illuminate\Support\Collection ? $opciones->all() : (array) $opciones;
    $esLista = array_is_list($items);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($label !== null)
        <label for="{{ $modelo }}" class="text-sm text-slate-600">{{ $label }}</label>
    @endif

    <div class="relative">
        <select
            id="{{ $modelo }}"
            wire:model{{ $live ? '.live' : '' }}="{{ $modelo }}"
            @disabled($deshabilitado)
            @if ($ayuda) title="{{ $ayuda }}" @endif
            class="h-10 w-full appearance-none rounded-md border border-slate-200 pr-9 pl-3 text-sm focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none {{ $deshabilitado ? 'cursor-default bg-slate-50 text-slate-400' : 'bg-white text-slate-800' }}"
        >
            <option value="">{{ $vacio }}</option>
            @foreach ($items as $clave => $texto)
                <option value="{{ $esLista ? $texto : $clave }}">{{ $texto }}</option>
            @endforeach
        </select>
        <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
    </div>

    @if ($ayuda)
        <p class="text-xs text-slate-400">{{ $ayuda }}</p>
    @endif

    @error($modelo) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>
