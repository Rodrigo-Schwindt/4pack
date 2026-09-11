@php
    use App\Livewire\Cotizaciones\Form;

    $opciones = Form::OPCIONES;
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $tituloSeccion = 'px-6 pt-8 pb-5 text-[16px] leading-[normal] font-medium text-black';

    $falta = $this->faltaElegir;
    $avisoSolapa = $falta
        ? 'Elegí '.$falta.' y el tipo de producto para habilitar esta solapa'
        : 'Elegí el tipo de producto para habilitar esta solapa';

    $estiloSolapa = function (string $clave) {
        $activa = $this->solapa === $clave;
        $trabada = $this->bloqueado && $clave !== 'datos';

        return 'border-b-2 px-1 pb-2 text-sm '
            .match (true) {
                $activa => 'border-[#1c5480] text-[#1c5480] font-medium',
                $trabada => 'border-slate-200 text-slate-300 cursor-default',
                default => 'border-slate-200 text-slate-400 hover:text-slate-600',
            };
    };
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('cotizaciones.index') }}" wire:navigate aria-label="Volver al listado" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-[#101828] text-[24px] font-bold leading-[32px]">{{ $numero }}</h1>
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <nav class="flex flex-wrap items-center gap-8">
            @foreach (Form::SOLAPAS as $clave => $titulo)
                <button
                    type="button"
                    wire:click="verSolapa('{{ $clave }}')"
                    @disabled($this->bloqueado && $clave !== 'datos')
                    title="{{ $this->bloqueado && $clave !== 'datos' ? $avisoSolapa : '' }}"
                    class="{{ $estiloSolapa($clave) }}"
                >
                    {{ $titulo }}
                </button>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <button
                type="button"
                disabled
                title="Próximamente"
                class="flex h-10 cursor-default items-center rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white"
            >
                {{ $this->accionPrincipal }}
            </button>
            <button
                type="button"
                disabled
                title="Próximamente"
                class="flex h-10 cursor-default items-center rounded-md border border-[#1c5480] bg-white px-5 text-sm font-medium text-[#1c5480]"
            >
                Guardar
            </button>
        </div>
    </div>

    @if ($solapa !== 'datos')
        @if ($solapa === 'costos' && $tipo_producto === 'bobinas')
            @include('livewire.cotizaciones.tipos.bobinas.costos')
        @elseif ($solapa === 'cotizacion' && $tipo_producto === 'bobinas')
            @include('livewire.cotizaciones.tipos.bobinas.cotizacion')
        @elseif ($solapa === 'orden-de-compra' && $tipo_producto === 'bobinas')
            @include('livewire.cotizaciones.tipos.bobinas.orden-compra')
        @elseif ($solapa === 'entrega' && $tipo_producto === 'bobinas')
            @include('livewire.cotizaciones.tipos.bobinas.entrega')
        @else
            <section class="min-h-[600px] rounded-lg bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-400">
                    {{ Form::SOLAPAS[$solapa] }} de {{ Form::TIPOS_PRODUCTO[$tipo_producto] }}: pendiente de definir.
                </p>
            </section>
        @endif
    @else
    <section class="min-h-[600px] rounded-lg bg-white shadow-sm">
        <h2 class="{{ $tituloSeccion }} pt-5">Datos generales</h2>
        <hr class="border-slate-100" />

        <div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
            {{-- Live: el cliente decide si se puede elegir el tipo y que productos se listan. --}}
            <x-campo.select label="Cliente" modelo="cliente_id" :opciones="$clientes->pluck('razon_social', 'id')" live requerido />
            <x-campo.select label="Categoría" modelo="categoria" :opciones="Form::OPCIONES['categorias']" />
            <x-campo.texto label="Ajuste" modelo="ajuste_categoria" tipo="number" paso="0.01" modificador="live.blur" />

            {{-- Vendedor y su ajuste comparten la cuarta columna. --}}
            <div class="flex items-end gap-3">
                <x-campo.select label="Vendedor" modelo="vendedor_id" :opciones="$vendedores->pluck('nombre', 'id')" live requerido class="flex-1" />
                <x-campo.texto label="Ajuste" modelo="ajuste_vendedor" tipo="number" paso="0.01" modificador="live.blur" class="w-[88px] shrink-0" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="numero" class="text-sm text-slate-600">N° de cotización</label>
                <input id="numero" value="{{ $numero }}" readonly class="{{ $campo }} cursor-default" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="fecha" class="text-sm text-slate-600">Fecha</label>
                <div class="relative">
                    <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input id="fecha" type="date" wire:model="fecha" class="{{ $campo }} campo-fecha pl-9" />
                </div>
            </div>

            <x-campo.texto label="N° Ref. Pedido Cotización" modelo="referencia" placeholder="-" />
        </div>

        <h2 class="{{ $tituloSeccion }}">Datos de producto</h2>
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

            @if ($tipo_producto === 'bobinas' && ! $falta)
                @include('livewire.cotizaciones.tipos.bobinas.identificacion')
            @endif
        </div>

        @if ($tipo_producto === 'bobinas' && ! $falta)
            <hr class="border-slate-100" />
            @include('livewire.cotizaciones.tipos.bobinas.materiales')

            <hr class="border-slate-100" />
            @include('livewire.cotizaciones.tipos.bobinas.impresion')

            <h2 class="{{ $tituloSeccion }}">Forma de entrega</h2>
            <hr class="border-slate-100" />
            @include('livewire.cotizaciones.tipos.bobinas.forma-entrega')

            <h2 class="{{ $tituloSeccion }}">Datos técnicos</h2>
            <hr class="border-slate-100" />
            @include('livewire.cotizaciones.tipos.bobinas.tecnicos')

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
    </section>
    @endif

    @if ($solapa === 'datos' && ! $this->bloqueado)
        <div class="mt-4 flex items-center justify-end gap-3">
            <button
                type="button"
                disabled
                title="Próximamente"
                class="flex h-10 cursor-default items-center rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white"
            >
                Duplicar Cotización
            </button>
            <button
                type="button"
                disabled
                title="Próximamente"
                class="flex h-10 cursor-default items-center rounded-md border border-[#1c5480] bg-white px-5 text-sm font-medium text-[#1c5480]"
            >
                Nuevo Producto
            </button>
        </div>
    @endif
</div>
