@php
    use App\Livewire\Cotizaciones\Form;

    $opciones = Form::OPCIONES;
    $opciones['dias_ff'] = $diasFf ?: $opciones['dias_ff'];
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
            @if ($this->accionPrincipal === 'Enviar pedido')
                {{-- Guarda la OC y pasa la cotizacion a Finalizada. --}}
                @php $enviado = $guardada?->estado === \App\Models\Cotizacion::FINALIZADA; @endphp
                <button
                    type="button"
                    wire:click="enviarPedido"
                    wire:loading.attr="disabled"
                    @disabled($enviado)
                    title="{{ $enviado ? 'La cotización ya está finalizada' : 'Guarda la OC y pasa la cotización a Finalizada' }}"
                    class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white hover:bg-[#174567] disabled:cursor-default disabled:opacity-60"
                >
                    <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="enviarPedido" />
                    {{ $enviado ? 'Pedido enviado' : 'Enviar pedido' }}
                </button>
            @else
                <button
                    type="button"
                    disabled
                    title="Próximamente"
                    class="flex h-10 cursor-default items-center rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white"
                >
                    {{ $this->accionPrincipal }}
                </button>
            @endif
            <button
                type="button"
                wire:click="guardar"
                wire:loading.attr="disabled"
                class="flex h-10 items-center gap-2 rounded-md border border-[#1c5480] bg-white px-5 text-sm font-medium text-[#1c5480] hover:bg-slate-50 disabled:opacity-70"
            >
                <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="guardar" />
                Guardar
            </button>
        </div>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    @if ($errors->hasAny(['cliente_id', 'vendedor_id', 'fecha']))
        <p class="mb-4 text-sm text-red-600">Para guardar hace falta el cliente, el vendedor y la fecha.</p>
    @endif

    @error('oc.numero')
        <p class="mb-4 text-sm text-red-600">Para enviar el pedido falta el N° OC (solapa Orden de Compra).</p>
    @enderror

    @php
        // Bobinas y DPK comparten casi todo: el DPK solo tiene vistas propias donde cambia.
        $conFormulario = in_array($tipo_producto, ['bobinas', 'confeccion-dpk'], true);
        $carpeta = $tipo_producto === 'confeccion-dpk' ? 'confeccion-dpk' : 'bobinas';
    @endphp

    @php
        // Los productos que se sumaron con Duplicar / Nuevo producto.
        $hayExtras = $productosExtra !== [];
        $tituloProducto = 'mb-3 text-[16px] leading-[normal] font-medium text-black';
        // "Producto 1 · Opción 2": uno por formulario, en orden.
        $lista = $this->listaProductos;
    @endphp

    @if ($solapa !== 'datos')
        @if ($solapa === 'costos' && $conFormulario)
            @if ($hayExtras)
                <h2 class="{{ $tituloProducto }}">{{ $lista[0]['etiqueta'] }} · {{ Form::TIPOS_PRODUCTO[$tipo_producto] }}</h2>
            @endif
            @include('livewire.cotizaciones.tipos.'.$carpeta.'.costos')

            @foreach ($productosExtra as $indice => $extra)
                <div wire:key="costos-extra-{{ $extra['uid'] }}" class="mt-8">
                    <h2 class="{{ $tituloProducto }}">{{ $lista[$indice + 1]['etiqueta'] }} · {{ Form::TIPOS_PRODUCTO[$extra['tipo_producto']] ?? 'Sin tipo de producto' }}</h2>
                    @livewire('cotizaciones.form', ['producto' => $extra, 'generales' => $this->generales, 'solapaProducto' => 'costos', 'numeroProducto' => $indice + 2, 'etiquetaProducto' => $lista[$indice + 1]['etiqueta']], key('producto-'.$extra['uid'].'-costos-'.$this->claveGenerales.'-'.$lista[$indice + 1]['etiqueta']))
                </div>
            @endforeach

            @if ($hayExtras)
                @include('livewire.cotizaciones.tipos._resumen')
            @endif
        @elseif ($solapa === 'cotizacion' && $conFormulario)
            @include('livewire.cotizaciones.tipos.bobinas.cotizacion')
            @include('livewire.cotizaciones.tipos.bobinas.condiciones-venta')
        @elseif ($solapa === 'orden-de-compra' && $conFormulario)
            @include('livewire.cotizaciones.tipos.bobinas.orden-compra')
        @elseif ($solapa === 'entrega' && $conFormulario)
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
            <x-campo.select label="Cliente" modelo="cliente_id" :opciones="$clientes" live requerido />
            <x-campo.select label="Categoría" modelo="categoria" :opciones="Form::OPCIONES['categorias']" />
            <x-campo.texto label="Descuento" modelo="ajuste_categoria" tipo="number" paso="0.01" modificador="live.blur" />

            {{-- Vendedor y su comisión extra comparten la cuarta columna. --}}
            <div class="flex items-end gap-3">
                <x-campo.select label="Vendedor" modelo="vendedor_id" :opciones="$vendedores->pluck('nombre', 'id')" live requerido class="flex-1" />
                <x-campo.texto label="Comisión" modelo="ajuste_vendedor" tipo="number" paso="0.01" modificador="live.blur" class="w-[88px] shrink-0" />
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

        @include('livewire.cotizaciones.tipos._producto')
    </section>

    @foreach ($productosExtra as $indice => $extra)
        <div wire:key="datos-extra-{{ $extra['uid'] }}" class="mt-6">
            @livewire('cotizaciones.form', ['producto' => $extra, 'generales' => $this->generales, 'solapaProducto' => 'datos', 'numeroProducto' => $indice + 2, 'etiquetaProducto' => $lista[$indice + 1]['etiqueta']], key('producto-'.$extra['uid'].'-datos-'.$this->claveGenerales.'-'.$lista[$indice + 1]['etiqueta']))
        </div>
    @endforeach
    @endif

    @if ($solapa === 'datos' && ! $this->bloqueado)
        @php
            // Se duplica el ultimo producto de la lista, y solo si ya tiene tipo.
            $ultimo = $productosExtra === [] ? $tipo_producto : (end($productosExtra)['tipo_producto'] ?? '');
        @endphp

        <div class="mt-4 flex items-center justify-end gap-3">
            <button
                type="button"
                wire:click="duplicarProducto"
                wire:loading.attr="disabled"
                @disabled($ultimo === '')
                title="{{ $ultimo === '' ? 'Elegí el tipo del último producto para poder duplicarlo' : 'Agrega abajo una copia del último producto' }}"
                class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white hover:bg-[#174567] disabled:cursor-default disabled:opacity-60"
            >
                <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="duplicarProducto" />
                Duplicar Cotización
            </button>
            <button
                type="button"
                wire:click="nuevoProducto"
                wire:loading.attr="disabled"
                title="Agrega abajo un producto nuevo, de cualquier tipo"
                class="flex h-10 items-center gap-2 rounded-md border border-[#1c5480] bg-white px-5 text-sm font-medium text-[#1c5480] hover:bg-slate-50 disabled:opacity-60"
            >
                <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="nuevoProducto" />
                Nuevo Producto
            </button>
        </div>
    @endif
</div>
