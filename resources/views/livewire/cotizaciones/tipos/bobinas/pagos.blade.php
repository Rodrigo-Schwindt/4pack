{{-- Las tres filas de la maqueta: la primera sin financiacion y la tercera sin AC. --}}
<div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
    <x-campo.select label="Valor por Kgrs al contado" modelo="pagos.0.valor_kgrs" :opciones="$opciones['si_no']" />
    <x-campo.texto label="Costo total" modelo="pagos.0.costo_total" />
    <div class="hidden xl:block"></div>
    <div class="hidden xl:block"></div>

    <x-campo.select label="Valor por Kgrs a" modelo="pagos.1.valor_kgrs" :opciones="$opciones['si_no']" />
    <div class="flex items-end gap-3">
        <x-campo.select label="Días FF" modelo="pagos.1.dias_ff" :opciones="$opciones['dias_ff']" class="flex-1" />
        <x-campo.texto label="AC" modelo="pagos.1.ac" calculado class="w-[64px] shrink-0" />
    </div>
    <x-campo.texto label="Financiación bancaria" modelo="pagos.1.financiacion" />
    <x-campo.texto label="Costo total" modelo="pagos.1.costo_total" />

    <x-campo.select label="Valor por Kgrs al contado" modelo="pagos.2.valor_kgrs" :opciones="$opciones['si_no']" />
    <x-campo.select label="Días FF" modelo="pagos.2.dias_ff" :opciones="$opciones['dias_ff']" />
    <x-campo.texto label="Financiación bancaria" modelo="pagos.2.financiacion" />
    <x-campo.texto label="Costo total" modelo="pagos.2.costo_total" />
</div>
