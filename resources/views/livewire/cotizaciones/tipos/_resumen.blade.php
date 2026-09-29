{{--
    La cotizacion completa: un renglon por producto y el total general. Cada
    producto se vende en su unidad (bobinas por kg, DPK por millar), asi que
    lo que se suma es el importe total de cada uno.
--}}
@php
    $filas = $this->resumenProductos;
    $usd = fn ($valor) => \App\Support\Numero::usd($valor, prefijo: 'U$S ');
    $total = collect($filas)->sum(fn (array $fila) => $fila['total'] ?? 0);
    $pesoTotal = collect($filas)->sum('peso');
    $incompletos = collect($filas)->filter(fn (array $fila) => $fila['total'] === null)->pluck('numero');
@endphp

<section class="mt-6 rounded-lg bg-white shadow-sm">
    <h2 class="px-6 pt-5 pb-5 text-[16px] leading-[normal] font-medium text-black">Total de la cotización</h2>
    <hr class="border-slate-100" />

    <div class="overflow-x-auto scroll-sutil px-6 py-6">
        <table class="w-full min-w-[720px]">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                    <th class="px-3 py-3 text-left font-medium">Producto</th>
                    <th class="px-3 py-3 text-left font-medium">Tipo</th>
                    <th class="px-3 py-3 text-right font-medium">Cantidad</th>
                    <th class="px-3 py-3 text-right font-medium">Precio al contado</th>
                    <th class="px-3 py-3 text-right font-medium">Total al contado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr wire:key="resumen-{{ $fila['numero'] }}" class="border-b border-slate-100">
                        <td class="px-3 py-3 text-sm text-slate-800">
                            <span class="text-slate-500">{{ $fila['etiqueta'] }}:</span> {{ $fila['producto'] ?: 'Sin producto elegido' }}
                        </td>
                        <td class="px-3 py-3 text-sm text-slate-600">{{ $fila['tipo'] }}</td>
                        <td class="px-3 py-3 text-right text-sm text-slate-600">{{ $fila['cantidad'] }}</td>
                        <td class="px-3 py-3 text-right text-sm text-slate-600">
                            {{ $fila['unitario'] === null ? '-' : $usd($fila['unitario']).' / '.$fila['unidad'] }}
                        </td>
                        <td class="px-3 py-3 text-right text-sm text-slate-800">{{ $usd($fila['total']) }}</td>
                    </tr>
                @endforeach
                <tr class="border-t-2 border-slate-200 font-medium">
                    <td class="px-3 py-3 text-sm text-slate-900" colspan="2">Total general ({{ count($filas) }} productos)</td>
                    <td class="px-3 py-3 text-right text-sm text-slate-900">{{ \App\Support\Numero::corto($pesoTotal) }} kg</td>
                    <td class="px-3 py-3"></td>
                    <td class="px-3 py-3 text-right text-sm text-slate-900">{{ $usd($total) }}</td>
                </tr>
            </tbody>
        </table>

        @if ($incompletos->isNotEmpty())
            <p class="mt-3 text-xs text-amber-700">
                Sin precio todavía: producto {{ $incompletos->implode(', ') }}. El total suma solo los que ya tienen el costo completo.
            </p>
        @endif
    </div>
</section>
