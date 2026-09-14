@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    // Como en la maqueta: 1,5 y 0 en vez de 1,50 y 0,00.
    $num = fn ($valor) => \App\Support\Numero::corto($valor);
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Gestión de Operativos</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Costos operativos por sector</h2>
                <p class="mt-1 text-xs text-slate-400">Valor de la hora en U$S, metros por hora, horas de preparación y scrap. Los usa el cálculo de costos de la cotización.</p>
            </div>

            <button
                type="button"
                wire:click="nuevo"
                class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nuevo Sector
            </button>
        </div>

        <div class="relative mb-5">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                type="search"
                wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por sector..."
                aria-label="Buscar por sector"
                class="h-11 w-full rounded-md border border-slate-200 bg-white pr-3 pl-9 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
            />
        </div>

        <div class="overflow-x-auto scroll-sutil rounded-md border border-slate-100">
            <table class="w-full min-w-[760px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                        <th class="px-6 py-4 font-medium">Sector</th>
                        <th class="px-6 py-4 text-right font-medium">Valor hora (U$S)</th>
                        <th class="px-6 py-4 text-right font-medium">Producción (mts/h)</th>
                        <th class="px-6 py-4 text-right font-medium">Setup (hs)</th>
                        <th class="px-6 py-4 text-right font-medium">Scrap (%)</th>
                        <th class="w-24 px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($editando === 0)
                        @include('livewire.operativos.fila-edicion', ['etiqueta' => 'sector nuevo'])
                    @endif

                    @foreach ($operativos as $operativo)
                        @if ($editando === $operativo->id)
                            @include('livewire.operativos.fila-edicion', ['etiqueta' => $operativo->sector, 'clave' => 'editar-'.$operativo->id])
                        @else
                            <tr wire:key="operativo-{{ $operativo->id }}" class="border-b border-slate-100 last:border-0 hover:bg-slate-50/60">
                                <td class="px-6 py-5 text-sm text-slate-700">{{ $operativo->sector }}</td>
                                <td class="px-6 py-5 text-right text-sm text-slate-600">{{ $num($operativo->valor_hora) }}</td>
                                <td class="px-6 py-5 text-right text-sm text-slate-600">{{ $num($operativo->produccion_mts_hora) }}</td>
                                <td class="px-6 py-5 text-right text-sm text-slate-600">{{ $num($operativo->setup_horas) }}</td>
                                <td class="px-6 py-5 text-right text-sm text-slate-600">{{ $num($operativo->scrap_pct) }}%</td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" wire:click="editar({{ $operativo->id }})" aria-label="Editar {{ $operativo->sector }}" class="text-slate-400 hover:text-[#1c5480]">
                                            <x-icon name="square-pen" class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="eliminar({{ $operativo->id }})"
                                            wire:confirm="¿Eliminar el sector {{ $operativo->sector }}?"
                                            aria-label="Eliminar {{ $operativo->sector }}"
                                            class="text-red-400 hover:text-red-600"
                                        >
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    @if ($operativos->isEmpty() && $editando !== 0)
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">
                                {{ $buscar !== '' ? 'Ningún sector coincide con la búsqueda.' : 'Todavía no hay sectores cargados.' }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>
