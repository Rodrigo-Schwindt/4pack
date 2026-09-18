@php
    // Sin impresion los datos tecnicos no se pueden completar todavia.
    $trabado = $this->sinImpresion;
@endphp

<div class="px-6 py-6">
    @if ($trabado)
        <p class="mb-5 text-sm text-slate-500">
            Poné <span class="font-medium text-slate-700">Impresión</span> en Si para cargar los datos técnicos.
        </p>
    @endif

    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
        {{-- Medidas de la lamina: alto x 2 + fuelle, por modulos, mas calle, mas el extra de Ajustes. --}}
        <div class="flex flex-col gap-5">
            <x-campo.texto label="Ancho (cm)" modelo="bobinas.ancho_desplegado" calculado ayuda="Alto × 2 + fuelle" />
            <x-campo.texto label="Ancho refilado (cm)" modelo="bobinas.ancho_refilado" calculado ayuda="Ancho × módulos en el ancho + calle" />
            <x-campo.texto label="Ancho lámina (cm)" modelo="bobinas.ancho_lamina" calculado :ayuda="'Ancho refilado + '.\App\Support\Numero::corto($this->anchoLaminaExtraDpk).' cm (Ajustes)'" />
        </div>

        <div class="flex flex-col gap-5">
            <x-campo.texto label="Impresión Scrap (cm)" modelo="bobinas.impresion_scrap" tipo="number" paso="0.01" modificador="live.blur" :deshabilitado="$trabado" />
            <x-campo.texto label="Laminación Scrap (cm)" modelo="bobinas.laminacion_scrap" tipo="number" paso="0.01" modificador="live.blur" :deshabilitado="$trabado" />
            <x-campo.texto label="Bilaminación Scrap (cm)" modelo="bobinas.bilaminacion_scrap" tipo="number" paso="0.01" modificador="live.blur" :deshabilitado="$trabado" />

            <div class="flex items-end gap-3">
                <x-campo.select label="Extras" modelo="bobinas.extras" :opciones="$opciones['si_no']" vacio="" live class="flex-1" />
                <x-campo.texto label="U$S" modelo="bobinas.extras_usd" tipo="number" paso="0.01" modificador="live.blur" :deshabilitado="($bobinas['extras'] ?? 'No') !== 'Si'" class="w-[96px] shrink-0" />
            </div>
        </div>

        <div class="flex flex-col gap-5">
            <x-campo.texto label="U$S" modelo="bobinas.impresion_scrap_usd" calculado :ayuda="$trabado ? null : $this->ayudaScrap('impresion_scrap')" />
            <x-campo.texto label="U$S" modelo="bobinas.laminacion_scrap_usd" calculado :ayuda="$trabado ? null : $this->ayudaScrap('laminacion_scrap')" />
            <x-campo.texto label="U$S" modelo="bobinas.bilaminacion_scrap_usd" calculado :ayuda="$trabado ? null : $this->ayudaScrap('bilaminacion_scrap')" />
        </div>

        <div class="flex flex-col gap-5">
            <x-campo.texto label="Paso (cm)" modelo="bobinas.paso" calculado ayuda="Es el ancho del envase" />
            <x-campo.texto label="Desarrollo (cm)" modelo="bobinas.desarrollo" calculado :ayuda="$this->ayudaDesarrollo() ?? 'Paso × módulos de desarrollo'" />
            <x-campo.ajuste label="Mangas disponibles (cm)" grupo="mangas" modelo="bobinas.mangas" :opciones="$mangas" :creando="$creando" :campo-alta="$campoAlta" :deshabilitado="$trabado" />
        </div>
    </div>

    <div class="mt-5 grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
        <x-campo.texto label="Módulos en el ancho" modelo="bobinas.modulos_ancho" tipo="number" paso="1" modificador="live.blur" placeholder="1" />
        <x-campo.texto label="Calle entre módulos (cm)" modelo="bobinas.calle" tipo="number" paso="0.01" modificador="live.blur" placeholder="0" />
        <x-campo.texto label="Módulos desarrollo" modelo="bobinas.modulos_desarrollo" tipo="number" paso="1" modificador="live.blur" requerido />
    </div>
</div>
