@php
    // Sin impresion las mangas todavia no se pueden elegir.
    $trabado = $this->sinImpresion;
@endphp

{{-- Completa la fila del tipo de producto: las medidas del envase. --}}
<x-campo.texto label="Ancho (cm)" modelo="bobinas.ancho" tipo="number" paso="0.01" modificador="live.blur" requerido />
<x-campo.texto label="Alto (cm)" modelo="bobinas.alto" tipo="number" paso="0.01" modificador="live.blur" requerido />
<x-campo.texto label="Fuelle total (cm)" modelo="bobinas.fuelle" tipo="number" paso="0.01" modificador="live.blur" />

{{-- Lo que eran los Datos técnicos: la lámina que sale del envase y cómo entran los módulos. --}}
<x-campo.texto label="Ancho desplegado (cm)" modelo="bobinas.ancho_desplegado" calculado ayuda="Alto × 2 + fuelle" />
<x-campo.texto label="Ancho refilado (cm)" modelo="bobinas.ancho_refilado" calculado ayuda="Ancho desplegado × módulos en el ancho + calle" />
<x-campo.texto label="Paso (cm)" modelo="bobinas.paso" calculado ayuda="Es el ancho del envase" />
<x-campo.texto label="Desarrollo (cm)" modelo="bobinas.desarrollo" calculado :ayuda="$this->ayudaDesarrollo() ?? 'Paso × módulos de desarrollo'" />

<x-campo.texto label="Módulos en el ancho" modelo="bobinas.modulos_ancho" tipo="number" paso="1" modificador="live.blur" placeholder="1" />
<x-campo.texto label="Calle entre módulos (cm)" modelo="bobinas.calle" tipo="number" paso="0.01" modificador="live.blur" placeholder="0" />
<x-campo.texto label="Módulos desarrollo" modelo="bobinas.modulos_desarrollo" tipo="number" paso="1" modificador="live.blur" requerido />
<x-campo.ajuste label="Mangas disp (cm)" grupo="mangas" modelo="bobinas.mangas" :opciones="$mangas" :creando="$creando" :campo-alta="$campoAlta" :deshabilitado="$trabado" />

{{-- Un importe suelto de la cotizacion: se reparte entre los envases. --}}
<div class="flex items-end gap-3 self-start">
    <x-campo.select label="Extras" modelo="bobinas.extras" :opciones="$opciones['si_no']" vacio="" live class="flex-1" />
    <x-campo.texto label="U$S" modelo="bobinas.extras_usd" tipo="number" paso="0.01" modificador="live.blur" :deshabilitado="($bobinas['extras'] ?? 'No') !== 'Si'" class="w-[96px] shrink-0" />
</div>
