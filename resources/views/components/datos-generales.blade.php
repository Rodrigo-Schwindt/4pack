@props(['provincias', 'rubros', 'tipos', 'vendedores', 'creando' => null])

@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';

    // Bloque de datos generales, identico para prospectos y clientes.
    $textos = [
        ['nombre_comercial', 'Nombre Comercial', 'text'],
        ['razon_social', 'Razón social', 'text'],
        ['cuit', 'CUIT', 'text'],
        ['email', 'Email', 'email'],
        ['direccion', 'Dirección', 'text'],
    ];

    $catalogos = [
        ['rubros', 'Rubro', 'rubro_id', $rubros],
        ['tipos', 'Tipo', 'tipo_id', $tipos],
    ];
@endphp

<section class="rounded-lg bg-white p-6 shadow-sm">
    <h2 class="mb-6 font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Datos generales</h2>

    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($textos as [$nombre, $label, $tipo])
            <div class="flex flex-col gap-1.5">
                <label for="{{ $nombre }}" class="text-sm text-slate-600">{{ $label }}</label>
                <input id="{{ $nombre }}" type="{{ $tipo }}" wire:model="{{ $nombre }}" placeholder="-" class="{{ $campo }}" />
                @error($nombre) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endforeach

        <div class="flex flex-col gap-1.5">
            <label for="provincia" class="text-sm text-slate-600">Provincia</label>
            <select id="provincia" wire:model="provincia" class="{{ $campo }}">
                <option value="">-</option>
                @foreach ($provincias as $provincia)
                    <option value="{{ $provincia }}">{{ $provincia }}</option>
                @endforeach
            </select>
            @error('provincia') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @foreach ([['localidad', 'Localidad'], ['telefono', 'Teléfono'], ['celular', 'Celular'], ['pagina_web', 'Página web']] as [$nombre, $label])
            <div class="flex flex-col gap-1.5">
                <label for="{{ $nombre }}" class="text-sm text-slate-600">{{ $label }}</label>
                <input id="{{ $nombre }}" type="text" wire:model="{{ $nombre }}" placeholder="-" class="{{ $campo }}" />
                @error($nombre) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endforeach

        {{-- Rubro y Tipo: ademas de elegir, se puede dar de alta sin salir del formulario. --}}
        @foreach ($catalogos as [$catalogo, $label, $modelo, $opciones])
            <div class="flex flex-col gap-1.5">
                <label class="text-sm text-slate-600">{{ $label }}</label>

                @if ($creando === $catalogo)
                    <div class="flex items-center gap-2">
                        <input
                            autofocus
                            wire:model="nuevoCatalogo"
                            wire:keydown.enter.prevent="guardarCatalogo"
                            wire:keydown.escape="cancelarCatalogo"
                            placeholder="Nuevo {{ Str::lower($label) }}"
                            class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                        />
                        <button
                            type="button"
                            wire:click="guardarCatalogo"
                            wire:loading.attr="disabled"
                            aria-label="Guardar {{ Str::lower($label) }}"
                            class="rounded-md bg-[#1c5480] p-2 text-white hover:bg-[#174567] disabled:opacity-60"
                        >
                            <x-icon name="check" class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            wire:click="cancelarCatalogo"
                            aria-label="Cancelar"
                            class="rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50"
                        >
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                    @error('nuevoCatalogo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @else
                    <div class="flex items-center gap-2">
                        <select wire:model="{{ $modelo }}" class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none">
                            <option value="">-</option>
                            @foreach ($opciones as $opcion)
                                <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
                            @endforeach
                        </select>
                        <button
                            type="button"
                            wire:click="abrirCatalogo('{{ $catalogo }}')"
                            title="Agregar {{ Str::lower($label) }}"
                            aria-label="Agregar {{ Str::lower($label) }}"
                            class="rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 hover:text-[#1c5480]"
                        >
                            <x-icon name="plus" class="h-4 w-4" />
                        </button>
                    </div>
                    @error($modelo) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @endif
            </div>
        @endforeach

        <div class="flex flex-col gap-1.5">
            <label for="vendedor_id" class="text-sm text-slate-600">Vendedor</label>
            <select id="vendedor_id" wire:model="vendedor_id" class="{{ $campo }}">
                <option value="">-</option>
                @foreach ($vendedores as $vendedor)
                    <option value="{{ $vendedor->id }}">{{ $vendedor->nombre }}</option>
                @endforeach
            </select>
            @error('vendedor_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col gap-1.5 md:col-span-1 xl:col-span-3">
            <label for="observaciones" class="text-sm text-slate-600">Observaciones</label>
            <input id="observaciones" wire:model="observaciones" placeholder="-" class="{{ $campo }}" />
            @error('observaciones') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</section>
