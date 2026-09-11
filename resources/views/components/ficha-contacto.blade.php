@props(['ficha', 'ruta', 'titulo'])

{{-- Ficha de solo lectura del contacto, en un modal sobre el listado. --}}
@if ($ficha)
    <div
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/40 px-4 py-10"
        wire:keydown.escape.window="cerrar"
        wire:click.self="cerrar"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ficha-titulo"
    >
        <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4">
                <div>
                    <p class="text-xs text-slate-400">{{ $titulo }} {{ $ficha['codigo'] }}</p>
                    <h2 id="ficha-titulo" class="text-lg font-semibold text-slate-900">{{ $ficha['razon_social'] }}</h2>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        href="{{ route($ruta.'.edit', $ficha['id']) }}"
                        wire:navigate
                        class="flex h-9 items-center gap-2 rounded-md border border-slate-200 px-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#1c5480]"
                    >
                        <x-icon name="square-pen" class="h-4 w-4" />
                        Editar
                    </a>
                    <button type="button" wire:click="cerrar" aria-label="Cerrar" class="rounded-md p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-700">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($ficha['campos'] as $etiqueta => $valor)
                    <div>
                        <dt class="text-xs text-slate-400">{{ $etiqueta }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $valor ?: '-' }}</dd>
                    </div>
                @endforeach

                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="text-xs text-slate-400">Observaciones</dt>
                    <dd class="mt-0.5 text-sm whitespace-pre-line text-slate-800">{{ $ficha['observaciones'] ?: '-' }}</dd>
                </div>
            </dl>

            @if ($ficha['direcciones'] !== [])
                <div class="border-t border-slate-100 px-6 py-5">
                    <h3 class="mb-3 text-sm font-medium text-slate-700">Direcciones de entrega</h3>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[11px] tracking-wide text-slate-400 uppercase">
                                <th class="py-1 pr-4 font-medium">Zona</th>
                                <th class="py-1 pr-4 font-medium">Dirección</th>
                                <th class="py-1 font-medium">Código postal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ficha['direcciones'] as $direccion)
                                <tr class="border-t border-slate-100">
                                    <td class="py-2 pr-4 text-sm text-slate-700">{{ $direccion['zona'] ?: '-' }}</td>
                                    <td class="py-2 pr-4 text-sm text-slate-700">{{ $direccion['direccion'] }}</td>
                                    <td class="py-2 text-sm text-slate-700">{{ $direccion['codigo_postal'] ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif
