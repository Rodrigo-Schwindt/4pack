@php
    // Como en el Figma: una tarjeta por producto. Si el producto se duplico,
    // arriba va lo que comparten sus opciones y cada "Opción" muestra solo lo
    // que cambia (cantidad y precio siempre). Todo sale de Datos y Costos.
    $casilla = 'h-10 w-full min-w-0 cursor-default rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none';
    $etiqueta = 'text-sm text-slate-500';
    $aprobada = $guardada?->estado === \App\Models\Cotizacion::APROBADA;
    $bloques = $this->bloquesCotizacion;
@endphp

@foreach ($bloques as $bloque)
    <section wire:key="cotizacion-{{ $bloque['numero'] }}" class="{{ $loop->first ? '' : 'mt-6' }} rounded-lg bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
            <div class="flex items-center gap-3">
                <h2 class="text-[16px] leading-[normal] font-medium text-black">Cotización {{ $bloque['numero'] }}</h2>
                @if ($aprobada && $loop->first)
                    <span class="rounded-md bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                        Aprobada el {{ $guardada->aprobada_en->format('d/m/Y H:i') }}
                    </span>
                @endif
            </div>

            {{-- Se aprueba la cotizacion entera: el boton va una sola vez. --}}
            @if ($loop->first)
                <button
                    type="button"
                    wire:click="aprobar"
                    wire:loading.attr="disabled"
                    @disabled($aprobada)
                    class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-8 text-sm font-medium text-white hover:bg-[#174567] disabled:cursor-default disabled:opacity-60"
                >
                    <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="aprobar" />
                    {{ $aprobada ? 'Aprobada' : 'Aprobar' }}
                </button>
            @endif
        </div>
        <hr class="border-slate-100" />

        <div class="flex flex-col gap-5 px-6 py-6">
            @include('livewire.cotizaciones.tipos.bobinas._cotizacion-campos', ['campos' => $bloque['campos'], 'entregas' => $bloque['entregas'], 'clave' => 'c'.$bloque['numero']])
        </div>
    </section>

    @foreach ($bloque['opciones'] as $opcion)
        <section wire:key="cotizacion-{{ $bloque['numero'] }}-opcion-{{ $opcion['numero'] }}" class="mt-6 rounded-lg bg-white shadow-sm">
            <h2 class="px-6 py-5 text-[16px] leading-[normal] font-medium text-black">Opción {{ $opcion['numero'] }}</h2>
            <hr class="border-slate-100" />

            <div class="flex flex-col gap-5 px-6 py-6">
                @include('livewire.cotizaciones.tipos.bobinas._cotizacion-campos', ['campos' => $opcion['campos'], 'entregas' => $opcion['entregas'], 'clave' => 'c'.$bloque['numero'].'o'.$opcion['numero']])
            </div>
        </section>
    @endforeach
@endforeach
