{{-- Las tres filas de la maqueta: la primera al contado, las otras dos a plazo. --}}
@php
    $ayudaPago = $this->calculoRentabilidad === null ? 'Se completa cuando el tab Costos tenga el valor al contado.' : null;
@endphp

{{-- items-start: la ayuda bajo "Costo total" hace mas alta la fila y sin esto los otros campos quedan chuecos. --}}
<div class="grid items-start gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
    <x-campo.select label="Valor por Kgrs al contado" modelo="pagos.0.valor_kgrs" :opciones="$opciones['si_no']" />
    <x-campo.texto label="Costo total (U$S x kg)" modelo="pagos.0.costo_total" calculado :ayuda="$ayudaPago" />
    <div class="hidden xl:block"></div>
    <div class="hidden xl:block"></div>

    @foreach ([1, 2] as $fila)
        <x-campo.select label="Valor por Kgrs a" :modelo="'pagos.'.$fila.'.valor_kgrs'" :opciones="$opciones['si_no']" />
        <div class="flex items-start gap-3">
            {{-- Live: los dias definen el % de financiacion (AC) y el costo total. --}}
            <x-campo.select label="Días FF" :modelo="'pagos.'.$fila.'.dias_ff'" :opciones="$opciones['dias_ff']" live class="flex-1" />
            <x-campo.texto label="AC %" :modelo="'pagos.'.$fila.'.ac'" calculado class="w-[72px] shrink-0" />
        </div>
        <x-campo.texto label="Financiación bancaria (U$S x kg)" :modelo="'pagos.'.$fila.'.financiacion'" calculado />
        <x-campo.texto label="Costo total (U$S x kg)" :modelo="'pagos.'.$fila.'.costo_total'" calculado :ayuda="$ayudaPago" />
    @endforeach
</div>
