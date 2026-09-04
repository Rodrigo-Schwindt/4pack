<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-slate-900">Cotizaciones</h1>

        {{-- El buscador y el boton van juntos contra la derecha: el aire queda entre el titulo y el buscador. --}}
        <div class="flex flex-1 flex-wrap items-center justify-end gap-3">
            <div class="relative min-w-[280px] max-w-[500px] flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    wire:model.live.debounce.300ms="busqueda"
                    placeholder="Buscar por código o descripción..."
                    aria-label="Buscar cotizaciones"
                    class="h-10 w-full rounded-md border border-slate-200 bg-white pr-3 pl-9 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                />
            </div>

            <a
                href="{{ route('cotizaciones.create') }}"
                wire:navigate
                class="flex h-10 shrink-0 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nueva Cotización
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] tracking-wide text-slate-500 uppercase">
                        <th class="px-6 py-3 font-medium">N° Cotización</th>
                        <th class="px-6 py-3 font-medium">Fecha</th>
                        <th class="px-6 py-3 font-medium">Cliente</th>
                        <th class="px-6 py-3 font-medium">Vendedor</th>
                        <th class="px-6 py-3 font-medium">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cotizaciones as $cotizacion)
                        <tr wire:key="cotizacion-{{ $cotizacion['id'] }}" class="border-b border-slate-100 last:border-0 hover:bg-slate-50/60">
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $cotizacion['numero'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $cotizacion['fecha'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $cotizacion['cliente'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $cotizacion['vendedor'] }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-md px-3 py-1 text-xs font-medium {{ $cotizacion['estado'] === 'Finalizado' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                    {{ $cotizacion['estado'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" disabled title="Próximamente" aria-label="Ver cotización {{ $cotizacion['numero'] }}" class="cursor-default text-slate-400">
                                    <x-icon name="chevron-right" class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    @if ($cotizaciones->isEmpty())
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">
                                No hay cotizaciones que coincidan con la búsqueda.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
