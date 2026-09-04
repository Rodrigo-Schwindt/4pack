@props(['actividades'])

{{-- Linea de tiempo del contacto. La entrada mas reciente va arriba y en tono fuerte. --}}
<section class="mt-8">
    <h2 class="mb-4 font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Actividad</h2>

    <ol class="relative">
        @foreach ($actividades as $indice => $actividad)
            <li class="relative flex gap-4 pb-6 last:pb-0">
                <div class="flex flex-col items-center">
                    <span class="mt-1.5 h-2.5 w-2.5 rounded-full {{ $indice === 0 ? 'bg-slate-500' : 'bg-slate-300' }}"></span>
                    @if ($indice < count($actividades) - 1)
                        <span class="mt-1 w-px flex-1 bg-slate-200"></span>
                    @endif
                </div>
                <div class="{{ $indice === 0 ? 'text-slate-800' : 'text-slate-400' }}">
                    <p class="text-sm font-semibold">{{ $actividad['fecha'] }}</p>
                    <p class="text-sm">
                        {{ $actividad['descripcion'] }}{{ $actividad['autor'] ? ' - '.$actividad['autor'] : '' }}
                    </p>
                </div>
            </li>
        @endforeach

        @if (count($actividades) === 0)
            <li class="text-sm text-slate-400">Todavía no hay actividad registrada.</li>
        @endif
    </ol>
</section>
