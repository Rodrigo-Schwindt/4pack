@php
    // Sin impresion los datos tecnicos no se pueden completar todavia.
    $trabado = $this->sinImpresion;
@endphp

<div class="px-6 py-6">
    @if ($trabado)
        <p class="mb-5 text-sm text-slate-500">
            Poné <span class="font-medium text-slate-700">Impresión</span> en Si para cargar los datos técnicos.
        </p>
    @endif

    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="flex flex-col gap-5">
            {{-- Ancho (cm) x Módulos Ancho (cm) --}}
            <x-campo.texto label="Ancho refilado (cm)" modelo="bobinas.ancho_refilado" calculado />

            {{-- Ancho refilado + un valor del sistema, editable desde el icono. --}}
            <div class="flex flex-col gap-1.5">
                <label for="bobinas.ancho_lamina" class="text-sm text-slate-600">Ancho lámina (cm)</label>

                @if ($editandoExtra)
                    <div class="flex items-center gap-2">
                        <input
                            autofocus
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model="nuevoExtra"
                            wire:keydown.enter.prevent="guardarExtra"
                            wire:keydown.escape="cancelarExtra"
                            aria-label="Valor que se suma al ancho refilado"
                            class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                        />
                        <button type="button" wire:click="guardarExtra" aria-label="Guardar valor" class="rounded-md bg-[#1c5480] p-2 text-white hover:bg-[#174567]">
                            <x-icon name="check" class="h-4 w-4" />
                        </button>
                        <button type="button" wire:click="cancelarExtra" aria-label="Cancelar" class="rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50">
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                    <p class="text-xs text-slate-400">Se suma al ancho refilado en todas las cotizaciones.</p>
                    @error('nuevoExtra') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @else
                    <div class="flex items-center gap-2">
                        <input
                            id="bobinas.ancho_lamina"
                            wire:model="bobinas.ancho_lamina"
                            readonly
                            class="h-10 w-full cursor-default rounded-md border border-slate-200 bg-slate-50 px-3 text-sm text-slate-400"
                        />
                        <button
                            type="button"
                            wire:click="abrirExtra"
                            @disabled($trabado)
                            title="Ancho refilado + {{ $this->anchoLaminaExtra }} — clic para cambiar el valor"
                            aria-label="Cambiar el valor que se suma al ancho refilado"
                            class="rounded-md border border-slate-200 p-2 {{ $trabado ? 'cursor-default text-slate-300' : 'text-slate-500 hover:bg-slate-50 hover:text-[#1c5480]' }}"
                        >
                            <x-icon name="sliders-horizontal" class="h-4 w-4" />
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-5">
            <x-campo.texto label="Impresión Scrap (cm)" modelo="bobinas.impresion_scrap" :deshabilitado="$trabado" />
            <x-campo.texto label="Laminación Scrap (cm)" modelo="bobinas.laminacion_scrap" :deshabilitado="$trabado" />
            <x-campo.texto label="Bilaminación Scrap (cm)" modelo="bobinas.bilaminacion_scrap" :deshabilitado="$trabado" />
        </div>

        <div class="flex flex-col gap-5">
            <x-campo.texto label="U$S" modelo="bobinas.impresion_scrap_usd" calculado />
            <x-campo.texto label="U$S" modelo="bobinas.laminacion_scrap_usd" calculado />
            <x-campo.texto label="U$S" modelo="bobinas.bilaminacion_scrap_usd" calculado />
        </div>

        <x-campo.ajuste label="Mangas disponibles (cm)" grupo="mangas" modelo="bobinas.mangas" :opciones="$mangas" :creando="$creando" :campo-alta="$campoAlta" :deshabilitado="$trabado" />
    </div>
</div>
