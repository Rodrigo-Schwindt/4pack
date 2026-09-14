<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Ajustes</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
        <nav class="rounded-lg bg-white p-3 shadow-sm">
            @foreach ($grupos as $clave => $titulo)
                <button
                    type="button"
                    wire:key="grupo-{{ $clave }}"
                    wire:click="seleccionar('{{ $clave }}')"
                    class="flex h-10 w-full items-center gap-2 rounded-md px-3 text-sm {{ $grupo === $clave ? 'bg-[#1c5480] font-medium text-white' : 'text-slate-600 hover:bg-slate-50' }}"
                >
                    <x-icon name="sliders-horizontal" class="h-4 w-4" />
                    {{ $titulo }}
                </button>
            @endforeach

            <hr class="my-2 border-slate-100" />

            {{-- Constantes de las formulas de la cotizacion, un numero por clave. --}}
            <button
                type="button"
                wire:click="seleccionar('variables')"
                class="flex h-10 w-full items-center gap-2 rounded-md px-3 text-sm {{ $grupo === 'variables' ? 'bg-[#1c5480] font-medium text-white' : 'text-slate-600 hover:bg-slate-50' }}"
            >
                <x-icon name="settings" class="h-4 w-4" />
                Variables de cálculo
            </button>
        </nav>

        @if ($grupo === 'variables')
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Variables de cálculo</h2>
            <p class="mt-1 mb-6 text-xs text-slate-400">Constantes que usan las fórmulas de la cotización. Se guardan al salir del campo; el valor entre paréntesis es el de la planilla original.</p>

            @foreach ($secciones as $seccion => $variables)
                <h3 class="mt-6 mb-2 text-[11px] font-medium tracking-wide text-slate-500 uppercase first:mt-0">{{ $seccion }}</h3>

                <div class="divide-y divide-slate-100 rounded-md border border-slate-100">
                    @foreach ($variables as $clave => $variable)
                        <div wire:key="variable-{{ $clave }}" class="flex flex-wrap items-center gap-3 px-4 py-2.5">
                            <label for="variable-{{ $clave }}" class="flex-1 text-sm text-slate-700">
                                {{ $variable['etiqueta'] }}
                                <span class="text-xs text-slate-400">({{ \App\Support\Numero::corto($variable['defecto'], 4) }})</span>
                            </label>

                            <div class="flex items-center gap-2">
                                <input
                                    id="variable-{{ $clave }}"
                                    type="number"
                                    step="any"
                                    min="0"
                                    wire:model.blur="variables.{{ $clave }}"
                                    class="h-9 w-32 rounded-md border border-slate-200 bg-white px-3 text-right text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                                />
                                <button
                                    type="button"
                                    wire:click="restaurar('{{ $clave }}')"
                                    title="Volver al valor de la planilla"
                                    aria-label="Restaurar {{ $variable['etiqueta'] }}"
                                    class="text-slate-300 hover:text-[#1c5480]"
                                >
                                    <x-icon name="loader-circle" class="h-4 w-4" />
                                </button>
                            </div>

                            @error('variables.'.$clave) <p class="w-full text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            @endforeach
        </section>
        @else
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-6 font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">
                Valores de {{ $grupos[$grupo] }}
            </h2>

            <form wire:submit="agregar" class="mb-6 flex flex-wrap items-start gap-3">
                <div class="flex w-40 flex-col gap-1.5">
                    <label for="valor" class="sr-only">Nuevo valor</label>
                    <input
                        id="valor"
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="valor"
                        placeholder="0"
                        class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                    />
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567] disabled:opacity-70"
                >
                    <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="agregar" />
                    <x-icon name="plus" class="h-4 w-4" wire:loading.remove wire:target="agregar" />
                    Agregar
                </button>

                @error('valor') <p class="w-full text-sm text-red-600">{{ $message }}</p> @enderror
            </form>

            <div class="overflow-x-auto scroll-sutil">
                <table class="w-full min-w-[320px] text-left">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] tracking-wide text-slate-500 uppercase">
                            <th class="px-2 py-3 font-medium">Valor</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($valores as $item)
                            <tr wire:key="valor-{{ $item->id }}" class="border-b border-slate-100 last:border-0">
                                <td class="px-2 py-3 text-sm text-slate-800">{{ \App\Support\Numero::corto($item->valor, 4) }}</td>
                                <td class="px-2 py-3">
                                    <div class="flex items-center justify-end">
                                        <button
                                            type="button"
                                            wire:click="eliminar({{ $item->id }})"
                                            wire:confirm="¿Eliminar el valor {{ \App\Support\Numero::corto($item->valor, 4) }}?"
                                            aria-label="Eliminar {{ \App\Support\Numero::corto($item->valor, 4) }}"
                                            class="text-red-400 hover:text-red-600"
                                        >
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        @if ($valores->isEmpty())
                            <tr>
                                <td colspan="2" class="px-2 py-10 text-center text-sm text-slate-400">
                                    Todavía no hay valores cargados.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
        @endif
    </div>
</div>
