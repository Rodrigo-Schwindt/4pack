@php
    use App\Support\Numero;

    $tarjeta = 'rounded-[10px] border border-[#E5E7EB] bg-white';
    // Como en Estadisticas: el calendario se abre tocando en cualquier parte del campo.
    $campoFecha = 'relative h-[34px] w-[128px] rounded-lg border border-[#E5E7EB] bg-white pr-2 pl-8 text-[12px] text-[#364153] focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0';

    // Las barras se miden contra el valor mas alto del rango; el tope deja
    // lugar para el "x kg" que va pegado al final de cada barra.
    $maximo = max($ingreso['cotizado'], $ingreso['oc'], $ingreso['entregado']);
    $ancho = fn (float $kg) => $maximo <= 0 || $kg <= 0 ? 0 : max($kg / $maximo * 78, 2);

    $filas = [
        ['etiqueta' => 'Cotizado', 'kg' => $ingreso['cotizado'], 'destacado' => false],
        ['etiqueta' => 'OC', 'kg' => $ingreso['oc'], 'destacado' => true],
        ['etiqueta' => 'Entregado', 'kg' => $ingreso['entregado'], 'destacado' => false],
    ];
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <h1 class="mb-6 text-2xl font-bold text-[#101828]">Dashboard</h1>

    <div class="grid items-start gap-6 lg:grid-cols-2">
        {{-- Kilos cotizados, con OC y entregados del rango elegido --}}
        <section class="{{ $tarjeta }} p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-[16px] leading-[normal] font-medium text-black">Ingreso de kg en OC</h2>

                <div class="flex items-end gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="desde" class="text-[11px] text-[#6A7282]">Desde</label>
                        <div class="relative">
                            <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-2.5 z-10 h-[14px] w-[14px] -translate-y-1/2 text-[#6A7282]" stroke="1.6" />
                            <input id="desde" type="date" wire:model.live="desde" class="{{ $campoFecha }}" />
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="hasta" class="text-[11px] text-[#6A7282]">Hasta</label>
                        <div class="relative">
                            <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-2.5 z-10 h-[14px] w-[14px] -translate-y-1/2 text-[#6A7282]" stroke="1.6" />
                            <input id="hasta" type="date" wire:model.live="hasta" class="{{ $campoFecha }}" />
                        </div>
                    </div>
                </div>
            </div>

            @error('desde') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('hasta') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

            <dl class="mt-8 flex flex-col gap-[16px]">
                @foreach ($filas as $fila)
                    <div class="flex items-center gap-2">
                        <dt class="w-[64px] shrink-0 text-[12px] text-[#364153]">{{ $fila['etiqueta'] }}</dt>

                        <dd class="flex min-w-0 flex-1 items-center gap-2.5">
                            @if ($fila['kg'] > 0)
                                <div
                                    class="h-2.5 shrink-0 rounded-full {{ $fila['destacado'] ? 'bg-[#22577C]' : 'bg-[#DBEAFE]' }}"
                                    style="width: {{ round($ancho($fila['kg']), 2) }}%"
                                ></div>
                            @endif

                            <span class="text-[12px] whitespace-nowrap {{ $fila['destacado'] ? 'font-semibold text-[#101828]' : 'text-[#6A7282]' }}">
                                {{ Numero::formato($fila['kg'], 0) }} kg
                            </span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Alertas de cotizaciones --}}
        <section class="{{ $tarjeta }} p-6">
            <h2 class="flex items-center gap-2 text-[16px] leading-6 font-semibold text-[#101828]">
                <x-icon name="triangle-alert" class="h-4 w-4 text-[#F20000]" />
                Alertas de cotizaciones
            </h2>
            <p class="mt-1 text-[13px] leading-5 text-[#6A7282]">Abiertas hace más de 7 días</p>

            <ul class="mt-6 flex flex-col gap-6">
                @foreach ($alertas as $alerta)
                    <li wire:key="alerta-{{ $alerta['id'] }}">
                        <a
                            href="{{ route('cotizaciones.edit', $alerta['id']) }}"
                            wire:navigate
                            class="flex items-center justify-between gap-4 rounded-[10px] border border-[#FDE8E8] bg-[#FFF8F8] px-4 py-3.5 transition-colors hover:border-[#F20000]/40"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-[12px] leading-[18px] font-semibold text-[#F20000]">{{ $alerta['codigo'] }}</span>
                                    <span class="rounded-[7.5px] bg-[#FEE1E1] px-[7px] text-[10px] leading-[15px] font-medium text-[#F20000]">{{ $alerta['estado'] }}</span>
                                </div>
                                <p class="mt-0.5 truncate text-[13px] leading-5 font-medium text-[#101828]">{{ $alerta['cliente'] }}</p>
                                <p class="text-[11px] leading-[17px] text-[#6A7282]">{{ Numero::corto($alerta['toneladas']) }} t solicitadas</p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-[20px] leading-[30px] font-bold text-[#F20000]">{{ $alerta['dias'] }}</p>
                                <p class="text-[10px] leading-[15px] text-[#6A7282]">días</p>
                            </div>
                        </a>
                    </li>
                @endforeach

                @if ($alertas->isEmpty())
                    <li class="rounded-[10px] border border-dashed border-[#E5E7EB] px-4 py-8 text-center text-sm text-slate-400">
                        No hay cotizaciones pendientes hace más de 7 días.
                    </li>
                @endif
            </ul>
        </section>
    </div>

    {{-- Nuevos prospectos --}}
    <section class="{{ $tarjeta }} mt-6 p-6">
        <h2 class="text-[16px] leading-[normal] font-medium text-black">Nuevos Prospectos</h2>
        <p class="mt-1.5 text-[13px] leading-5 text-[#6A7282]">Últimos 7 días - {{ count($prospectos) }} registros</p>

        <ul class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($prospectos as $prospecto)
                <li wire:key="prospecto-{{ $prospecto['id'] }}">
                    <a
                        href="{{ route('prospectos.edit', $prospecto['id']) }}"
                        wire:navigate
                        class="block h-[95px] rounded-[10px] border border-[#E5E7EB] bg-[#EDF4FD] px-3 py-3 transition-colors hover:border-[#1c5480]/40"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="truncate rounded-[7.5px] bg-[#DBEAFE] px-1.5 text-[10px] leading-4 font-medium text-[#136CD4]">{{ $prospecto['vendedor'] }}</span>
                            <span class="shrink-0 text-[11px] leading-4 whitespace-nowrap text-[#6A7282]">
                                @if ($prospecto['dias'] === 0)
                                    Hoy
                                @else
                                    Hace {{ $prospecto['dias'] }} {{ $prospecto['dias'] === 1 ? 'día' : 'días' }}
                                @endif
                            </span>
                        </div>

                        <p class="mt-3 truncate text-[13px] leading-5 font-medium text-[#101828]">{{ $prospecto['empresa'] }}</p>
                        <p class="truncate text-[11px] leading-[17px] text-[#6A7282]">Contacto: {{ $prospecto['contacto'] }}</p>
                    </a>
                </li>
            @endforeach

            @if ($prospectos->isEmpty())
                <li class="col-span-full rounded-[10px] border border-dashed border-[#E5E7EB] px-4 py-8 text-center text-sm text-slate-400">
                    No se cargaron prospectos en los últimos 7 días.
                </li>
            @endif
        </ul>
    </section>
</div>
