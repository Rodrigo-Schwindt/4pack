@php
    $numero = fn (?float $valor) => $valor === null ? '-' : \App\Support\Numero::corto($valor);
    $usd = fn (?float $valor) => $valor === null ? '-' : \App\Support\Numero::usd($valor, prefijo: 'U$S ');
    $fecha = fn (?string $valor) => $valor ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y') : '-';
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-slate-700 hover:text-slate-900">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <h1 class="text-2xl font-bold text-[#101828]">Entregas</h1>
        </div>

        <button
            type="button"
            wire:click="$toggle('mostrarFiltros')"
            aria-expanded="{{ $mostrarFiltros ? 'true' : 'false' }}"
            class="flex h-10 items-center gap-2 rounded-[10px] border border-[#22577C] bg-white px-6 text-base font-medium text-[#22577C] hover:bg-slate-50"
        >
            Filtros
            @if ($hayFiltros)
                <span class="h-2 w-2 rounded-full bg-[#22577C]" aria-label="Hay filtros aplicados"></span>
            @endif
        </button>
    </div>

    @if ($mostrarFiltros)
        <div class="mb-6 rounded-lg bg-white p-5 shadow-sm">
            <div class="grid gap-x-6 gap-y-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="flex flex-col gap-1.5">
                    <label for="filtro-cliente" class="text-sm text-slate-600">Cliente</label>
                    <input id="filtro-cliente" wire:model.live.debounce.300ms="cliente" placeholder="Buscar cliente..." class="{{ $campo }}" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="filtro-vendedor" class="text-sm text-slate-600">Vendedor</label>
                    <div class="relative">
                        <select id="filtro-vendedor" wire:model.live="vendedor" class="{{ $campo }} appearance-none pr-9">
                            <option value="">Todos</option>
                            @foreach ($vendedores as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="filtro-estado" class="text-sm text-slate-600">Estado</label>
                    <div class="relative">
                        <select id="filtro-estado" wire:model.live="estado" class="{{ $campo }} appearance-none pr-9">
                            <option value="">Todas</option>
                            <option value="pendientes">Pendientes de entregar</option>
                            <option value="entregadas">Entregadas</option>
                        </select>
                        <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="filtro-desde" class="text-sm text-slate-600">Desde</label>
                    <input id="filtro-desde" type="date" wire:model.live="desde" class="{{ $campo }} campo-fecha" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="filtro-hasta" class="text-sm text-slate-600">Hasta</label>
                    <input id="filtro-hasta" type="date" wire:model.live="hasta" class="{{ $campo }} campo-fecha" />
                </div>
            </div>

            @if ($hayFiltros)
                <button type="button" wire:click="limpiarFiltros" class="mt-4 text-sm text-[#1c5480] hover:underline">Limpiar filtros</button>
            @endif
        </div>
    @endif

    <div class="overflow-hidden bg-white">
        <div class="overflow-x-auto scroll-sutil">
            <table class="w-full min-w-[960px] text-left">
                <thead>
                    <tr class="h-12 border-b border-[#E5E7EB] bg-[#F9FAFB] text-xs font-medium tracking-[0.6px] text-[#6A7282] uppercase">
                        <th class="px-[35px] font-medium">Cliente</th>
                        <th class="px-4 font-medium">Fecha entrega</th>
                        <th class="px-4 font-medium">Cantidad a entregar</th>
                        <th class="px-4 font-medium">Cantidad entregada</th>
                        <th class="px-4 text-right font-medium">Comisión proyectada</th>
                        <th class="pr-[27px] pl-4 text-right font-medium">Comisión real</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entregas as $fila)
                        <tr
                            wire:key="entrega-{{ $fila['cotizacion_id'] }}-{{ $loop->index }}-{{ $entregas->currentPage() }}"
                            class="h-[68px] border-b border-[#E5E7EB] text-sm text-[#6A7282] hover:bg-slate-50/60"
                        >
                            <td class="px-[35px] py-3">
                                <a href="{{ route('cotizaciones.edit', $fila['cotizacion_id']) }}" wire:navigate class="hover:text-[#1c5480]">
                                    {{ $fila['cliente'] }}
                                </a>
                                <span class="block text-xs text-slate-400">{{ $fila['numero'] }} · {{ $fila['entrega'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ $fecha($fila['fecha']) }}
                                @if ($fila['fecha'] && ! $fila['fecha_real'])
                                    <span class="block text-xs text-slate-400">pactada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $fila['a_entregar'] > 0 ? $numero($fila['a_entregar']).' '.$fila['unidad'] : '-' }}</td>
                            <td class="px-4 py-3">{{ $fila['entregada'] === null ? '-' : $numero($fila['entregada']).' '.$fila['unidad'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $usd($fila['proyectada']) }}</td>
                            <td class="py-3 pr-[27px] pl-4 text-right">{{ $fila['entregada'] === null ? '-' : $usd($fila['real']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-400">
                                {{ $hayFiltros ? 'Ninguna entrega coincide con los filtros.' : 'Todavía no hay entregas: aparecen cuando se aprueba una cotización.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($entregas->hasPages())
        <div class="mt-4">{{ $entregas->links() }}</div>
    @endif
</div>
