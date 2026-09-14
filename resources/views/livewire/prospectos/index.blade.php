@php
    $solapa = fn (bool $activa) => 'border-b-2 px-1 pb-1 text-sm '
        .($activa ? 'border-[#1c5480] text-slate-900' : 'border-transparent text-slate-400 hover:text-slate-600');
@endphp

<div class="mx-auto w-full max-w-[1224px] ">
    <h1 class="mb-[32px] text-[#101828] text-[24px] font-bold leading-[32px]">Prospectos</h1>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <nav class="flex items-center gap-6">
            <button type="button" wire:click="filtrar(null)" class="{{ $solapa(! $vendedorActivo) }}">Todos</button>
            @foreach ($vendedores as $vendedor)
                <button type="button" wire:click="filtrar({{ $vendedor->id }})" class="{{ $solapa($vendedorActivo === $vendedor->id) }}">
                    {{ $vendedor->nombre }}
                </button>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <button
                type="button"
                disabled
                title="Próximamente"
                class="flex h-10 cursor-default items-center gap-2 rounded-md border border-[#1c5480] px-4 text-sm text-[#1c5480]"
            >
                <x-icon name="sliders-horizontal" class="h-4 w-4" />
                Filtros
            </button>
            <a
                href="{{ route('prospectos.create') }}"
                wire:navigate
                class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nuevo Prospecto
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto scroll-sutil">
            <table class="w-full min-w-[760px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                        <th class="px-6 py-3 font-medium">Código</th>
                        <th class="px-6 py-3 font-medium">Razón social</th>
                        <th class="px-6 py-3 font-medium">Localidad</th>
                        <th class="px-6 py-3 font-medium">Rubro</th>
                        <th class="px-6 py-3 font-medium">Vendedor</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prospectos as $prospecto)
                        <tr wire:key="prospecto-{{ $prospecto['id'] }}" class="border-b border-slate-100 last:border-0 hover:bg-slate-50/60">
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $prospecto['codigo'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-800">{{ $prospecto['razon_social'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $prospecto['localidad'] ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $prospecto['rubro'] ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $prospecto['vendedor'] ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        wire:click="ver({{ $prospecto['id'] }})"
                                        aria-label="Ver {{ $prospecto['razon_social'] }}"
                                        class="text-slate-400 hover:text-[#1c5480]"
                                    >
                                        <x-icon name="eye" class="h-4 w-4" />
                                    </button>
                                    <a
                                        href="{{ route('prospectos.edit', $prospecto['id']) }}"
                                        wire:navigate
                                        aria-label="Editar {{ $prospecto['razon_social'] }}"
                                        class="text-slate-400 hover:text-[#1c5480]"
                                    >
                                        <x-icon name="square-pen" class="h-4 w-4" />
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="eliminar({{ $prospecto['id'] }})"
                                        wire:confirm="¿Eliminar el prospecto {{ $prospecto['codigo'] }} - {{ $prospecto['razon_social'] }}?"
                                        aria-label="Eliminar {{ $prospecto['razon_social'] }}"
                                        class="text-slate-400 hover:text-red-600"
                                    >
                                        <x-icon name="trash-2" class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if ($prospectos->isEmpty())
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">
                                No hay prospectos para esta solapa.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <x-ficha-contacto :ficha="$ficha" ruta="prospectos" titulo="Prospecto" />
</div>
