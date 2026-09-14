<div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
    <x-campo.select label="Impresión" modelo="bobinas.impresion" :opciones="$opciones['si_no']" live requerido />
    <div class="flex flex-col gap-5">
        <x-campo.select label="Reprint" modelo="bobinas.reprint" :opciones="$opciones['si_no']" />
        {{-- D8 de la planilla: si no se refila, el bloque Refilado del tab de Costos queda en cero. --}}
        <x-campo.select label="Refilado" modelo="bobinas.refilado" :opciones="$opciones['si_no']" />
    </div>

    <div class="flex flex-col gap-5">
        <x-campo.select label="Contiene Líquido" modelo="bobinas.contiene_liquido" :opciones="$opciones['si_no']" />
        <x-campo.select label="Solvente" modelo="bobinas.solvente" :opciones="$opciones['si_no']" />
        <x-campo.select label="Laminación" modelo="bobinas.laminacion" :opciones="$opciones['laminaciones']" />
    </div>

    <div class="flex flex-col gap-5">
        {{-- Los cuatro campos alimentan el total de la derecha, asi que sincronizan al salir. --}}
        <x-campo.texto label="Diseños" modelo="bobinas.disenos" tipo="number" paso="1" modificador="live.blur" />
        <x-campo.texto label="Variedades" modelo="bobinas.variedades" tipo="number" paso="1" modificador="live.blur" />
        <x-campo.texto label="Cambios" modelo="bobinas.cambios" tipo="number" paso="1" modificador="live.blur" />

        <div class="flex flex-col gap-1.5">
            <div class="flex items-end gap-3">
                <x-campo.texto label="Colores" modelo="bobinas.colores" tipo="number" paso="1" modificador="live.blur" class="flex-1" />

                {{-- Resultado de (diseños x colores) + (variedades x cambios), en gris como el resto de los calculados. --}}
                <output
                    for="bobinas.disenos bobinas.variedades bobinas.cambios bobinas.colores"
                    class="flex h-10 w-[72px] shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-sm text-slate-500"
                >{{ $this->coloresTotal }}</output>
            </div>

            @if ($ayuda = $this->ayudaColores())
                <p class="text-xs text-slate-400">{{ $ayuda }}</p>
            @endif
        </div>

        <x-campo.texto label="% Impreso" modelo="bobinas.porcentaje_impreso" tipo="number" paso="0.01" modificador="live.blur" />
        <x-campo.texto label="Blanco %" modelo="bobinas.blanco" tipo="number" paso="0.01" modificador="live.blur" />
        <x-campo.texto label="Bonificación polímeros %" modelo="bobinas.bonifica_polimeros" tipo="number" paso="0.01" modificador="live.blur" placeholder="0" />
    </div>
</div>
