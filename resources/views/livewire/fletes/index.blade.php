@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $pesos = fn ($valor) => '$'.number_format((float) $valor, 2, ',', '.');
    $dolares = fn ($valor) => $cotizacion > 0 ? 'USD '.number_format((float) $valor / $cotizacion, 2, ',', '.') : 'USD -';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Gestión de Flete Insumos</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Catálogo de flete Insumos</h2>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="dolar" class="text-sm text-slate-600">Dólar</label>
                    <input
                        id="dolar"
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model.live.debounce.500ms="dolar"
                        class="h-10 w-28 rounded-md border border-slate-200 bg-white px-3 text-right text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                    />
                </div>

                <button
                    type="button"
                    wire:click="$toggle('mostrarTramos')"
                    class="flex h-10 items-center gap-2 rounded-md border border-slate-200 px-4 text-sm text-slate-600 hover:bg-slate-50"
                >
                    <x-icon name="sliders-horizontal" class="h-4 w-4" />
                    Tramos
                </button>

                <button
                    type="button"
                    wire:click="nueva"
                    class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    Nueva Zona
                </button>
            </div>
        </div>

        @error('dolar') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror

        @if ($mostrarTramos)
            <div class="mb-5 rounded-md border border-slate-200 bg-slate-50 p-4">
                <p class="mb-3 text-sm font-medium text-slate-700">Tramos (columnas de la tabla)</p>

                <div class="mb-4 flex flex-wrap gap-2">
                    @foreach ($tramos as $tramo)
                        <span wire:key="chip-{{ $tramo->id }}" class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs text-slate-600">
                            {{ $tramo->etiqueta }}
                            <button
                                type="button"
                                wire:click="eliminarTramo({{ $tramo->id }})"
                                wire:confirm="Se borran también los precios cargados en la columna {{ $tramo->etiqueta }}. ¿Continuar?"
                                aria-label="Eliminar tramo {{ $tramo->etiqueta }}"
                                class="text-slate-300 hover:text-red-600"
                            >
                                <x-icon name="x" class="h-3.5 w-3.5" />
                            </button>
                        </span>
                    @endforeach

                    @if ($tramos->isEmpty())
                        <p class="text-xs text-slate-400">Todavía no hay tramos: agregá el primero para poder cargar precios.</p>
                    @endif
                </div>

                <form wire:submit="agregarTramo" class="flex flex-wrap items-start gap-3">
                    <div class="w-32">
                        <input type="number" step="0.01" min="0" wire:model="tramoKg" placeholder="Kg" aria-label="Kg del tramo" class="{{ $campo }}" />
                        @error('tramoKg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="w-32">
                        <input type="number" step="1" min="1" wire:model="tramoPallets" placeholder="Pallets" aria-label="Pallets del tramo" class="{{ $campo }}" />
                        @error('tramoPallets') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="flex h-10 items-center gap-2 rounded-md border border-slate-200 bg-white px-4 text-sm text-slate-600 hover:bg-slate-50">
                        <x-icon name="plus" class="h-4 w-4" />
                        Agregar tramo
                    </button>
                </form>
            </div>
        @endif

        {{-- Zonas que entraron por una direccion de entrega y esperan precios. --}}
        @if ($pendientes->isNotEmpty())
            <div class="mb-5 flex flex-wrap items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-4 py-3">
                <x-icon name="triangle-alert" class="h-4 w-4 text-amber-500" />
                <p class="text-sm text-amber-800">Sin precios cargados:</p>

                @foreach ($pendientes as $pendiente)
                    <button
                        type="button"
                        wire:key="pendiente-{{ $pendiente->id }}"
                        wire:click="editar({{ $pendiente->id }})"
                        class="rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-800 hover:border-[#1c5480] hover:text-[#1c5480]"
                    >
                        {{ $pendiente->nombre }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="relative mb-5">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                type="search"
                wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por zona..."
                aria-label="Buscar por zona"
                class="h-11 w-full rounded-md border border-slate-200 bg-white pr-3 pl-9 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left">
                <thead>
                    <tr class="bg-slate-50 text-[11px] tracking-wide text-slate-500 uppercase">
                        <th class="rounded-l-md px-4 py-3 font-medium">Zona</th>
                        @foreach ($tramos as $tramo)
                            <th class="px-4 py-3 text-right font-medium">{{ $tramo->etiqueta }}</th>
                        @endforeach
                        <th class="w-24 rounded-r-md px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($editando === 0)
                        <tr class="border-b border-slate-100 bg-slate-50/60">
                            <td class="px-4 py-3">
                                <input autofocus wire:model="nombre" placeholder="Nombre de la zona" aria-label="Nombre de la zona" class="{{ $campo }}" />
                                @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </td>
                            @foreach ($tramos as $tramo)
                                <td class="px-4 py-3">
                                    <input type="number" step="0.01" min="0" wire:model="precios.{{ $tramo->id }}" placeholder="0,00" aria-label="Precio {{ $tramo->etiqueta }}" class="{{ $campo }} text-right" />
                                    @error('precios.'.$tramo->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                            @endforeach
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" wire:click="guardar" aria-label="Guardar zona" class="text-[#1c5480] hover:text-[#174567]">
                                        <x-icon name="check" class="h-4 w-4" />
                                    </button>
                                    <button type="button" wire:click="cancelar" aria-label="Cancelar" class="text-slate-400 hover:text-slate-600">
                                        <x-icon name="x" class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endif

                    @foreach ($zonas as $zona)
                        @php $fila = $zona->preciosPorTramo(); @endphp

                        @if ($editando === $zona->id)
                            <tr wire:key="editar-{{ $zona->id }}" class="border-b border-slate-100 bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <input autofocus wire:model="nombre" placeholder="Nombre de la zona" aria-label="Nombre de la zona" class="{{ $campo }}" />
                                    @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                                @foreach ($tramos as $tramo)
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0" wire:model="precios.{{ $tramo->id }}" placeholder="0,00" aria-label="Precio {{ $tramo->etiqueta }}" class="{{ $campo }} text-right" />
                                        @error('precios.'.$tramo->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                @endforeach
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" wire:click="guardar" aria-label="Guardar {{ $zona->nombre }}" class="text-[#1c5480] hover:text-[#174567]">
                                            <x-icon name="check" class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="cancelar" aria-label="Cancelar" class="text-slate-400 hover:text-slate-600">
                                            <x-icon name="x" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr wire:key="zona-{{ $zona->id }}" class="border-b border-slate-100 last:border-0">
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $zona->nombre }}</td>

                                @foreach ($tramos as $tramo)
                                    <td class="px-4 py-4 text-right">
                                        @if (isset($fila[$tramo->id]))
                                            <p class="text-sm text-slate-800">{{ $pesos($fila[$tramo->id]) }}</p>
                                            <p class="text-xs text-slate-400">{{ $dolares($fila[$tramo->id]) }}</p>
                                        @else
                                            <p class="text-sm text-slate-300">-</p>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" wire:click="editar({{ $zona->id }})" aria-label="Editar {{ $zona->nombre }}" class="text-slate-400 hover:text-[#1c5480]">
                                            <x-icon name="square-pen" class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="eliminar({{ $zona->id }})"
                                            wire:confirm="¿Eliminar la zona {{ $zona->nombre }}?"
                                            aria-label="Eliminar {{ $zona->nombre }}"
                                            class="text-red-400 hover:text-red-600"
                                        >
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    @if ($zonas->isEmpty() && $editando !== 0)
                        <tr>
                            <td colspan="{{ $tramos->count() + 2 }}" class="px-4 py-10 text-center text-sm text-slate-400">
                                {{ $buscar !== '' ? 'Ninguna zona coincide con la búsqueda.' : 'Todavía no hay zonas cargadas.' }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>
