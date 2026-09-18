@php
    $r = $this->calculoRentabilidad;
    $costoFinal = $this->costoFinal;
    $usd = fn ($valor, int $decimales = 2) => \App\Support\Numero::usd($valor, $decimales, prefijo: 'U$S ');
    $pct = fn ($valor) => \App\Support\Numero::corto($valor).' %';
    $envases = (float) ($bobinas['envases'] ?: 0);
    $peso = (float) ($bobinas['peso'] ?: 0);

    // Filas del resumen: [detalle, por millar, por kg, total de la cotizacion]
    $resumen = $r === null ? [] : [
        ['Costo bobina (material, impresión, laminación, refilado, scrap)', $r['costoBobinaMillar'], $r['costoBobinaMillar'] * $envases / 1000 / $peso, $r['costoBobinaMillar'] * $envases / 1000],
        ['Costo envase (confección, accesorios, cajas, flete, extras)', $r['costoEnvaseMillar'], $r['costoEnvaseMillar'] * $envases / 1000 / $peso, $r['costoEnvaseMillar'] * $envases / 1000],
        ['Costo bruto', $r['costoBrutoMillar'], $r['costoBruto'], $r['costoBrutoMillar'] * $envases / 1000],
        ['% Plus '.$pct($r['plus']).' (margen '.$pct($r['margen']).($r['categoriaPct'] > 0 ? ' + categoría '.$pct($r['categoriaPct']) : '').')', $r['plusMillar'], $r['plusKg'], $r['plusTotal']],
        ['% Comisión '.$pct($r['comision']), $r['comisionMillar'], $r['comisionKg'], $r['comisionTotal']],
        ['Ajuste '.$pct($r['ajusteManual']), $r['ajusteMillar'], $r['ajusteMillar'] * $envases / 1000 / $peso, $r['ajusteMillar'] * $envases / 1000],
        ['Polímeros (a cargo del cliente)', $r['polimerosUsd'] / $envases * 1000, $r['polimerosKg'], $r['polimerosUsd']],
    ];
@endphp

<section class="rounded-lg bg-white shadow-sm">
    @foreach ($this->seccionesDpk as $indice => $seccion)
        <x-tabla-costos :titulo="$seccion['titulo']" :columnas="$seccion['columnas']" :filas="$seccion['filas']" class="{{ $indice === 0 ? 'pt-5' : '' }}" />
    @endforeach

    <h2 class="px-6 pt-8 pb-5 text-[16px] leading-[normal] font-medium text-black">Rentabilidad y precio por millar</h2>
    <hr class="border-slate-100" />

    @if ($r === null)
        <p class="px-6 py-6 text-sm text-slate-400">Se completa cuando estén cargados el ancho, el alto, los envases, los materiales y el proveedor.</p>
    @else
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1 px-6 pt-4 text-xs text-slate-500">
            <span>Ancho {{ \App\Support\Numero::corto($bobinas['ancho']) }} cm → margen {{ $bobinas['ancho'] < 18 ? 'DPK < 18 cm' : 'DPK ≥ 18 cm' }} por {{ \App\Support\Numero::corto($peso, 0) }} kg</span>
            <span>Valor por millar al contado: <span class="font-medium text-slate-700">{{ $usd($r['contado']) }}</span></span>
            <span>Equivale a <span class="font-medium text-slate-700">{{ $usd($r['contadoKg']) }} por kg</span></span>
            @if ($r['dias'] > 0)
                <span class="text-slate-400">El contado ya incluye la comisión financiada a {{ $r['dias'] }} días, como la planilla.</span>
            @endif
        </div>

        <div class="overflow-x-auto scroll-sutil px-6 py-6">
            <table class="w-full min-w-[720px]">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                        <th class="px-3 py-3 text-left font-medium">Detalle</th>
                        <th class="px-3 py-3 text-right font-medium">Por millar</th>
                        <th class="px-3 py-3 text-right font-medium">Por kg</th>
                        <th class="px-3 py-3 text-right font-medium">Total cotización</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resumen as [$detalle, $millar, $kg, $total])
                        <tr class="border-b border-slate-100 last:border-0 {{ $loop->index === 2 ? 'font-medium' : '' }}">
                            <td class="px-3 py-3 text-sm text-slate-800">{{ $detalle }}</td>
                            <td class="px-3 py-3 text-right text-sm text-slate-600">{{ $usd($millar) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-slate-600">{{ $usd($kg) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-slate-600">{{ $usd($total) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t-2 border-slate-200 font-medium">
                        <td class="px-3 py-3 text-sm text-slate-900">Valor por millar al contado</td>
                        <td class="px-3 py-3 text-right text-sm text-slate-900">{{ $usd($r['contado']) }}</td>
                        <td class="px-3 py-3 text-right text-sm text-slate-900">{{ $usd($r['contadoKg']) }}</td>
                        <td class="px-3 py-3 text-right text-sm text-slate-900">{{ $usd($r['contado'] * $envases / 1000) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <x-tabla-costos titulo="Costo final" :columnas="$costoFinal['columnas']" :filas="$costoFinal['filas']" :detalle="0" />
</section>
