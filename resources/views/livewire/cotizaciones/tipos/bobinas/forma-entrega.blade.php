@php
    // Lo que se entrega tiene que repartir la Cantidad (mts) cargada en el producto.
    $trabado = $this->sinCantidad;
    $cantidad = (float) ($bobinas['cantidad'] ?: 0);
    $pendiente = $this->cantidadPendiente;
    $numero = fn (float $valor) => rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
@endphp

<div class="px-6 py-6">
    @if ($trabado)
        <p class="mb-5 text-sm text-slate-500">
            Completá <span class="font-medium text-slate-700">Cantidad (mts)</span> en Datos de producto para cargar las entregas.
        </p>
    @endif

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
                <x-campo.select
                    label="Flete"
                    :modelo="'entregas.'.$indice.'.flete_zona_id'"
                    :opciones="$this->zonasCliente"
                    live
                    requerido
                    :deshabilitado="$trabado"
                    :ayuda="$trabado || ! $this->zonasCliente->isEmpty() ? null : 'El cliente no tiene direcciones de entrega con zona'"
                />

                <x-campo.select
                    label="Dirección"
                    :modelo="'entregas.'.$indice.'.direccion_id'"
                    :opciones="$deLaZona"
                    :deshabilitado="$trabado || ! $zona"
                    :ayuda="$trabado || $zona ? null : 'Elegí primero el flete'"
                />

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
                    :deshabilitado="$trabado"
                />
            </div>
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
