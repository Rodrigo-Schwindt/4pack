@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    [$titulo, $etiquetaClave, $ayuda] = $tipos[$tipo];
    $pct = fn ($valor) => \App\Support\Numero::porcentaje($valor, 4);
    $claveVisible = fn (string $clave) => $clave === \App\Models\VariableCosto::RESTO ? 'Resto' : (is_numeric($clave) ? \App\Support\Numero::formato($clave, 0) : $clave);
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Variables Costos</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
        <nav class="rounded-lg bg-white p-3 shadow-sm">
            @foreach ($tipos as $clave => [$nombre])
                <button
                    type="button"
                    wire:key="tipo-{{ $clave }}"
                    wire:click="seleccionar('{{ $clave }}')"
                    class="flex h-10 w-full items-center gap-2 rounded-md px-3 text-left text-sm {{ $tipo === $clave ? 'bg-[#1c5480] font-medium text-white' : 'text-slate-600 hover:bg-slate-50' }}"
                >
                    <x-icon name="sliders-horizontal" class="h-4 w-4 shrink-0" />
                    {{ $nombre }}
                </button>
            @endforeach
        </nav>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">{{ $titulo }}</h2>
                    <p class="mt-1 text-xs text-slate-400">{{ $ayuda }}</p>
                </div>

                <button
                    type="button"
                    wire:click="nueva"
                    class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    Agregar
                </button>
            </div>

            <div class="overflow-x-auto scroll-sutil rounded-md border border-slate-100">
                <table class="w-full min-w-[420px] text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                            <th class="px-6 py-4 font-medium">{{ $etiquetaClave }}</th>
                            <th class="px-6 py-4 text-right font-medium">Porcentaje</th>
                            <th class="w-24 px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($editando === 0)
                            @include('livewire.variables-costos.fila-edicion', ['etiqueta' => 'nuevo valor'])
                        @endif

                        @foreach ($filas as $fila)
                            @if ($editando === $fila->id)
                                @include('livewire.variables-costos.fila-edicion', ['etiqueta' => $fila->clave, 'clave' => 'editar-'.$fila->id])
                            @else
                                <tr wire:key="fila-{{ $fila->id }}" class="border-b border-slate-100 last:border-0 hover:bg-slate-50/60">
                                    <td class="px-6 py-4 text-sm text-slate-700">{{ $claveVisible($fila->clave) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-slate-600">{{ $pct($fila->valor) }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-3">
                                            <button type="button" wire:click="editar({{ $fila->id }})" aria-label="Editar {{ $fila->clave }}" class="text-slate-400 hover:text-[#1c5480]">
                                                <x-icon name="square-pen" class="h-4 w-4" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="eliminar({{ $fila->id }})"
                                                wire:confirm="¿Eliminar {{ $claveVisible($fila->clave) }}?"
                                                aria-label="Eliminar {{ $fila->clave }}"
                                                class="text-red-400 hover:text-red-600"
                                            >
                                                <x-icon name="trash-2" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach

                        @if ($filas->isEmpty() && $editando !== 0)
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-sm text-slate-400">Todavía no hay valores cargados.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
