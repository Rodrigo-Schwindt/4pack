@php
    // Lo que se entrega tiene que repartir la Cantidad (mts) cargada en el producto.
    $cantidad = (float) ($bobinas['cantidad'] ?: 0);
    $pendiente = $this->cantidadPendiente;
    $numero = fn (float $valor) => rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
@endphp

<div class="px-6 py-6">
    @if ($this->sinCantidad)
        <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
            <p class="text-sm text-slate-500">
                Completá <span class="font-medium text-slate-700">Cantidad (mts)</span> en Datos de producto para repartir la entrega.
            </p>
        </div>
    @else
        @foreach ($entregas as $indice => $entrega)
            <div wire:key="entrega-{{ $indice }}" class="{{ $indice > 0 ? 'mt-5' : '' }}">
                <div class="mb-2 flex items-center gap-3">
                    <p class="text-xs text-slate-500">{{ $indice + 1 }}° entrega</p>
                    @if (count($entregas) > 1)
                        <button
                            type="button"
                            wire:click="quitarEntrega({{ $indice }})"
                            aria-label="Quitar {{ $indice + 1 }}° entrega"
                            class="text-slate-300 hover:text-red-600"
                        >
                            <x-icon name="trash-2" class="h-3.5 w-3.5" />
                        </button>
                    @endif
                </div>

                @php
                    // La direccion depende de la zona: a Quilmes no le corresponde una de Caba.
                    $zona = $entrega['flete_zona_id'] ?? '';
                    $deLaZona = $this->direccionesDeZona($zona);
                @endphp

                <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
                    {{-- Solo las zonas donde el cliente tiene direcciones agendadas. --}}
                    <x-campo.select
                        label="Flete"
                        :modelo="'entregas.'.$indice.'.flete_zona_id'"
                        :opciones="$this->zonasCliente"
                        live
                        :ayuda="$this->zonasCliente->isEmpty() ? 'El cliente no tiene direcciones de entrega con zona' : null"
                    />

                    <x-campo.select
                        label="Dirección"
                        :modelo="'entregas.'.$indice.'.direccion_id'"
                        :opciones="$deLaZona"
                        :deshabilitado="! $zona"
                        :ayuda="$zona ? null : 'Elegí primero el flete'"
                    />

                    <x-campo.texto
                        label="Cantidad"
                        :modelo="'entregas.'.$indice.'.cantidad'"
                        tipo="number"
                        paso="0.01"
                        modificador="live.blur"
                    />

                    <x-campo.select label="kg / pallets" :modelo="'entregas.'.$indice.'.flete_tramo_id'" :opciones="$tramos" />
                </div>
            </div>
        @endforeach

        {{-- Cuánto de la cantidad del producto queda por repartir. --}}
        <p class="mt-4 text-xs {{ abs($pendiente) < 0.01 ? 'text-slate-500' : 'text-red-600' }}">
            Repartido {{ $numero($this->cantidadRepartida) }} de {{ $numero($cantidad) }} mts
            @if ($pendiente > 0.01)
                — faltan {{ $numero($pendiente) }}
            @elseif ($pendiente < -0.01)
                — te pasaste por {{ $numero(abs($pendiente)) }}
            @endif
        </p>

        <button type="button" wire:click="agregarEntrega" class="mt-4 text-sm text-[#1c5480] hover:underline">
            + Agregar entrega
        </button>
    @endif
</div>
