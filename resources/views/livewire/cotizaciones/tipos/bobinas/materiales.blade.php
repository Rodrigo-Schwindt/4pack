{{-- Una columna por material con su proveedor; el DPK suma la de los envases. --}}
<div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 {{ $this->esDpk ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }}">
    @php $columnas = $this->esDpk ? 3 : (int) $bobinas['laminado']; @endphp
    @foreach (array_slice($bobinas['materiales'], 0, $columnas, true) as $indice => $material)
        @php
            // El proveedor depende del material: solo los que lo tienen cargado.
            $proveedores = $this->proveedoresDeMaterial($material['material_id'] ?? '');
        @endphp

        <div class="flex flex-col gap-5" wire:key="material-{{ $indice }}">
            <div class="flex items-end gap-3">
                <x-campo.select
                    label="Material"
                    :modelo="'bobinas.materiales.'.$indice.'.material_id'"
                    :opciones="$this->materiales"
                    live
                    requerido
                    class="flex-1"
                />
                <x-campo.texto label="mic" :modelo="'bobinas.materiales.'.$indice.'.mic'" tipo="number" paso="0.01" modificador="live.blur" class="w-[64px] shrink-0" />
            </div>

            <x-campo.select
                label="Proveedor"
                :modelo="'bobinas.materiales.'.$indice.'.proveedor_id'"
                :opciones="$proveedores"
                live
                :deshabilitado="! ($material['material_id'] ?? '')"
                :ayuda="$this->ayudaProveedor($material['material_id'] ?? '')"
            />
        </div>
    @endforeach

    @for ($i = $columnas; $i < 3; $i++)
        <div class="hidden xl:block"></div>
    @endfor

    @if ($this->esDpk)
        {{-- DPK: se cotizan envases; los metros salen del ancho (J5 de la hoja). --}}
        <div class="flex flex-col gap-5">
            <x-campo.texto label="Envases (unidades)" modelo="bobinas.envases" tipo="number" paso="1" modificador="live.blur" requerido />
            <x-campo.texto label="Cantidad (mts)" modelo="bobinas.cantidad" calculado />
            <x-campo.texto label="Peso (kg)" modelo="bobinas.peso" calculado :ayuda="$this->ayudaPeso()" />
        </div>
    @endif
</div>
