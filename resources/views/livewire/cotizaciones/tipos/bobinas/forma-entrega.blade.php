@php
    // Lo que se entrega tiene que repartir la Cantidad (mts) cargada en el producto.
    $trabado = $this->sinCantidad;
    // Con retiro en sucursal los campos de flete se ven pero quedan en gris.
    $retiro = $this->retiroEnSucursal;
    $sinFlete = $trabado || $retiro;
    $cantidad = (float) ($bobinas['cantidad'] ?: 0);
    $pendiente = $this->cantidadPendiente;
    $numero = fn (float $valor) => \App\Support\Numero::corto($valor);
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $chico = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-200 text-slate-500';
@endphp

<div class="px-6 py-6">
    @if ($trabado)
        <p class="mb-5 text-sm text-slate-500">
            Completá <span class="font-medium text-slate-700">Cantidad (mts)</span> en Datos de producto para cargar las entregas.
        </p>
    @endif

    <div class="mb-6 grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
        <x-campo.select
            label="Forma de entrega"
            modelo="bobinas.forma_entrega"
            :opciones="$opciones['formas_entrega']"
            live
            requerido
            :deshabilitado="$trabado"
            :ayuda="$retiro ? 'El cliente retira: no se cotiza flete' : null"
        />
    </div>

    @foreach ($entregas as $indice => $entrega)
        @php
            // La direccion depende de la zona: a Quilmes no le corresponde una de Caba.
            $zona = $entrega['flete_zona_id'] ?? '';
            $deLaZona = $this->direccionesDeZona($zona);
        @endphp

        <div wire:key="entrega-{{ $indice }}" class="{{ $indice > 0 ? 'mt-5' : '' }}">
            <div class="mb-2 flex items-center gap-3">
                <p class="text-xs text-slate-500">{{ $indice + 1 }}° entrega</p>
                @if (count($entregas) > 1)
                    <button
                        type="button"
                        wire:click="quitarEntrega({{ $indice }})"
                        @disabled($trabado)
                        aria-label="Quitar {{ $indice + 1 }}° entrega"
                        class="{{ $trabado ? 'cursor-default text-slate-200' : 'text-slate-300 hover:text-red-600' }}"
                    >
                        <x-icon name="trash-2" class="h-3.5 w-3.5" />
                    </button>
                @endif
            </div>

            <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
                {{-- Solo las zonas donde el cliente tiene direcciones agendadas. --}}
                @php
                    $ayudaFlete = match (true) {
                        $sinFlete => null,
                        $this->zonasCliente->isEmpty() => 'El cliente no tiene direcciones de entrega con zona: agregá una con el +',
                        $this->zonaSinPrecios($zona) => 'Esta zona todavía no tiene precios en Flete Insumos',
                        default => null,
                    };
                @endphp
                <x-campo.select
                    label="Flete"
                    :modelo="'entregas.'.$indice.'.flete_zona_id'"
                    :opciones="$this->zonasCliente"
                    live
                    :requerido="! $retiro"
                    :deshabilitado="$sinFlete"
                    :ayuda="$ayudaFlete"
                />

                {{-- Direccion + alta al vuelo: queda agendada en el cliente, como desde su ficha. --}}
                <div class="flex flex-col gap-1.5">
                    {{-- La ayuda va fuera del select para que el + quede a la altura del campo. --}}
                    <div class="flex items-end gap-2">
                        <x-campo.select
                            label="Dirección"
                            :modelo="'entregas.'.$indice.'.direccion_id'"
                            :opciones="$deLaZona"
                            :deshabilitado="$sinFlete || ! $zona"
                            class="flex-1"
                        />
                        <button
                            type="button"
                            wire:click="abrirDireccion({{ $indice }})"
                            @disabled($sinFlete)
                            title="Agregar dirección de entrega al cliente"
                            aria-label="Agregar dirección de entrega a la {{ $indice + 1 }}° entrega"
                            class="{{ $chico }} {{ $sinFlete ? 'cursor-default bg-slate-50 text-slate-300' : 'hover:bg-slate-50 hover:text-[#1c5480]' }}"
                        >
                            <x-icon name="plus" class="h-4 w-4" />
                        </button>
                    </div>

                    @unless ($sinFlete || $zona)
                        <p class="text-xs text-slate-400">Elegí primero el flete</p>
                    @endunless
                </div>

                <x-campo.texto
                    label="Cantidad"
                    :modelo="'entregas.'.$indice.'.cantidad'"
                    tipo="number"
                    paso="0.01"
                    modificador="live.blur"
                    :deshabilitado="$trabado"
                />

                <x-campo.select
                    label="kg / pallets"
                    :modelo="'entregas.'.$indice.'.flete_tramo_id'"
                    :opciones="$tramos"
                    :deshabilitado="$sinFlete"
                />
            </div>

            @if ($agregandoDireccionEn === $indice)
                <div class="mt-3 rounded-md border border-slate-200 bg-slate-50 p-4">
                    <p class="mb-3 text-sm font-medium text-slate-700">Nueva dirección de entrega</p>

                    <div class="grid gap-x-6 gap-y-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="nuevaDireccion.flete_zona_id" class="text-sm text-slate-600">Zona (flete)</label>
                            <div class="relative">
                                <select id="nuevaDireccion.flete_zona_id" wire:model="nuevaDireccion.flete_zona_id" class="{{ $campo }} appearance-none pr-9">
                                    <option value="">Elegir del catálogo...</option>
                                    @foreach ($zonasCatalogo as $id => $nombre)
                                        <option value="{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            </div>
                            @error('nuevaDireccion.flete_zona_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="nuevaDireccion.nueva_zona" class="text-sm text-slate-600">o zona nueva</label>
                            <input id="nuevaDireccion.nueva_zona" wire:model="nuevaDireccion.nueva_zona" placeholder="Ej.: Quilmes" class="{{ $campo }}" />
                            <p class="text-xs text-slate-400">Queda pendiente de precios en Flete Insumos.</p>
                            @error('nuevaDireccion.nueva_zona') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="nuevaDireccion.direccion" class="text-sm text-slate-600">Dirección <span class="text-red-500">*</span></label>
                            <input id="nuevaDireccion.direccion" autofocus wire:model="nuevaDireccion.direccion" wire:keydown.enter.prevent="guardarDireccion" wire:keydown.escape="cancelarDireccion" placeholder="Calle y número" class="{{ $campo }}" />
                            @error('nuevaDireccion.direccion') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="nuevaDireccion.codigo_postal" class="text-sm text-slate-600">Código postal</label>
                            <input id="nuevaDireccion.codigo_postal" wire:model="nuevaDireccion.codigo_postal" wire:keydown.enter.prevent="guardarDireccion" placeholder="CP" class="{{ $campo }}" />
                            @error('nuevaDireccion.codigo_postal') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col gap-1.5 md:col-span-2 xl:col-span-4">
                            <label for="nuevaDireccion.observaciones" class="text-sm text-slate-600">Observaciones</label>
                            <textarea id="nuevaDireccion.observaciones" wire:model="nuevaDireccion.observaciones" rows="2" placeholder="Horarios, referencias, contacto en destino..." class="{{ str_replace('h-10 ', 'min-h-10 py-2 ', $campo) }}"></textarea>
                            @error('nuevaDireccion.observaciones') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <button type="button" wire:click="guardarDireccion" class="flex h-9 items-center gap-2 rounded-md bg-[#1c5480] px-3 text-sm font-medium text-white hover:bg-[#174567]">
                            <x-icon name="check" class="h-4 w-4" />
                            Agregar dirección
                        </button>
                        <button type="button" wire:click="cancelarDireccion" class="flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-600 hover:bg-slate-50">
                            Cancelar
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endforeach

    {{-- Cuánto de la cantidad del producto queda por repartir. --}}
    @unless ($trabado)
        <p class="mt-4 text-xs {{ abs($pendiente) < 0.01 ? 'text-slate-500' : 'text-red-600' }}">
            Repartido {{ $numero($this->cantidadRepartida) }} de {{ $numero($cantidad) }} mts
            @if ($pendiente > 0.01)
                — faltan {{ $numero($pendiente) }}
            @elseif ($pendiente < -0.01)
                — te pasaste por {{ $numero(abs($pendiente)) }}
            @endif
        </p>
    @endunless

    <button
        type="button"
        wire:click="agregarEntrega"
        @disabled($trabado)
        class="mt-4 text-sm {{ $trabado ? 'cursor-default text-slate-300' : 'text-[#1c5480] hover:underline' }}"
    >
        + Agregar entrega
    </button>
</div>
