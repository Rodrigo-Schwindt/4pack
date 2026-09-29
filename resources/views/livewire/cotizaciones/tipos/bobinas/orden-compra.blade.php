@php
    use App\Models\AjusteTexto;

    // Todos los formularios de la cotizacion (productos y opciones), con sus entregas.
    $filas = $this->filasOrdenCompra;
    $entregasOc = $this->entregasOrdenCompra;
    $canales = AjusteTexto::opciones(AjusteTexto::CANALES_OC);

    $linea = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $chico = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-[#1c5480]';
    $peso = fn (int $bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '.').' MB' : max(1, round($bytes / 1024)).' KB';
@endphp

<section class="rounded-lg bg-white shadow-sm">
    <h2 class="px-6 py-5 text-[16px] leading-[normal] font-medium text-black">Orden de compra</h2>
    <hr class="border-slate-100" />

    <div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
        {{-- Canal: lista de Configuración > Ajustes > Canales de OC, con alta al vuelo. --}}
        <div class="flex flex-col gap-1.5">
            <label for="oc.canal" class="text-sm text-slate-600">Canal recibo de OC</label>

            @if ($creandoCanal)
                <div class="flex items-center gap-2">
                    <input
                        id="oc.canal"
                        autofocus
                        wire:model="nuevoCanal"
                        wire:keydown.enter.prevent="guardarCanal"
                        wire:keydown.escape="cancelarCanal"
                        maxlength="60"
                        placeholder="Nuevo canal"
                        class="{{ $linea }}"
                    />
                    <button type="button" wire:click="guardarCanal" aria-label="Guardar canal" class="rounded-md bg-[#1c5480] p-2 text-white hover:bg-[#174567]">
                        <x-icon name="check" class="h-4 w-4" />
                    </button>
                    <button type="button" wire:click="cancelarCanal" aria-label="Cancelar" class="rounded-md border border-slate-200 p-2 text-slate-500 hover:bg-slate-50">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
                @error('nuevoCanal') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @else
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <select id="oc.canal" wire:model="oc.canal" class="{{ $linea }} appearance-none pr-9">
                            <option value="">-</option>
                            @foreach ($canales as $canal)
                                <option value="{{ $canal }}">{{ $canal }}</option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    </div>
                    <button type="button" wire:click="abrirCanal" title="Agregar canal" aria-label="Agregar canal de recibo de OC" class="{{ $chico }}">
                        <x-icon name="plus" class="h-4 w-4" />
                    </button>
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="oc.fecha_recibo" class="text-sm text-slate-600">Fecha recibo OC</label>
            <div class="relative">
                <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input id="oc.fecha_recibo" type="date" wire:model="oc.fecha_recibo" class="{{ $linea }} campo-fecha pl-9" />
            </div>
        </div>

        <x-campo.texto label="Quien recibió OC" modelo="oc.quien" />

        {{-- Cualquier tipo de archivo, uno o varios a la vez: se suman a la lista de abajo. --}}
        <div class="flex flex-col gap-1.5">
            <span class="text-sm text-slate-600">Adjuntar OC</span>
            <label class="flex h-10 cursor-pointer items-center justify-between rounded-md border border-slate-200 bg-white px-3 text-sm hover:bg-slate-50">
                <span class="text-slate-400" wire:loading.remove wire:target="subidasOc">Seleccionar archivo</span>
                <span class="text-slate-500" wire:loading wire:target="subidasOc">Subiendo...</span>
                <x-icon name="download" class="h-4 w-4 shrink-0 text-slate-500" wire:loading.remove wire:target="subidasOc" />
                <x-icon name="loader-circle" class="h-4 w-4 shrink-0 animate-spin text-slate-500" wire:loading wire:target="subidasOc" />
                <input type="file" multiple wire:model="subidasOc" class="sr-only" />
            </label>
            @error('subidasOc') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('subidasOc.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-campo.texto label="N° OC" modelo="oc.numero" />

        {{-- Una fecha pactada por cada entrega de cada producto y opcion. --}}
        @foreach ($entregasOc as $indice => $entrega)
            <div class="flex items-end gap-4" wire:key="oc-entrega-{{ $entrega['modelo'] }}">
                <div class="flex w-[110px] shrink-0 flex-col gap-1.5">
                    <span class="truncate text-sm text-slate-600" title="{{ $entrega['titulo'] }}">{{ $entrega['etiqueta'] }}</span>
                    <input readonly value="{{ $entrega['lugar'] }}" placeholder="Zona" aria-label="Lugar de la {{ $entrega['etiqueta'] }}" class="{{ $linea }} cursor-default" />
                </div>

                <div class="flex flex-1 flex-col gap-1.5">
                    <label for="entrega-fecha-{{ $indice }}" class="text-sm text-slate-600">Fecha de entrega</label>
                    <input id="entrega-fecha-{{ $indice }}" type="date" wire:model="{{ $entrega['modelo'] }}" class="{{ $linea }} campo-fecha" />
                </div>
            </div>
        @endforeach
    </div>

    @if ($archivosOc !== [])
        <div class="px-6 pb-6">
            <p class="mb-2 text-sm text-slate-600">Archivos de la OC</p>
            <ul class="divide-y divide-slate-100 rounded-md border border-slate-100">
                @foreach ($archivosOc as $indice => $archivo)
                    <li wire:key="oc-archivo-{{ $indice }}-{{ $archivo['ruta'] }}" class="flex items-center gap-3 px-4 py-2.5">
                        <x-icon name="file-text" class="h-4 w-4 shrink-0 text-slate-400" />
                        <span class="min-w-0 flex-1 truncate text-sm text-slate-800" title="{{ $archivo['nombre'] }}">{{ $archivo['nombre'] }}</span>
                        <span class="shrink-0 text-xs text-slate-400">{{ $peso($archivo['tamanio']) }} · {{ $archivo['subido'] }}</span>
                        <button type="button" wire:click="descargarArchivoOc({{ $indice }})" title="Descargar" aria-label="Descargar {{ $archivo['nombre'] }}" class="text-slate-400 hover:text-[#1c5480]">
                            <x-icon name="download" class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            wire:click="quitarArchivoOc({{ $indice }})"
                            wire:confirm="¿Quitar {{ $archivo['nombre'] }} de la OC?"
                            title="Quitar"
                            aria-label="Quitar {{ $archivo['nombre'] }}"
                            class="text-red-400 hover:text-red-600"
                        >
                            <x-icon name="trash-2" class="h-4 w-4" />
                        </button>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-slate-400">Se guardan con la cotización al tocar Guardar.</p>
        </div>
    @endif

    <div class="overflow-x-auto scroll-sutil px-6 pb-6">
        <table class="w-full min-w-[860px]">
            <thead>
                <tr class="border-y border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                    <th class="px-3 py-3 text-left font-medium">Tipo de producto</th>
                    <th class="px-3 py-3 text-left font-medium">Producto</th>
                    <th class="px-3 py-3 text-left font-medium">Características</th>
                    <th class="px-3 py-3 text-right font-medium">Cantidad</th>
                    <th class="px-3 py-3 text-right font-medium">Precio unitario</th>
                    <th class="px-3 py-3 text-right font-medium">Importe total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $indice => $fila)
                    <tr wire:key="oc-fila-{{ $indice }}" class="border-b border-slate-100 last:border-0 align-top">
                        <td class="px-3 py-4 text-sm text-slate-700">{{ $fila['tipo'] }}</td>
                        <td class="max-w-[220px] px-3 py-4 text-sm text-slate-700">
                            {{ $fila['producto'] }}
                            @if ($fila['opcion'])
                                <span class="mt-1 block text-xs text-slate-400">{{ $fila['opcion'] }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-700">
                            @forelse ($fila['caracteristicas'] as $caracteristica)
                                <p>{{ $caracteristica }}</p>
                            @empty
                                <p class="text-slate-300">-</p>
                            @endforelse
                        </td>
                        <td class="px-3 py-4 text-right text-sm whitespace-nowrap text-slate-600">{{ $fila['cantidad'] }}</td>
                        <td class="px-3 py-4 text-right text-sm whitespace-nowrap text-slate-600">{{ $fila['precio_unitario'] }}</td>
                        <td class="px-3 py-4 text-right text-sm whitespace-nowrap text-slate-600">{{ $fila['importe_total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
