@php
    $campo = 'h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm text-slate-800 placeholder:text-slate-300 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $usd = fn ($valor) => \App\Support\Numero::usd($valor);
    $celda = 'border-l border-slate-100 px-3 py-3 text-center text-sm';
    $subtitulo = 'border-l border-slate-100 px-3 py-2 text-center text-[10px] font-medium tracking-wide text-slate-500 uppercase';

    // Ancho minimo de cada subcolumna: sin esto el select de Flete queda tan
    // angosto que se come el "Si" / "No".
    $anchos = [
        'costo' => 'min-w-[104px]',
        'peso_esp' => 'min-w-[104px]',
        'volumen' => 'min-w-[128px]',
        'flete' => 'min-w-[92px]',
        'donde' => 'min-w-[132px]',
        'costo_flete' => 'min-w-[112px]',
        'total' => 'min-w-[108px]',
    ];
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <a href="{{ route('insumos.index') }}" wire:navigate aria-label="Volver a Gestión de insumos" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $insumo->nombre }}</h1>

        <div class="ml-auto flex flex-wrap items-center gap-3">
            <button
                type="button"
                wire:click="abrirFamilia"
                class="flex h-10 items-center gap-2 rounded-md border border-slate-200 bg-white px-4 text-sm text-slate-600 hover:bg-slate-50"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nueva familia
            </button>

            @if ($familias->isNotEmpty())
                <button
                    type="button"
                    wire:click="abrirItem"
                    class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    Nuevo {{ $insumo->singular }}
                </button>
            @endif
        </div>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    @if ($creandoFamilia)
        <div class="mb-6 flex flex-wrap items-start gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div class="w-64">
                <input
                    autofocus
                    wire:model="familiaNombre"
                    wire:keydown.enter.prevent="guardarFamilia"
                    placeholder="Nombre de la familia"
                    aria-label="Nombre de la familia"
                    class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                />
                @error('familiaNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="button" wire:click="guardarFamilia" class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]">
                <x-icon name="check" class="h-4 w-4" />
                Crear familia
            </button>
            <button type="button" wire:click="cancelarEdiciones" class="flex h-10 items-center rounded-md border border-slate-200 bg-white px-4 text-sm text-slate-600 hover:bg-slate-50">
                Cancelar
            </button>
        </div>
    @endif

    @if ($creandoItem)
        <div class="mb-6 flex flex-wrap items-start gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div class="w-72">
                <input
                    autofocus
                    wire:model="itemNombre"
                    wire:keydown.enter.prevent="guardarItem"
                    placeholder="Nombre del {{ Str::lower($insumo->singular) }}"
                    aria-label="Nombre"
                    class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                />
                @error('itemNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="w-56">
                <select wire:model="itemFamiliaId" aria-label="Familia" class="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none">
                    @foreach ($familias as $opcion)
                        <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
                    @endforeach
                </select>
                @error('itemFamiliaId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="button" wire:click="guardarItem" class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567]">
                <x-icon name="check" class="h-4 w-4" />
                Agregar
            </button>
            <button type="button" wire:click="cancelarEdiciones" class="flex h-10 items-center rounded-md border border-slate-200 bg-white px-4 text-sm text-slate-600 hover:bg-slate-50">
                Cancelar
            </button>
        </div>
    @endif

    @foreach ($familias as $familia)
        @php
            $proveedores = $familia->proveedores;
            $abierto = $expandido[$familia->id] ?? null;
        @endphp

        <section wire:key="familia-{{ $familia->id }}" class="mb-10">
            <div class="mb-3 flex flex-wrap items-center gap-3">
                <h2 class="text-lg font-semibold text-slate-900">{{ $familia->nombre }}</h2>

                <button
                    type="button"
                    wire:click="abrirProveedor({{ $familia->id }})"
                    class="text-xs text-[#1c5480] hover:underline"
                >
                    + Proveedor
                </button>
                <button
                    type="button"
                    wire:click="abrirItem({{ $familia->id }})"
                    class="text-xs text-[#1c5480] hover:underline"
                >
                    + {{ $insumo->singular }}
                </button>
                <button
                    type="button"
                    wire:click="eliminarFamilia({{ $familia->id }})"
                    wire:confirm="Se borran también sus {{ Str::lower($insumo->nombre) }} y precios. ¿Eliminar la familia {{ $familia->nombre }}?"
                    aria-label="Eliminar familia {{ $familia->nombre }}"
                    class="text-slate-300 hover:text-red-600"
                >
                    <x-icon name="trash-2" class="h-3.5 w-3.5" />
                </button>
            </div>

            @error('volumenDesde.'.$familia->id) <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

            @if ($agregandoProveedorEn === $familia->id)
                <div class="mb-3 flex flex-wrap items-start gap-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                    <div class="w-56">
                        <select wire:model="proveedorId" aria-label="Proveedor del catálogo" class="h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none">
                            <option value="">Elegir del catálogo...</option>
                            @foreach ($catalogo as $opcion)
                                <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
                            @endforeach
                        </select>
                        @error('proveedorId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <span class="pt-2 text-xs text-slate-400">o</span>
                    <div class="w-56">
                        <input
                            wire:model="nuevoProveedor"
                            wire:keydown.enter.prevent="guardarProveedor"
                            placeholder="Proveedor nuevo"
                            aria-label="Proveedor nuevo"
                            class="{{ $campo }}"
                        />
                    </div>
                    <button type="button" wire:click="guardarProveedor" class="flex h-9 items-center gap-2 rounded-md bg-[#1c5480] px-3 text-sm font-medium text-white hover:bg-[#174567]">
                        <x-icon name="check" class="h-4 w-4" />
                        Agregar
                    </button>
                    <button type="button" wire:click="cancelarProveedor" class="flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-600 hover:bg-slate-50">
                        Cancelar
                    </button>
                </div>
            @endif

            <div class="overflow-x-auto scroll-sutil rounded-lg border border-slate-200 bg-white">
                <table class="w-full min-w-[900px] border-collapse text-left">
                    <thead>
                        <tr class="bg-slate-50/80">
                            <th rowspan="2" class="w-52 px-4 py-3 text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                                {{ $insumo->singular }}
                            </th>

                            @foreach ($proveedores as $proveedor)
                                <th
                                    wire:key="cab-{{ $familia->id }}-{{ $proveedor->id }}"
                                    colspan="{{ $abierto === $proveedor->id ? 7 : 1 }}"
                                    class="border-l border-slate-200 px-3 py-2 text-center text-[11px] font-medium tracking-wide text-slate-600 uppercase"
                                >
                                    @if ($editandoProveedor === $proveedor->id)
                                        <div class="flex items-center justify-center gap-2">
                                            <input
                                                autofocus
                                                wire:model="proveedorNombre"
                                                wire:keydown.enter.prevent="guardarProveedorEditado"
                                                wire:keydown.escape="cancelarEdiciones"
                                                aria-label="Nombre del proveedor"
                                                class="h-8 w-40 rounded-md border border-slate-200 px-2 text-xs text-slate-800 normal-case focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                                            />
                                            <button type="button" wire:click="guardarProveedorEditado" aria-label="Guardar proveedor" class="text-[#1c5480]">
                                                <x-icon name="check" class="h-3.5 w-3.5" />
                                            </button>
                                            <button type="button" wire:click="cancelarEdiciones" aria-label="Cancelar" class="text-slate-400">
                                                <x-icon name="x" class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                        @error('proveedorNombre') <p class="mt-1 text-[10px] normal-case text-red-600">{{ $message }}</p> @enderror
                                    @else
                                        <div class="flex items-center justify-center gap-2">
                                            @if ($abierto === $proveedor->id)
                                                <button type="button" wire:click="colapsar({{ $familia->id }})" aria-label="Contraer {{ $proveedor->nombre }}" class="text-slate-400 hover:text-slate-600">
                                                    <x-icon name="circle-minus" class="h-4 w-4" stroke="1.5" />
                                                </button>
                                            @else
                                                <button type="button" wire:click="expandir({{ $familia->id }}, {{ $proveedor->id }})" aria-label="Desplegar {{ $proveedor->nombre }}" class="text-slate-400 hover:text-[#1c5480]">
                                                    <x-icon name="circle-plus" class="h-4 w-4" stroke="1.5" />
                                                </button>
                                            @endif

                                            <span>{{ $proveedor->nombre }}</span>

                                            @if ($abierto === $proveedor->id)
                                                <button type="button" wire:click="editarProveedor({{ $proveedor->id }})" aria-label="Renombrar {{ $proveedor->nombre }}" class="text-slate-400 hover:text-[#1c5480]">
                                                    <x-icon name="square-pen" class="h-3.5 w-3.5" />
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="quitarProveedor({{ $familia->id }}, {{ $proveedor->id }})"
                                                    wire:confirm="Se quita la columna de {{ $familia->nombre }} y se borran sus precios. ¿Continuar?"
                                                    aria-label="Quitar {{ $proveedor->nombre }} de {{ $familia->nombre }}"
                                                    class="text-red-300 hover:text-red-600"
                                                >
                                                    <x-icon name="trash-2" class="h-3.5 w-3.5" />
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </th>
                            @endforeach

                            @if ($proveedores->isEmpty())
                                <th class="border-l border-slate-200 px-4 py-3 text-left text-xs font-normal text-slate-400 normal-case">
                                    Agregá un proveedor para empezar a cargar costos.
                                </th>
                            @endif
                        </tr>

                        <tr class="bg-slate-50/80">
                            @foreach ($proveedores as $proveedor)
                                @if ($abierto === $proveedor->id)
                                    <th class="{{ $subtitulo }} {{ $anchos['costo'] }}">Costo</th>
                                    <th class="{{ $subtitulo }} {{ $anchos['peso_esp'] }}" title="Dato del material: vale para todos sus proveedores">Peso esp.</th>
                                    {{-- El umbral en toneladas es de la familia y se guarda al salir del campo. --}}
                                    <th class="{{ $subtitulo }} {{ $anchos['volumen'] }}" title="Precio que rige desde esta cantidad de toneladas">
                                        <span class="flex items-center justify-center gap-1 whitespace-nowrap">
                                            Más de
                                            <input
                                                type="number"
                                                step="any"
                                                min="0.001"
                                                wire:model.blur="volumenDesde.{{ $familia->id }}"
                                                wire:keydown.enter.prevent="$refresh"
                                                aria-label="Toneladas desde las que rige el precio por volumen en {{ $familia->nombre }}"
                                                class="h-6 w-12 rounded border border-slate-200 bg-white px-1 text-center text-[11px] text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none {{ $errors->has('volumenDesde.'.$familia->id) ? 'border-red-400' : '' }}"
                                            />
                                            TN
                                        </span>
                                    </th>
                                    <th class="{{ $subtitulo }} {{ $anchos['flete'] }}">Flete</th>
                                    <th class="{{ $subtitulo }} {{ $anchos['donde'] }}">Dónde</th>
                                    <th class="{{ $subtitulo }} {{ $anchos['costo_flete'] }}">Costo flete</th>
                                    <th class="{{ $subtitulo }} {{ $anchos['total'] }}">Total</th>
                                @else
                                    <th class="{{ $subtitulo }} {{ $anchos['total'] }}">Total</th>
                                @endif
                            @endforeach

                            @if ($proveedores->isEmpty())
                                <th class="border-l border-slate-100"></th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($familia->items as $item)
                            @php $precios = $item->preciosPorProveedor(); @endphp

                            <tr wire:key="item-{{ $item->id }}" class="border-t border-slate-100">
                                <td class="px-4 py-3">
                                    @if ($editandoItem === $item->id)
                                        <div class="flex items-center gap-2">
                                            <input
                                                autofocus
                                                wire:model="itemNombreEditado"
                                                wire:keydown.enter.prevent="guardarItemEditado"
                                                wire:keydown.escape="cancelarEdiciones"
                                                aria-label="Nombre"
                                                class="{{ $campo }}"
                                            />
                                            <button type="button" wire:click="guardarItemEditado" aria-label="Guardar nombre" class="text-[#1c5480]">
                                                <x-icon name="check" class="h-4 w-4" />
                                            </button>
                                            <button type="button" wire:click="cancelarEdiciones" aria-label="Cancelar" class="text-slate-400">
                                                <x-icon name="x" class="h-4 w-4" />
                                            </button>
                                        </div>
                                        @error('itemNombreEditado') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    @else
                                        <div class="group flex items-center gap-2">
                                            <span class="text-sm text-slate-700">{{ $item->nombre }}</span>
                                            <button
                                                type="button"
                                                wire:click="editarItem({{ $item->id }})"
                                                aria-label="Renombrar {{ $item->nombre }}"
                                                class="text-slate-300 opacity-0 group-hover:opacity-100 hover:text-[#1c5480]"
                                            >
                                                <x-icon name="square-pen" class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="eliminarItem({{ $item->id }})"
                                                wire:confirm="¿Eliminar {{ $item->nombre }} y sus precios?"
                                                aria-label="Eliminar {{ $item->nombre }}"
                                                class="text-slate-300 opacity-0 group-hover:opacity-100 hover:text-red-600"
                                            >
                                                <x-icon name="trash-2" class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                @foreach ($proveedores as $proveedor)
                                    @php
                                        $elegido = $item->proveedor_elegido_id === $proveedor->id;
                                        $fondo = $elegido ? 'bg-sky-50' : '';
                                    @endphp

                                    @if ($abierto === $proveedor->id)
                                        @php
                                            $datos = $fila[$item->id.'_'.$proveedor->id] ?? ['costo' => '', 'costo_volumen' => '', 'flete' => 0, 'donde' => '', 'costo_flete' => ''];
                                            $conFlete = (int) $datos['flete'] === 1;
                                            $total = $datos['costo'] === '' || ! is_numeric($datos['costo'])
                                                ? null
                                                : (float) $datos['costo'] + ($conFlete && is_numeric($datos['costo_flete']) ? (float) $datos['costo_flete'] : 0);
                                        @endphp

                                        <td class="{{ $celda }} {{ $anchos['costo'] }}">
                                            <input type="number" step="0.0001" min="0" wire:model.live="fila.{{ $item->id }}_{{ $proveedor->id }}.costo" placeholder="-" aria-label="Costo {{ $item->nombre }} {{ $proveedor->nombre }}" class="{{ $campo }} text-center" />
                                        </td>
                                        <td class="{{ $celda }} {{ $anchos['peso_esp'] }}">
                                            <input type="number" step="any" min="0" wire:model.live="fila.{{ $item->id }}_{{ $proveedor->id }}.peso_especifico" placeholder="-" title="Peso específico del material: vale para todos sus proveedores" aria-label="Peso específico {{ $item->nombre }}" class="{{ $campo }} text-center" />
                                        </td>
                                        <td class="{{ $celda }} {{ $anchos['volumen'] }}">
                                            <input type="number" step="0.0001" min="0" wire:model="fila.{{ $item->id }}_{{ $proveedor->id }}.costo_volumen" placeholder="-" aria-label="Precio por volumen {{ $item->nombre }} {{ $proveedor->nombre }}" class="{{ $campo }} text-center" />
                                        </td>
                                        <td class="{{ $celda }} {{ $anchos['flete'] }}">
                                            <select wire:model.live="fila.{{ $item->id }}_{{ $proveedor->id }}.flete" aria-label="Flete {{ $item->nombre }} {{ $proveedor->nombre }}" class="{{ $campo }} pr-1">
                                                <option value="0">No</option>
                                                <option value="1">Si</option>
                                            </select>
                                        </td>
                                        <td class="{{ $celda }} {{ $anchos['donde'] }}">
                                            <input wire:model="fila.{{ $item->id }}_{{ $proveedor->id }}.donde" placeholder="-" @disabled(! $conFlete) aria-label="Dónde {{ $item->nombre }} {{ $proveedor->nombre }}" class="{{ $campo }} text-center disabled:bg-slate-50 disabled:text-slate-300" />
                                        </td>
                                        <td class="{{ $celda }} {{ $anchos['costo_flete'] }}">
                                            <input type="number" step="0.0001" min="0" wire:model.live="fila.{{ $item->id }}_{{ $proveedor->id }}.costo_flete" placeholder="-" @disabled(! $conFlete) aria-label="Costo flete {{ $item->nombre }} {{ $proveedor->nombre }}" class="{{ $campo }} text-center disabled:bg-slate-50 disabled:text-slate-300" />
                                        </td>
                                        <td class="{{ $celda }} {{ $anchos['total'] }} {{ $fondo }}">
                                            <button
                                                type="button"
                                                wire:click="elegir({{ $item->id }}, {{ $proveedor->id }})"
                                                title="{{ $elegido ? 'Proveedor elegido' : 'Marcar como proveedor elegido' }}"
                                                class="w-full rounded px-2 py-1 {{ $elegido ? 'font-medium text-sky-900' : 'text-slate-500 hover:bg-slate-50' }}"
                                            >
                                                {{ $usd($total) }}
                                            </button>
                                        </td>
                                    @else
                                        <td class="{{ $celda }} {{ $anchos['total'] }} {{ $fondo }}">
                                            <button
                                                type="button"
                                                wire:click="elegir({{ $item->id }}, {{ $proveedor->id }})"
                                                title="{{ $elegido ? 'Proveedor elegido' : 'Marcar como proveedor elegido' }}"
                                                class="w-full rounded px-2 py-1 {{ $elegido ? 'font-medium text-sky-900' : 'text-slate-500 hover:bg-slate-50' }}"
                                            >
                                                {{ $usd($precios[$proveedor->id]->total ?? null) }}
                                            </button>
                                        </td>
                                    @endif
                                @endforeach

                                @if ($proveedores->isEmpty())
                                    <td class="border-l border-slate-100"></td>
                                @endif
                            </tr>
                        @endforeach

                        @if ($familia->items->isEmpty())
                            <tr class="border-t border-slate-100">
                                <td colspan="{{ max($proveedores->count(), 1) * 7 + 1 }}" class="px-4 py-8 text-center text-sm text-slate-400">
                                    Todavía no hay {{ Str::lower($insumo->nombre) }} en esta familia.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            @if ($abierto && $familia->items->isNotEmpty())
                <div class="mt-3">
                    <button
                        type="button"
                        wire:click="guardarPrecios({{ $familia->id }})"
                        wire:loading.attr="disabled"
                        class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-4 text-sm font-medium text-white hover:bg-[#174567] disabled:opacity-70"
                    >
                        <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="guardarPrecios({{ $familia->id }})" />
                        Guardar {{ $proveedores->firstWhere('id', $abierto)?->nombre }}
                    </button>
                </div>
            @endif
        </section>
    @endforeach

    @if ($familias->isEmpty())
        <div class="rounded-lg bg-white p-10 text-center text-sm text-slate-400 shadow-sm">
            Todavía no hay familias cargadas. Empezá creando una (por ejemplo, Polietileno).
        </div>
    @endif
</div>
