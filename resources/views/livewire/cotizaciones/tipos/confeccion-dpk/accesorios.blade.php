{{-- Zipper, troquel y pico: si el envase lo lleva, se elige el tipo (items de Insumos). --}}
@php
    $accesorios = [
        ['zipper', 'Zipper', 'tipo_zipper_id', 'Tipo de Zipper', $tiposZipper],
        ['troquel', 'Troquel', 'tipo_troquel_id', 'Tipo de Troquel', $tiposTroquel],
        ['pico', 'Pico', 'tipo_pico_id', 'Tipo de Pico', $tiposPico],
    ];
@endphp

<div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
    @foreach ($accesorios as [$campo, $label, $tipo, $labelTipo, $items])
        <div class="flex flex-col gap-5" wire:key="accesorio-{{ $campo }}">
            <x-campo.select :label="$label" :modelo="'bobinas.'.$campo" :opciones="$opciones['si_no']" vacio="" live />
            <x-campo.select
                :label="$labelTipo"
                :modelo="'bobinas.'.$tipo"
                :opciones="$items"
                live
                :deshabilitado="($bobinas[$campo] ?? 'No') !== 'Si'"
                :ayuda="($bobinas[$campo] ?? 'No') === 'Si' && $items->isEmpty() ? 'No hay '.Str::lower($label).'s cargados en Insumos' : null"
            />
        </div>
    @endforeach
</div>
