{{--
    Un producto de la cotizacion, de "Datos de producto" para abajo. Lo usan
    el producto principal (dentro de la tarjeta de Datos generales) y cada
    producto que se suma con Duplicar / Nuevo producto.
--}}
@php
    use App\Livewire\Cotizaciones\Form;

    $opciones = Form::OPCIONES;
    $opciones['dias_ff'] = $diasFf ?: $opciones['dias_ff'];
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $tituloSeccion = 'px-6 pt-8 pb-5 text-[16px] leading-[normal] font-medium text-black';

    $falta = $this->faltaElegir;
    $conFormulario = in_array($tipo_producto, ['bobinas', 'confeccion-dpk'], true);
    $carpeta = $tipo_producto === 'confeccion-dpk' ? 'confeccion-dpk' : 'bobinas';

    // Con mas de un producto cada bloque lleva su nombre: "producto 1 · Opción 2".
    $esExtra = $uidProducto !== null;
    $numerado = $esExtra || $productosExtra !== [];
    $nombre = $esExtra ? $etiquetaProducto : ($numerado ? $this->listaProductos[0]['etiqueta'] : '');
@endphp

<div class="flex items-center justify-between gap-4 px-6 {{ $esExtra ? 'pt-5' : 'pt-8' }} pb-5">
    <h2 class="text-[16px] leading-[normal] font-medium text-black">
        Datos de {{ $numerado ? Str::lcfirst($nombre) : 'producto' }}
    </h2>

    @if ($esExtra)
        {{-- Lo saca la cotizacion, que es la que tiene la lista de productos. --}}
        <button
            type="button"
            wire:click="$dispatch('quitar-producto', { uid: @js($uidProducto) })"
            wire:confirm="¿Quitar {{ Str::lcfirst($nombre) }} de la cotización?"
            class="flex items-center gap-1.5 text-sm text-slate-400 hover:text-red-600"
        >
            <x-icon name="trash-2" class="h-4 w-4" />
            Quitar producto
        </button>
    @endif
</div>
<hr class="border-slate-100" />

<div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
    <x-campo.select
        label="Tipo de producto"
        modelo="tipo_producto"
        :opciones="Form::TIPOS_PRODUCTO"
        vacio=""
        live
        requerido
        :deshabilitado="(bool) $falta"
        :ayuda="$falta ? 'Elegí primero '.$falta : null"
    />

    @if ($conFormulario && ! $falta)
        {{-- El producto es propio del cliente y va pegado al tipo de producto. --}}
        @include('livewire.cotizaciones.tipos.bobinas.producto')
    @endif

    @if ($tipo_producto === 'bobinas' && ! $falta)
        {{-- Cuantas laminas lleva el producto: define cuantos materiales se cargan. --}}
        <x-campo.select label="Laminado" modelo="bobinas.laminado" :opciones="Form::LAMINADOS" vacio="" live requerido />

        @include('livewire.cotizaciones.tipos.bobinas.identificacion')
    @elseif ($tipo_producto === 'confeccion-dpk' && ! $falta)
        @include('livewire.cotizaciones.tipos.confeccion-dpk.identificacion')
    @endif
</div>

@if ($conFormulario && ! $falta)
    @if ($tipo_producto === 'confeccion-dpk')
        <hr class="border-slate-100" />
        @include('livewire.cotizaciones.tipos.confeccion-dpk.accesorios')
    @endif

    <hr class="border-slate-100" />
    @include('livewire.cotizaciones.tipos.bobinas.materiales')

    <hr class="border-slate-100" />
    @include('livewire.cotizaciones.tipos.bobinas.impresion')

    <h2 class="{{ $tituloSeccion }}">Forma de entrega</h2>
    <hr class="border-slate-100" />
    @include('livewire.cotizaciones.tipos.bobinas.forma-entrega')

    <h2 class="{{ $tituloSeccion }}">Condiciones de pago</h2>
    <hr class="border-slate-100" />
    @include('livewire.cotizaciones.tipos.bobinas.pagos')
@elseif (! $this->bloqueado)
    <div class="px-6 pb-10">
        <p class="text-sm text-slate-400">
            Campos de {{ Form::TIPOS_PRODUCTO[$tipo_producto] }}: pendientes de definir.
        </p>
    </div>
@endif
