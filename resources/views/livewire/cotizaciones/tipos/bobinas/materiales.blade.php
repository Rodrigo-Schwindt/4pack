{{-- Tres columnas de material + proveedor, y a la derecha la columna de cantidad y peso. --}}
<div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
    @foreach (array_slice($bobinas['materiales'], 0, (int) $bobinas['laminado'], true) as $indice => $material)
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

    @for ($i = (int) $bobinas['laminado']; $i < 3; $i++)
        <div class="hidden xl:block"></div>
    @endfor

    <div class="flex flex-col gap-5">
        <x-campo.texto label="Cantidad (mts)" modelo="bobinas.cantidad" tipo="number" paso="0.01" modificador="live.blur" requerido />
        <x-campo.texto label="Peso (kg)" modelo="bobinas.peso" calculado :ayuda="$this->ayudaPeso()" />
        <x-campo.select label="Peso neto" modelo="bobinas.peso_neto" :opciones="$opciones['si_no']" />
        <x-campo.texto label="x bobina (kg)" modelo="bobinas.por_bobina" />
        <x-campo.ajuste label="Buje" grupo="bujes" modelo="bobinas.buje" :opciones="$bujes" :creando="$creando" :campo-alta="$campoAlta" sufijo="´´" />
    </div>
</div>
