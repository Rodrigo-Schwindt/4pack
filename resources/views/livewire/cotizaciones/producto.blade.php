{{--
    Un producto que se sumo a la cotizacion con Duplicar / Nuevo producto. Es
    el mismo formulario, sin la cabecera: en Datos se edita y en Costos muestra
    lo suyo. La solapa Cotizacion la arma la cotizacion, que agrupa las opciones.
--}}
@php
    $conFormulario = in_array($tipo_producto, ['bobinas', 'confeccion-dpk'], true);
    $carpeta = $tipo_producto === 'confeccion-dpk' ? 'confeccion-dpk' : 'bobinas';
@endphp

<div>
    @if ($solapa === 'datos')
        <section class="rounded-lg bg-white shadow-sm">
            @include('livewire.cotizaciones.tipos._producto')
        </section>
    @elseif (! $conFormulario)
        <section class="rounded-lg bg-white px-6 py-5 shadow-sm">
            <p class="text-sm text-slate-400">
                {{ $etiquetaProducto }} todavía no tiene tipo de producto: completalo en Datos a completar.
            </p>
        </section>
    @elseif ($solapa === 'costos')
        @include('livewire.cotizaciones.tipos.'.$carpeta.'.costos')
    @endif
</div>
