{{--
    Tres bloques, cada uno con sus propias filas de 4: lo de la impresión
    arranca a la derecha de Reprint, abajo van los tres selects del material
    y al final, solo, el Refilado.
--}}
<div class="flex flex-col gap-5 px-6 py-6">
    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
        <x-campo.select label="Impresión" modelo="bobinas.impresion" :opciones="$opciones['si_no']" live requerido />
        <x-campo.select label="Reprint" modelo="bobinas.reprint" :opciones="$opciones['si_no']" />

        {{-- Los cuatro campos alimentan el total de la derecha, asi que sincronizan al salir. --}}
        <x-campo.texto label="Diseños" modelo="bobinas.disenos" tipo="number" paso="1" modificador="live.blur" />
        <x-campo.texto label="Variedades" modelo="bobinas.variedades" tipo="number" paso="1" modificador="live.blur" />
        <x-campo.texto label="Cambios" modelo="bobinas.cambios" tipo="number" paso="1" modificador="live.blur" />

        <div class="flex flex-col gap-1.5">
            <div class="flex items-end gap-3">
                <x-campo.texto label="Colores" modelo="bobinas.colores" tipo="number" paso="1" modificador="live.blur" class="flex-1" />

                {{--
                    Resultado de (diseños x colores) + (variedades x cambios). Se puede
                    pisar a mano: vacio muestra el calculado en gris y manda la cuenta.
                --}}
                <input
                    id="bobinas.colores_total"
                    type="number"
                    step="1"
                    min="0"
                    wire:model.live.blur="bobinas.colores_total"
                    placeholder="{{ $this->coloresTotal }}"
                    aria-label="Total de colores"
                    title="Se calcula solo; escribí un número para forzar otro total"
                    class="h-10 w-[72px] shrink-0 rounded-md border border-slate-200 px-2 text-center text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none [&:placeholder-shown]:bg-slate-50"
                />
            </div>

            @if ($ayuda = $this->ayudaColores())
                <p class="text-xs text-slate-400">{{ $ayuda }}</p>
            @endif
        </div>

        <x-campo.texto label="% Impreso" modelo="bobinas.porcentaje_impreso" tipo="number" paso="0.01" modificador="live.blur" />
        <x-campo.texto label="Blanco %" modelo="bobinas.blanco" tipo="number" paso="0.01" modificador="live.blur" />
        <x-campo.texto label="Bonificación polímeros %" modelo="bobinas.bonifica_polimeros" tipo="number" paso="0.01" modificador="live.blur" placeholder="0" />
    </div>

    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
        <x-campo.select label="Contiene Líquido" modelo="bobinas.contiene_liquido" :opciones="$opciones['si_no']" />
        <x-campo.select label="Solvente" modelo="bobinas.solvente" :opciones="$opciones['si_no']" />
        <x-campo.select label="Laminación" modelo="bobinas.laminacion" :opciones="$opciones['laminaciones']" />
    </div>

    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
        {{-- D8 de la planilla: si no se refila, el bloque Refilado del tab de Costos queda en cero. --}}
        <x-campo.select label="Refilado" modelo="bobinas.refilado" :opciones="$opciones['si_no']" />
    </div>
</div>
