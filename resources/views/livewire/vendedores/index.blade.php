<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Gestión de Vendedores</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Usuarios del Sistema</h2>
            <a
                href="{{ route('vendedores.create') }}"
                wire:navigate
                class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nuevo Vendedor
            </a>
        </div>

        <div class="overflow-x-auto scroll-sutil">
            <table class="w-full min-w-[860px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] tracking-wide text-slate-500 uppercase">
                        <th class="px-2 py-3 font-medium">Nombre</th>
                        <th class="px-2 py-3 font-medium">Com. Bobinas</th>
                        <th class="px-2 py-3 font-medium">Com. DPK</th>
                        <th class="px-2 py-3 font-medium">Com. Pouch</th>
                        <th class="px-2 py-3 font-medium">Com. 4 Costuras</th>
                        <th class="px-2 py-3 font-medium">Estado</th>
                        <th class="px-2 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vendedores as $vendedor)
                        <tr wire:key="vendedor-{{ $vendedor->id }}" class="border-b border-slate-100 last:border-0">
                            <td class="px-2 py-4 text-sm text-slate-800">{{ $vendedor->nombre }}</td>
                            <td class="px-2 py-4 text-sm text-slate-600">{{ \App\Support\Numero::porcentaje($vendedor->comision_bobinas) }}</td>
                            <td class="px-2 py-4 text-sm text-slate-600">{{ \App\Support\Numero::porcentaje($vendedor->comision_dpk) }}</td>
                            <td class="px-2 py-4 text-sm text-slate-600">{{ \App\Support\Numero::porcentaje($vendedor->comision_pouch) }}</td>
                            <td class="px-2 py-4 text-sm text-slate-600">{{ \App\Support\Numero::porcentaje($vendedor->comision_4_costuras) }}</td>
                            <td class="px-2 py-4">
                                <span class="rounded px-2 py-0.5 text-[11px] font-medium {{ $vendedor->activo ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $vendedor->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-2 py-4">
                                <div class="flex items-center justify-end gap-3">
                                    <a
                                        href="{{ route('vendedores.edit', $vendedor->id) }}"
                                        wire:navigate
                                        aria-label="Editar {{ $vendedor->nombre }}"
                                        class="text-slate-400 hover:text-[#1c5480]"
                                    >
                                        <x-icon name="square-pen" class="h-4 w-4" />
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="eliminar({{ $vendedor->id }})"
                                        wire:confirm="¿Eliminar al vendedor {{ $vendedor->nombre }}?"
                                        aria-label="Eliminar {{ $vendedor->nombre }}"
                                        class="text-red-400 hover:text-red-600"
                                    >
                                        <x-icon name="trash-2" class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if ($vendedores->isEmpty())
                        <tr>
                            <td colspan="7" class="px-2 py-10 text-center text-sm text-slate-400">
                                Todavía no hay vendedores cargados.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>
