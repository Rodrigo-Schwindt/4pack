@php
    $clases = 'flex flex-col items-center rounded-lg border border-slate-100 bg-white px-6 py-8 text-center shadow-sm';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Configuración del sistema</h1>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($tarjetas as $tarjeta)
            @if ($tarjeta['href'])
                <a href="{{ $tarjeta['href'] }}" wire:navigate class="{{ $clases }} transition-shadow hover:border-[#1c5480]/30 hover:shadow-md">
                    <x-icon :name="$tarjeta['icono']" class="h-7 w-7 text-slate-500" stroke="1.5" />
                    <p class="mt-4 text-lg font-semibold text-slate-900">{{ $tarjeta['titulo'] }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $tarjeta['descripcion'] }}</p>
                </a>
            @else
                <div title="Próximamente" class="{{ $clases }} cursor-default opacity-90">
                    <x-icon :name="$tarjeta['icono']" class="h-7 w-7 text-slate-500" stroke="1.5" />
                    <p class="mt-4 text-lg font-semibold text-slate-900">{{ $tarjeta['titulo'] }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $tarjeta['descripcion'] }}</p>
                </div>
            @endif
        @endforeach
    </div>
</div>
