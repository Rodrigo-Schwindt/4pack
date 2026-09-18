@php
    $chico = 'rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50';
    $altaCampo = 'h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
@endphp

{{-- Completa la fila del tipo de producto: medidas y producto. --}}
<x-campo.texto label="Ancho (cm)" modelo="bobinas.ancho" tipo="number" paso="0.01" modificador="live.blur" />
<x-campo.texto label="Paso (cm)" modelo="bobinas.paso" />
<x-campo.texto label="Módulos Desarrollo (cm)" modelo="bobinas.modulos_desarrollo" />

@include('livewire.cotizaciones.tipos.bobinas.producto')

<x-campo.texto label="Módulos Ancho (cm)" modelo="bobinas.modulos_ancho" tipo="number" paso="0.01" modificador="live.blur" />
<div class="hidden xl:block"></div>

{{-- Desarrollo toma los valores de mangas cargados en Configuración > Ajustes. --}}
<x-campo.ajuste label="Desarrollo (50)" grupo="mangas" modelo="bobinas.desarrollo" :opciones="$mangas" :creando="$creando" :campo-alta="$campoAlta" />
