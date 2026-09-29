{{--
    Un renglon por material: material, mic, demasia (cm que se suman al ancho
    refilado), proveedor, el scrap de su paso (impresion, laminacion o
    bilaminacion), lo que cuesta ese scrap y el ancho con el que se compra.
    El DPK agrega abajo los envases.
--}}
@php
    $filas = $this->esDpk ? 3 : (int) $bobinas['laminado'];
    $trabado = $this->sinImpresion;
    // Cada material va atado al scrap de un paso: 1 impresion, 2 laminacion, 3 bilaminacion.
    $scrapsFila = [
        ['impresion_scrap', 'Impresión Scrap (cm)'],
        ['laminacion_scrap', 'Laminación Scrap (cm)'],
        ['bilaminacion_scrap', 'Bilaminación Scrap (cm)'],
    ];
    $porDefecto = \App\Support\Numero::corto($this->esDpk ? $this->anchoLaminaExtraDpk : $this->anchoLaminaExtra);
    $gris = 'h-10 w-full cursor-default rounded-md border border-slate-200 bg-slate-50 px-3 text-sm text-slate-400';
@endphp

<div class="flex flex-col gap-6 px-6 py-6">
    @foreach (array_slice($bobinas['materiales'], 0, $filas, true) as $indice => $material)
        @php
            // El proveedor depende del material: solo los que lo tienen cargado.
            $proveedores = $this->proveedoresDeMaterial($material['material_id'] ?? '');
            [$scrapFila, $tituloScrap] = $scrapsFila[$indice];
            $ancho = $this->anchoMaterial($indice);
            $ayudaUsd = $trabado ? null : $this->ayudaScrap($scrapFila);
        @endphp

        <div wire:key="material-{{ $indice }}" class="grid items-start gap-x-4 gap-y-4 md:grid-cols-6 xl:grid-cols-12">
            <x-campo.select
                label="Material"
                :modelo="'bobinas.materiales.'.$indice.'.material_id'"
                :opciones="$this->materiales"
                live
                requerido
                class="md:col-span-4 xl:col-span-3"
            />
            <x-campo.texto label="mic" :modelo="'bobinas.materiales.'.$indice.'.mic'" tipo="number" paso="0.01" modificador="live.blur" class="md:col-span-1 xl:col-span-1" />

            {{-- Vacio: el valor por defecto de Configuración > Ajustes. --}}
            <x-campo.texto
                label="Demasía"
                :modelo="'bobinas.materiales.'.$indice.'.extra'"
                tipo="number"
                paso="0.01"
                modificador="live.blur"
                :placeholder="$porDefecto"
                class="md:col-span-1 xl:col-span-1"
            />

            <x-campo.select
                label="Proveedor"
                :modelo="'bobinas.materiales.'.$indice.'.proveedor_id'"
                :opciones="$proveedores"
                live
                :deshabilitado="! ($material['material_id'] ?? '')"
                :ayuda="$this->ayudaProveedor($material['material_id'] ?? '')"
                class="md:col-span-2 xl:col-span-2"
            />

            <x-campo.texto
                :label="$tituloScrap"
                :modelo="'bobinas.'.$scrapFila"
                tipo="number"
                paso="0.01"
                modificador="live.blur"
                :deshabilitado="$trabado"
                class="md:col-span-2 xl:col-span-2"
            />

            {{-- Lo que cuesta el scrap de esta fila. --}}
            <div class="flex flex-col gap-1.5 md:col-span-2 xl:col-span-1" @if ($ayudaUsd) title="{{ $ayudaUsd }}" @endif>
                <label for="usd-material-{{ $indice }}" class="text-sm text-slate-600">U$S</label>
                <input id="usd-material-{{ $indice }}" readonly value="{{ $bobinas[$scrapFila.'_usd'] ?? '' }}" placeholder="-" class="{{ $gris }}" />
            </div>

            <div class="flex flex-col gap-1.5 md:col-span-2 xl:col-span-2">
                <label for="ancho-material-{{ $indice }}" class="text-sm text-slate-600">Ancho material (cm)</label>
                <input id="ancho-material-{{ $indice }}" readonly value="{{ $ancho === null ? '' : \App\Support\Numero::corto($ancho) }}" placeholder="-" class="{{ $gris }}" />
            </div>
        </div>
    @endforeach

    <p class="text-xs text-slate-400">
        @if ($trabado)
            Poné <span class="font-medium text-slate-600">Impresión</span> en Si para cargar los scrap.
        @endif
        Ancho material = ancho refilado + demasía + scrap (todo en cm). Sin demasía cargada se usan {{ $porDefecto }} cm (Ajustes).
    </p>

    @if ($this->esDpk)
        {{-- DPK: se cotizan envases; los metros salen del ancho (J5 de la hoja). --}}
        <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
            <x-campo.texto label="Envases (unidades)" modelo="bobinas.envases" tipo="number" paso="1" modificador="live.blur" requerido />
            <x-campo.texto label="Cantidad (mts)" modelo="bobinas.cantidad" calculado />
            <x-campo.texto label="Peso (kg)" modelo="bobinas.peso" calculado :ayuda="$this->ayudaPeso()" />
        </div>
    @endif
</div>
