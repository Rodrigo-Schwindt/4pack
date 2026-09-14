@php
    // Como en la maqueta: cada dato en su casilla, pero armado con lo cargado en Datos y Costos (solo lectura).
    $casilla = 'h-10 min-w-0 flex-1 cursor-default rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none';
    $etiqueta = 'w-[130px] shrink-0 text-sm text-slate-500';
    $c = $this->condicionesDeVenta;
    $aprobada = $guardada?->estado === \App\Models\Cotizacion::APROBADA;

    $filas = [
        ['materiales', 'Materiales', 'Bopp Mate de 20 mic + Pe Blanco de 45 mic'],
        ['anchos', 'Anchos de bobina', '580 mm Buje 3´´'],
        ['paso', 'Paso', '580 mm'],
        ['impresion', 'Impresión', '8 colores'],
        ['laminacion', 'Laminación', 'Libre de solventes Apto alimentos'],
        ['cantidad', 'Cantidad', '2.146 kg +/- 10%'],
        ['precio', 'Precio', 'U$S 7,99 por kg + IVA'],
    ];
@endphp

<section class="rounded-lg bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
        <div class="flex items-center gap-3">
            <h2 class="text-[16px] leading-[normal] font-medium text-black">Cotización {{ $numero }}</h2>
            @if ($aprobada)
                <span class="rounded-md bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                    Aprobada el {{ $guardada->aprobada_en->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>

        <button
            type="button"
            wire:click="aprobar"
            wire:loading.attr="disabled"
            @disabled($aprobada)
            class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-8 text-sm font-medium text-white hover:bg-[#174567] disabled:cursor-default disabled:opacity-60"
        >
            <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="aprobar" />
            {{ $aprobada ? 'Aprobada' : 'Aprobar' }}
        </button>
    </div>
    <hr class="border-slate-100" />

    <div class="flex flex-col gap-4 px-6 py-6">
        <div class="flex items-center gap-4">
            <span class="{{ $etiqueta }}">Tipo de producto</span>
            <input readonly value="{{ $cotizacion['tipo_producto'] ?? '' }}" placeholder="Bobina" aria-label="Tipo de producto" class="{{ $casilla }} w-[240px] max-w-[40%] flex-none" />
            <input readonly value="{{ $cotizacion['producto'] ?? '' }}" placeholder="Producto" aria-label="Producto" class="{{ $casilla }}" />
        </div>

        @foreach ($filas as [$clave, $titulo, $ejemplo])
            <div class="flex items-center gap-4">
                <span class="{{ $etiqueta }}">{{ $titulo }}</span>
                <input readonly value="{{ $cotizacion[$clave] ?? '' }}" placeholder="{{ $ejemplo }}" aria-label="{{ $titulo }}" class="{{ $casilla }}" />
            </div>
        @endforeach

        @foreach ($entregas as $indice => $entrega)
            <div wire:key="cotizacion-entrega-{{ $indice }}" class="flex items-center gap-4">
                <span class="{{ $etiqueta }}">Entrega {{ $indice + 1 }}</span>
                <input readonly value="{{ $entrega['texto_lugar'] ?? '' }}" placeholder="Zona" aria-label="Lugar de la entrega {{ $indice + 1 }}" class="{{ $casilla }}" />
                <input readonly value="{{ $entrega['texto_cantidad'] ?? '' }}" placeholder="Cantidad" aria-label="Cantidad de la entrega {{ $indice + 1 }}" class="{{ $casilla }}" />
                <input readonly value="{{ $entrega['texto_direccion'] ?? '' }}" placeholder="Dirección" aria-label="Dirección de la entrega {{ $indice + 1 }}" class="{{ $casilla }}" />
            </div>
        @endforeach

        <p class="text-xs text-slate-400">Los datos se arman solos con lo cargado en Datos y Costos; lo que falta se ve en gris.</p>
    </div>
</section>

<section class="mt-6 rounded-lg bg-white px-6 py-5 shadow-sm">
    <h2 class="mb-4 text-[16px] leading-[normal] font-medium text-black">Condiciones de venta</h2>

    {{-- Texto fijo de la empresa con las partes que salen de la cotizacion; los plazos se administran en Ajustes. --}}
    <div class="flex flex-col gap-1 text-sm leading-relaxed text-slate-500">
        <p>Plazo de entrega: {{ $c['plazo'] }} días a partir de recibida la OC y polímeros en planta</p>
        <p>Lugar de entrega: Transporte {{ $c['lugar'] }}</p>
        <p>Vencimiento de los materiales: {{ $c['vencimiento'] }} meses de recibido</p>
        <p>Tiempo máximo de reclamos: {{ $c['reclamos'] }} días de recibido los materiales</p>
        <p>
            Forma de Pago: Transferencia bancaria a los {{ $c['dias_ff'] }} días ff {{ $c['fecha_pago'] }}. Por favor tener en cuenta que las
            Facturas serán emitidas en Dólares y se pesificarán sólo a efecto de la liquidación del IVA según Banco Nación
            Vendedor del día anterior y se tomará el tipo de cambio al momento del efectivo pago realizándose la
            correspondiente Nota de Débito y/o Crédito. Polímeros: a cargo del cliente.
        </p>
        <p>Vigencia de Cotización: {{ $c['vigencia'] }} días de corrido recibido el presente mail.</p>

        <p class="mt-4">Av. Otto Bemberg 4410, B1885 Guillermo Hudson, Buenos Aires +54 11 4578-1864 /fernando@4pack.com</p>
    </div>
</section>
