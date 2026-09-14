@php
    $rentabilidad = $this->rentabilidad;
    $costoFinal = $this->costoFinal;

    // Alto fijo de fila para que los tres bloques de rentabilidad queden alineados.
    $fila = 'flex h-[47px] items-center px-3 text-sm';
    $encabezado = 'flex h-[56px] items-center justify-center rounded-t border border-slate-100 bg-slate-50/60 px-3 text-[11px] tracking-wide text-slate-500 uppercase';
@endphp

<section class="rounded-lg bg-white shadow-sm">
    @php
        $secciones = [
            $this->seccionProveedores,
            $this->seccionImpresion,
            $this->seccionLaminacion,
            $this->seccionRefilado,
            $this->seccionOtrosCostos,
        ];
    @endphp

    @foreach ($secciones as $indice => $seccion)
        <x-tabla-costos :titulo="$seccion['titulo']" :columnas="$seccion['columnas']" :filas="$seccion['filas']" class="{{ $indice === 0 ? 'pt-5' : '' }}" />
    @endforeach

    <h2 class="px-6 pt-8 pb-5 text-[16px] leading-[normal] font-medium text-black">Rentabilidad, financiado y costo bruto</h2>
    <hr class="border-slate-100" />

    {{-- El costo bruto suma los bloques que ya se calculan: falta tela, tintas, laminacion y solventes. --}}
    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 px-6 pt-4 text-xs text-slate-500">
        <span>Estructura: <span class="font-medium text-slate-700">{{ $rentabilidad['estructura'] ?? '-' }}</span></span>
        <span>Valor por kg al contado: <span class="font-medium text-slate-700">{{ $rentabilidad['contado'] }}</span></span>
        @if ($rentabilidad['sin_margen'])
            <span class="text-amber-700">La estructura "{{ $rentabilidad['estructura'] }}" no tiene margen cargado en Variables Costos.</span>
        @endif
    </div>

    <div class="overflow-x-auto scroll-sutil px-6 py-6">
        <div class="flex min-w-[860px] items-start gap-4">
            {{-- Porcentaje suelto a la izquierda, fuera del recuadro. --}}
            <div class="w-[70px] shrink-0 pt-[56px]">
                @foreach ($rentabilidad['filas'] as $item)
                    <p class="{{ $fila }} justify-end text-slate-600">{{ $item['porcentaje'] }}</p>
                @endforeach
            </div>

            <div class="flex-1">
                <p class="{{ $encabezado }}">Rentabilidad</p>
                <div class="rounded-b border-x border-b border-slate-100">
                    @foreach ($rentabilidad['filas'] as $item)
                        <div class="flex h-[47px] items-center border-b border-slate-100 px-3 text-sm last:border-0">
                            <span class="flex-1 text-slate-800">{{ $item['detalle'] }}</span>
                            <span class="w-[110px] text-right text-slate-600">{{ $item['origen'] }}</span>
                            <span class="w-[80px] text-right text-slate-600">{{ $item['valor'] }}</span>
                            <span class="w-[130px] text-right text-slate-600">{{ $item['importe'] }}</span>
                            <span class="w-[90px] text-right text-slate-600">{{ $item['por_kg'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="w-[150px] shrink-0">
                <p class="{{ $encabezado }}">Financiado</p>
                <div class="rounded-b border-x border-b border-slate-100">
                    @foreach ($rentabilidad['filas'] as $item)
                        @if ($item['financiado'] !== '')
                            <div class="{{ $fila }} justify-end border-b border-slate-100 text-slate-600 last:border-0">
                                {{ $item['financiado'] }}
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="w-[190px] shrink-0">
                <p class="{{ $encabezado }} flex-col gap-0.5 leading-tight">
                    <span>Costo bruto</span>
                    <span class="normal-case">{{ $rentabilidad['costo_bruto'] }}</span>
                </p>
                <div class="rounded-b border-x border-b border-slate-100">
                    @foreach ($rentabilidad['filas'] as $item)
                        <div class="{{ $fila }} justify-end border-b border-slate-100 text-slate-600 last:border-0">
                            {{ $item['bruto'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <x-tabla-costos
        titulo="Costo final"
        :columnas="$costoFinal['columnas']"
        :filas="$costoFinal['filas']"
        :detalle="0"
    />
</section>
