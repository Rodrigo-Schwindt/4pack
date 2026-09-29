{{-- Campos de una tarjeta de la cotizacion: de a dos por fila, y abajo las entregas. --}}
@if ($campos !== [])
    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2">
        @foreach ($campos as $nombre => [$titulo, $valor, $ejemplo])
            <div class="flex flex-col gap-1.5">
                <label for="{{ $clave }}-{{ $nombre }}" class="{{ $etiqueta }}">{{ $titulo }}</label>
                <input id="{{ $clave }}-{{ $nombre }}" readonly value="{{ $valor }}" placeholder="{{ $ejemplo }}" class="{{ $casilla }}" />
            </div>
        @endforeach
    </div>
@endif

@foreach ($entregas as $indice => [$lugar, $cantidad, $direccion])
    <div wire:key="{{ $clave }}-entrega-{{ $indice }}" class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6">
        <span class="{{ $etiqueta }} w-[140px] shrink-0">Entrega {{ $indice + 1 }}</span>
        <input readonly value="{{ $lugar }}" placeholder="Zona" aria-label="Lugar de la entrega {{ $indice + 1 }}" class="{{ $casilla }}" />
        <input readonly value="{{ $cantidad }}" placeholder="Cantidad" aria-label="Cantidad de la entrega {{ $indice + 1 }}" class="{{ $casilla }}" />
        <input readonly value="{{ $direccion }}" placeholder="Dirección" aria-label="Dirección de la entrega {{ $indice + 1 }}" class="{{ $casilla }}" />
    </div>
@endforeach
