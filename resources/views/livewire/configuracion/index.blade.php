@php
    $clases = 'flex flex-col items-center rounded-xl bg-white px-6 py-10 text-center shadow-[0_1px_3px_rgba(15,23,42,0.06)]';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Configuración del sistema</h1>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($tarjetas as $tarjeta)
            @if ($tarjeta['href'])
                <a href="{{ $tarjeta['href'] }}" wire:navigate class="{{ $clases }} group transition-shadow hover:shadow-[0_4px_16px_rgba(15,23,42,0.10)]">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 transition-colors group-hover:bg-[#1c5480]/10">
                        <x-icon :name="$tarjeta['icono']" class="h-7 w-7 text-slate-700 transition-colors group-hover:text-[#1c5480]" stroke="1.75" />
                    </span>
                    <p class="mt-5 text-xl font-bold text-slate-900">{{ $tarjeta['titulo'] }}</p>
                    <p class="mt-1.5 text-sm text-slate-500">{{ $tarjeta['descripcion'] }}</p>
                </a>
            @else
                <div title="Próximamente" class="{{ $clases }} cursor-default">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                        <x-icon :name="$tarjeta['icono']" class="h-7 w-7 text-slate-700" stroke="1.75" />
                    </span>
                    <p class="mt-5 text-xl font-bold text-slate-900">{{ $tarjeta['titulo'] }}</p>
                    <p class="mt-1.5 text-sm text-slate-500">{{ $tarjeta['descripcion'] }}</p>
                </div>
            @endif
        @endforeach
    </div>
</div>
