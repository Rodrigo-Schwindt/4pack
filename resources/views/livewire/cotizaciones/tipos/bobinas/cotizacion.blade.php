@php
    // Los textos se arman con los datos cargados; el placeholder muestra el formato.
    $linea = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $etiqueta = 'w-[130px] shrink-0 text-sm text-slate-500';

    $filas = [
        ['materiales', 'Materiales', 'Bopp Mate de 20 mic + Pe Blanco 45 mic'],
        ['anchos', 'Anchos de bobina', '580 mm Buje 3´´'],
        ['paso', 'Paso', '580 mm'],
        ['impresion', 'Impresión', '8 colores con Fotocromía HD'],
        ['laminacion', 'Laminación', 'Libre de solventes Apto alimentos'],
        ['cantidad', 'Cantidad', '67.000 kg +/- 10%'],
        ['precio', 'Precio', 'U$S 7,99 por kg + IVA'],
    ];
@endphp

<section class="rounded-lg bg-white shadow-sm">
    <div class="flex items-center justify-between gap-4 px-6 py-4">
        <h2 class="text-[16px] leading-[normal] font-medium text-black">Cotización 1</h2>
        <button
            type="button"
            disabled
            title="Próximamente"
            class="flex h-10 cursor-default items-center rounded-md bg-[#1c5480] px-8 text-sm font-medium text-white"
        >
            Aprobar
        </button>
    </div>
    <hr class="border-slate-100" />

    <div class="flex flex-col gap-4 px-6 py-6">
        <div class="flex items-center gap-4">
            <label for="cotizacion.tipo_producto" class="{{ $etiqueta }}">Tipo de producto</label>
            <input id="cotizacion.tipo_producto" wire:model="cotizacion.tipo_producto" placeholder="Bobina" class="{{ $linea }} w-[240px] flex-none" />
            <input wire:model="cotizacion.producto" placeholder="Flowpack Bilaminado impreso Carrefour Caseras x 700g" class="{{ $linea }}" />
        </div>

        @foreach ($filas as [$clave, $titulo, $ejemplo])
            <div class="flex items-center gap-4">
                <label for="cotizacion.{{ $clave }}" class="{{ $etiqueta }}">{{ $titulo }}</label>
                <input id="cotizacion.{{ $clave }}" wire:model="cotizacion.{{ $clave }}" placeholder="{{ $ejemplo }}" class="{{ $linea }}" />
            </div>
        @endforeach

        @foreach ($entregas as $indice => $entrega)
            <div wire:key="cotizacion-entrega-{{ $indice }}" class="flex items-center gap-4">
                <label class="{{ $etiqueta }}">Entrega {{ $indice + 1 }}</label>
                <input wire:model="entregas.{{ $indice }}.texto_lugar" placeholder="CABA" class="{{ $linea }}" />
                <input wire:model="entregas.{{ $indice }}.texto_cantidad" placeholder="Cantidad 50.000kg" class="{{ $linea }}" />
                <input wire:model="entregas.{{ $indice }}.texto_direccion" placeholder="Dirección Av Rivadavia 1234" class="{{ $linea }}" />
            </div>
        @endforeach
    </div>
</section>

<section class="mt-6 rounded-lg bg-white px-6 py-5 shadow-sm">
    <h2 class="mb-4 text-[16px] leading-[normal] font-medium text-black">Condiciones de venta</h2>

    {{-- Texto fijo de la empresa. Tiene partes que salen de la cotizacion:
         el lugar de entrega, los dias ff y la fecha de la forma de pago. --}}
    <div class="flex flex-col gap-1 text-sm leading-relaxed text-slate-500">
        <p>Plazo de entrega: 30 días a partir de recibida la OC y polímeros en planta</p>
        <p>Lugar de entrega: Transporte CABA</p>
        <p>Vencimiento de los materiales: 12 meses de recibido</p>
        <p>Tiempo máximo de reclamos: 60 días de recibido los materiales</p>
        <p>
            Forma de Pago: Transferencia bancaria a los 30 días ff Agosto 06 de 2026 Por favor tener en cuenta que las
            Facturas será emitidas en Dólares y se pesificaran sólo a efecto de la liquidación del IVA según Banco Nación
            Vendedor del día anterior y se tomará el tipo de cambio al momento del efectivo pago realizándose la
            correspondiente Nota de Débito y/o Crédito. Polímeros: a cargo del cliente.
        </p>
        <p>Vigencia de Cotización: 2 días de corrido recibido el presente mail.</p>

        <p class="mt-4">Av. Otto Bemberg 4410, B1885 Guillermo Hudson, Buenos Aires +54 11 4578-1864 /fernando@4pack.com</p>
    </div>
</section>
