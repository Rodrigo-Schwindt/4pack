@php
    $APROBADO = '#1c5480';
    $FALTANTE = '#dbeafe';

    $faltan = max($toneladas['objetivo'] - $toneladas['aprobadas'], 0);

    // Donut de toneladas: arco proporcional a lo aprobado sobre el objetivo.
    $radio = 70;
    $circunferencia = 2 * M_PI * $radio;
    $proporcion = min($toneladas['aprobadas'] / $toneladas['objetivo'], 1);
    $arco = $circunferencia * $proporcion;
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Dashboard</h1>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Toneladas aprobadas hoy --}}
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Toneladas aprobadas hoy</h2>
            <p class="text-xs text-slate-400">Objetivo diario: {{ $toneladas['objetivo'] }} toneladas</p>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-8">
                <svg viewBox="0 0 200 200" class="h-56 w-56" role="img" aria-label="{{ $toneladas['aprobadas'] }} de {{ $toneladas['objetivo'] }} toneladas aprobadas hoy">
                    <g transform="rotate(-90 100 100)" fill="none" stroke-width="34">
                        <circle cx="100" cy="100" r="{{ $radio }}" stroke="{{ $FALTANTE }}" />
                        <circle cx="100" cy="100" r="{{ $radio }}" stroke="{{ $APROBADO }}" stroke-dasharray="{{ $arco }} {{ $circunferencia - $arco }}" />
                    </g>
                    <text x="100" y="100" text-anchor="middle" dominant-baseline="central" class="fill-slate-900 text-[28px]">
                        {{ $toneladas['aprobadas'] }} t
                    </text>
                </svg>

                <dl class="flex flex-col gap-6">
                    <div>
                        <dt class="flex items-center gap-2 text-base text-slate-600">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $APROBADO }}"></span>
                            Aprobado hoy
                        </dt>
                        <dd class="mt-1 text-xl font-bold" style="color: {{ $APROBADO }}">{{ $toneladas['aprobadas'] }} t</dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-2 text-base text-slate-600">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $FALTANTE }}"></span>
                            Faltan
                        </dt>
                        <dd class="mt-1 text-xl font-bold" style="color: {{ $APROBADO }}">{{ $faltan }} t</dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Alertas de cotizaciones --}}
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="flex items-center gap-2 font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">
                <x-icon name="triangle-alert" class="h-4 w-4 text-red-600" />
                Alertas de cotizaciones
            </h2>
            <p class="text-xs text-slate-400">Abiertas hace más de 7 días</p>

            <ul class="mt-4 flex flex-col gap-3">
                @foreach ($alertas as $alerta)
                    <li class="flex items-center justify-between rounded-md border border-red-100 bg-red-50/60 px-4 py-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-red-600">{{ $alerta['codigo'] }}</span>
                                <span class="rounded bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-700">{{ $alerta['estado'] }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-800">{{ $alerta['cliente'] }}</p>
                            <p class="text-xs text-slate-400">{{ $alerta['toneladas'] }} t solicitadas</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-red-600">{{ $alerta['dias'] }}</p>
                            <p class="text-[10px] text-slate-400">días</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- Nuevos prospectos --}}
    <section class="mt-6 rounded-lg bg-white p-6 shadow-sm">
        <h2 class="font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Nuevos Prospectos</h2>
        <p class="text-xs text-slate-400">Últimos 7 días - {{ count($prospectos) }} registros</p>

        <ul class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($prospectos as $prospecto)
                <li wire:key="prospecto-{{ $prospecto['id'] }}">
                    <a
                        href="{{ route('prospectos.edit', $prospecto['id']) }}"
                        wire:navigate
                        class="block rounded-md border border-blue-100 bg-[#EDF4FD] px-4 py-3 transition-colors hover:border-[#1c5480]/40 hover:bg-[#e3eefb]"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="rounded bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-800">{{ $prospecto['vendedor'] }}</span>
                            <span class="text-xs whitespace-nowrap text-slate-500">
                                @if ($prospecto['dias'] === 0)
                                    Hoy
                                @else
                                    Hace {{ $prospecto['dias'] }} {{ $prospecto['dias'] === 1 ? 'día' : 'días' }}
                                @endif
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-800">{{ $prospecto['empresa'] }}</p>
                        <p class="text-xs text-slate-400">Contacto: {{ $prospecto['contacto'] }}</p>
                    </a>
                </li>
            @endforeach

            @if ($prospectos->isEmpty())
                <li class="col-span-full rounded-md border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-400">
                    No se cargaron prospectos en los últimos 7 días.
                </li>
            @endif
        </ul>
    </section>
</div>
