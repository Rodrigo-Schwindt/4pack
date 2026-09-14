@props(['titulo' => null, 'columnas', 'filas', 'detalle' => 1])

{{-- Tabla de salida del tab de Costos: la columna $detalle va a la izquierda y el resto alineado a la derecha. --}}
@if ($titulo !== null)
    <h2 class="px-6 pt-8 pb-5 text-[16px] leading-[normal] font-medium text-black">{{ $titulo }}</h2>
    <hr class="border-slate-100" />
@endif

<div class="overflow-x-auto scroll-sutil px-6 py-6">
    <table class="w-full min-w-[860px]">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                @foreach ($columnas as $indice => $columna)
                    <th class="px-3 py-3 font-medium {{ $indice === $detalle ? 'text-left' : 'text-right' }}">{{ $columna }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $indiceFila => $fila)
                <tr wire:key="fila-{{ $indiceFila }}" class="border-b border-slate-100 last:border-0">
                    @foreach ($fila as $indice => $celda)
                        <td class="px-3 py-4 text-sm {{ $indice === $detalle ? 'text-left text-slate-800' : 'text-right text-slate-600' }}">
                            {{ $celda }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
