@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Gestión de insumos</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Catálogo de Insumos</h2>

            <button
                type="button"
                wire:click="nuevo"
                class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nuevo Insumo
            </button>
        </div>

        <div class="relative mb-5">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                type="search"
                wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por insumo..."
                aria-label="Buscar por insumo"
                class="h-11 w-full rounded-md border border-slate-200 bg-white pr-3 pl-9 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
            />
        </div>

        <div class="overflow-x-auto scroll-sutil">
            <table class="w-full min-w-[560px] text-left">
                <thead>
                    <tr class="bg-slate-50 text-[11px] tracking-wide text-slate-500 uppercase">
                        <th class="rounded-l-md px-6 py-3 font-medium">Insumo</th>
                        <th class="w-24 rounded-r-md px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($editando === 0)
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <td class="px-6 py-3">
                                <div class="flex flex-wrap gap-3">
                                    <input autofocus wire:model="nombre" placeholder="Nombre (ej. Tintas)" aria-label="Nombre del insumo" class="{{ $campo }} max-w-xs" />
                                    <input wire:model="singular" placeholder="En singular (ej. Tinta)" aria-label="Nombre en singular" class="{{ $campo }} max-w-xs" />
                                </div>
                                @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @error('singular') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" wire:click="guardar" aria-label="Guardar insumo" class="text-[#1c5480] hover:text-[#174567]">
                                        <x-icon name="check" class="h-4 w-4" />
                                    </button>
                                    <button type="button" wire:click="cancelar" aria-label="Cancelar" class="text-slate-400 hover:text-slate-600">
                                        <x-icon name="x" class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endif

                    @foreach ($insumos as $insumo)
                        @if ($editando === $insumo->id)
                            <tr wire:key="editar-{{ $insumo->id }}" class="border-b border-slate-100 bg-slate-50/60">
                                <td class="px-6 py-3">
                                    <div class="flex flex-wrap gap-3">
                                        <input autofocus wire:model="nombre" placeholder="Nombre" aria-label="Nombre del insumo" class="{{ $campo }} max-w-xs" />
                                        <input wire:model="singular" placeholder="En singular" aria-label="Nombre en singular" class="{{ $campo }} max-w-xs" />
                                    </div>
                                    @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    @error('singular') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" wire:click="guardar" aria-label="Guardar {{ $insumo->nombre }}" class="text-[#1c5480] hover:text-[#174567]">
                                            <x-icon name="check" class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="cancelar" aria-label="Cancelar" class="text-slate-400 hover:text-slate-600">
                                            <x-icon name="x" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr wire:key="insumo-{{ $insumo->id }}" class="border-b border-slate-100 last:border-0">
                                <td class="px-6 py-4">
                                    <a
                                        href="{{ route('insumos.detalle', $insumo) }}"
                                        wire:navigate
                                        class="text-sm text-slate-600 hover:text-[#1c5480] hover:underline"
                                    >
                                        {{ $insumo->nombre }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" wire:click="editar({{ $insumo->id }})" aria-label="Editar {{ $insumo->nombre }}" class="text-slate-400 hover:text-[#1c5480]">
                                            <x-icon name="square-pen" class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="eliminar({{ $insumo->id }})"
                                            wire:confirm="Se borran también sus familias, ítems y precios. ¿Eliminar {{ $insumo->nombre }}?"
                                            aria-label="Eliminar {{ $insumo->nombre }}"
                                            class="text-red-400 hover:text-red-600"
                                        >
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    @if ($insumos->isEmpty() && $editando !== 0)
                        <tr>
                            <td colspan="2" class="px-6 py-10 text-center text-sm text-slate-400">
                                {{ $buscar !== '' ? 'Ningún insumo coincide con la búsqueda.' : 'Todavía no hay insumos cargados.' }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>
