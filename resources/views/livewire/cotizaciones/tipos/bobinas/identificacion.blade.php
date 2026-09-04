@php
    $chico = 'rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50';
    $altaCampo = 'h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
@endphp

{{-- Completa la fila del tipo de producto: medidas y producto. --}}
<x-campo.texto label="Ancho (cm)" modelo="bobinas.ancho" tipo="number" paso="0.01" modificador="live.blur" />
<x-campo.texto label="Paso (cm)" modelo="bobinas.paso" />
<x-campo.texto label="Módulos Desarrollo (cm)" modelo="bobinas.modulos_desarrollo" />

{{-- Producto: se carga al vuelo y queda guardado solo para este cliente. --}}
<div class="flex flex-col gap-1.5">
    <label for="bobinas.producto_id" class="text-sm text-slate-600">Producto</label>

    @if ($creando === 'producto')
        <div class="flex items-center gap-2">
            <input
                id="bobinas.producto_id"
                autofocus
                wire:model="nuevoValor"
                wire:keydown.enter.prevent="guardarAlta"
                wire:keydown.escape="cancelarAlta"
                placeholder="Nuevo producto"
                class="{{ $altaCampo }}"
            />
            <button type="button" wire:click="guardarAlta" aria-label="Guardar producto" class="rounded-md bg-[#1c5480] p-2 text-white hover:bg-[#174567]">
                <x-icon name="check" class="h-4 w-4" />
            </button>
            <button type="button" wire:click="cancelarAlta" aria-label="Cancelar" class="{{ $chico }}">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @else
        <div class="flex items-center gap-2">
            <div class="relative flex-1">
                <select
                    id="bobinas.producto_id"
                    wire:model="bobinas.producto_id"
                    class="h-10 w-full appearance-none rounded-md border border-slate-200 bg-white pr-9 pl-3 text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                >
                    <option value="">-</option>
                    @foreach ($productos as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
                <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
            </div>

            <button type="button" wire:click="abrirAlta('producto')" title="Agregar producto" aria-label="Agregar producto" class="{{ $chico }} hover:text-[#1c5480]">
                <x-icon name="plus" class="h-4 w-4" />
            </button>

            {{-- Los productos no tienen ABM propio: se borran desde aca. --}}
            <button
                type="button"
                wire:click="eliminarProducto"
                wire:confirm="¿Eliminar este producto del cliente?"
                @disabled(! $bobinas['producto_id'])
                title="Eliminar el producto seleccionado"
                aria-label="Eliminar el producto seleccionado"
                class="{{ $chico }} {{ $bobinas['producto_id'] ? 'hover:text-red-600' : 'cursor-default opacity-40' }}"
            >
                <x-icon name="trash-2" class="h-4 w-4" />
            </button>
        </div>
    @endif

    @error('nuevoValor') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<x-campo.texto label="Módulos Ancho (cm)" modelo="bobinas.modulos_ancho" tipo="number" paso="0.01" modificador="live.blur" />
<div class="hidden xl:block"></div>

{{-- Desarrollo toma los valores de mangas cargados en Configuración > Ajustes. --}}
<x-campo.ajuste label="Desarrollo (50)" grupo="mangas" modelo="bobinas.desarrollo" :opciones="$mangas" :creando="$creando" />
