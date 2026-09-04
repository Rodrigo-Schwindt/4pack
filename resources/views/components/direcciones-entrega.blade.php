@props(['zonas', 'direcciones', 'editando' => null, 'creandoZona' => null])

@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $nombreZona = $zonas->pluck('nombre', 'id');
@endphp

{{-- Se guardan junto con el cliente: la fila vive en el componente hasta que se envia el formulario. --}}
<section class="mt-8">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Direcciones de entrega</h2>

        <button
            type="button"
            wire:click="nuevaDireccion"
            class="flex h-9 items-center gap-2 rounded-md border border-slate-200 bg-white px-4 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#1c5480]"
        >
            <x-icon name="plus" class="h-4 w-4" />
            Agregar dirección
        </button>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                        <th class="px-6 py-3 font-medium">Zona</th>
                        <th class="px-6 py-3 font-medium">Dirección</th>
                        <th class="px-6 py-3 font-medium">Código postal</th>
                        <th class="w-24 px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($direcciones as $indice => $fila)
                        <tr wire:key="direccion-{{ $indice }}" class="border-b border-slate-100 last:border-0 {{ $editando === $indice ? 'bg-slate-50/60' : '' }}">
                            @if ($editando === $indice)
                                {{-- Si la zona todavia no esta en el catalogo de flete, se crea desde aca. --}}
                                <td class="px-6 py-3">
                                    @if ($creandoZona === $indice)
                                        <div class="flex items-center gap-2">
                                            <input
                                                autofocus
                                                wire:model="nuevaZona"
                                                wire:keydown.enter.prevent="guardarZona"
                                                wire:keydown.escape="cancelarZona"
                                                placeholder="Nueva zona"
                                                aria-label="Nueva zona"
                                                class="{{ $campo }}"
                                            />
                                            <button type="button" wire:click="guardarZona" aria-label="Guardar zona" class="rounded-md bg-[#1c5480] p-2 text-white hover:bg-[#174567]">
                                                <x-icon name="check" class="h-4 w-4" />
                                            </button>
                                            <button type="button" wire:click="cancelarZona" aria-label="Cancelar zona" class="rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50">
                                                <x-icon name="x" class="h-4 w-4" />
                                            </button>
                                        </div>
                                        @error('nuevaZona') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    @else
                                        <div class="flex items-center gap-2">
                                            <select wire:model="direcciones.{{ $indice }}.flete_zona_id" aria-label="Zona" class="{{ $campo }}">
                                                <option value="">-</option>
                                                @foreach ($zonas as $zona)
                                                    <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                                                @endforeach
                                            </select>
                                            <button
                                                type="button"
                                                wire:click="abrirZona({{ $indice }})"
                                                title="Agregar zona"
                                                aria-label="Agregar zona"
                                                class="rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 hover:text-[#1c5480]"
                                            >
                                                <x-icon name="plus" class="h-4 w-4" />
                                            </button>
                                        </div>
                                        @error('direcciones.'.$indice.'.flete_zona_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <input
                                        wire:model="direcciones.{{ $indice }}.direccion"
                                        wire:keydown.enter.prevent="listoDireccion"
                                        placeholder="Calle y número"
                                        aria-label="Dirección"
                                        class="{{ $campo }}"
                                    />
                                    @error('direcciones.'.$indice.'.direccion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-6 py-3">
                                    <input
                                        wire:model="direcciones.{{ $indice }}.codigo_postal"
                                        wire:keydown.enter.prevent="listoDireccion"
                                        placeholder="CP"
                                        aria-label="Código postal"
                                        class="{{ $campo }}"
                                    />
                                    @error('direcciones.'.$indice.'.codigo_postal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" wire:click="listoDireccion" aria-label="Listo" class="text-[#1c5480] hover:text-[#174567]">
                                            <x-icon name="check" class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="quitarDireccion({{ $indice }})" aria-label="Quitar dirección" class="text-red-400 hover:text-red-600">
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            @else
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $nombreZona[$fila['flete_zona_id']] ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $fila['direccion'] ?: '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $fila['codigo_postal'] ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button
                                            type="button"
                                            wire:click="editarDireccion({{ $indice }})"
                                            aria-label="Editar dirección {{ $fila['direccion'] }}"
                                            class="text-slate-400 hover:text-[#1c5480]"
                                        >
                                            <x-icon name="square-pen" class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="quitarDireccion({{ $indice }})"
                                            aria-label="Quitar dirección {{ $fila['direccion'] }}"
                                            class="text-red-400 hover:text-red-600"
                                        >
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    @if (count($direcciones) === 0)
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-400">
                                Todavía no hay direcciones de entrega cargadas.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-2 text-xs text-slate-400">Los cambios se aplican al guardar el cliente.</p>
</section>
