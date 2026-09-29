@php
    // Va una sola vez, debajo de todos los productos de la cotizacion.
    $c = $this->condicionesDeVenta;
@endphp

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
