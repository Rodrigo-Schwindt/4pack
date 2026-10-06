@php
    use App\Support\Numero;

    $tarjeta = 'rounded-[4px] bg-white shadow-[2px_2px_15px_0px_rgba(0,0,0,0.15)]';
    $etiqueta = 'text-[14px] leading-[17px] font-medium text-[#0C0C0C]';
    $fecha = 'relative pr-3 pl-[45px] [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0';
    $campo = 'h-[46px] w-full rounded-[4px] border border-[#D9D9D9] bg-white px-2 text-[14px] text-[#5C5C5C] focus:border-[#22577C] focus:ring-1 focus:ring-[#22577C] focus:outline-none';

    // Escala de los graficos de barras: siempre la misma cantidad de tramos,
    // con el paso redondo mas chico que alcanza al maximo (0-20 de a 5, 0-70 de a 10).
    $escala = function (int $maximo, int $tramos): array {
        foreach ([1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000, 2000, 5000] as $paso) {
            if ($maximo <= $paso * $tramos) {
                return [$paso, $paso * $tramos];
            }
        }

        $paso = (int) ceil($maximo / $tramos);

        return [$paso, $paso * $tramos];
    };

    $indicadores = [
        ['clave' => 'procesadas', 'titulo' => 'Cotizaciones procesadas', 'icono' => 'file-text', 'fondo' => '#F0EAFD', 'color' => '#2900C1'],
        ['clave' => 'aprobadas', 'titulo' => 'Cotizaciones aprobadas', 'icono' => 'circle-check', 'fondo' => '#E0F7E5', 'color' => '#2D966A'],
        ['clave' => 'rechazadas', 'titulo' => 'Cotizaciones rechazadas', 'icono' => 'circle-x', 'fondo' => '#FEEBEB', 'color' => '#D52836'],
        ['clave' => 'con_oc', 'titulo' => 'Cotizaciones con OC', 'icono' => 'file-text', 'fondo' => '#EDF4FD', 'color' => '#136CD4'],
        ['clave' => 'prospectos', 'titulo' => 'Nuevos prospectos', 'icono' => 'user', 'fondo' => '#FEF0E0', 'color' => '#ED6002'],
        ['clave' => 'sin_cotizar', 'titulo' => 'Clientes sin cotizaciones', 'icono' => 'circle-x', 'fondo' => '#FEEBEB', 'color' => '#D52836'],
    ];

    // Donut de OC sobre lo aprobado.
    $radio = 49.5;
    $circunferencia = 2 * M_PI * $radio;
    $proporcionOc = $oc['total'] > 0 ? $oc['con'] / $oc['total'] : 0;
    $pct = fn (int $parte) => $oc['total'] > 0 ? (int) round($parte / $oc['total'] * 100) : 0;
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <h1 class="mb-6 text-2xl font-bold text-[#101828]">Estadísticas</h1>

    {{-- Filtros: cuentan recien al aplicar --}}
    <form wire:submit="aplicar" class="flex flex-wrap items-end gap-6">
        <div class="flex w-full flex-col gap-2 sm:w-[193px]">
            <label for="vendedor" class="{{ $etiqueta }}">Vendedores</label>
            <div class="relative">
                <select id="vendedor" wire:model="vendedor" class="{{ $campo }} appearance-none pr-8">
                    <option value="">Todos los vendedores</option>
                    @foreach ($vendedores as $opcion)
                        <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
                    @endforeach
                </select>
                <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#5C5C5C]" />
            </div>
            @error('vendedor') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex w-full flex-col gap-2 sm:w-[193px]">
            <label for="cliente" class="{{ $etiqueta }}">Clientes</label>
            <div class="relative">
                <select id="cliente" wire:model="cliente" class="{{ $campo }} appearance-none pr-8">
                    <option value="">Todos los clientes</option>
                    @foreach ($clientes as $id => $razonSocial)
                        <option value="{{ $id }}">{{ $razonSocial }}</option>
                    @endforeach
                </select>
                <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#5C5C5C]" />
            </div>
            @error('cliente') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex w-full flex-col gap-2 sm:w-[217px]">
            <label for="desde" class="{{ $etiqueta }}">Desde</label>
            <div class="relative">
                <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-[17px] z-10 h-[14px] w-[14px] -translate-y-1/2 text-[#5C5C5C]" stroke="1.6" />
                <input id="desde" type="date" wire:model="desde" class="{{ $campo }} {{ $fecha }}" />
            </div>
            @error('desde') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex w-full flex-col gap-2 sm:w-[217px]">
            <label for="hasta" class="{{ $etiqueta }}">Hasta</label>
            <div class="relative">
                <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-[17px] z-10 h-[14px] w-[14px] -translate-y-1/2 text-[#5C5C5C]" stroke="1.6" />
                <input id="hasta" type="date" wire:model="hasta" class="{{ $campo }} {{ $fecha }}" />
            </div>
            @error('hasta') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-6 pb-[3px] xl:ml-auto">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="flex h-10 w-[141px] items-center justify-center gap-2 rounded-[4px] bg-[#22577C] text-[16px] font-medium text-white hover:bg-[#1c4868] disabled:opacity-70"
            >
                <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="aplicar" />
                Aplicar filtros
            </button>
            <button
                type="button"
                wire:click="limpiar"
                class="h-10 w-[141px] rounded-[4px] border border-[#22577C] bg-white text-[16px] font-medium text-[#22577C] hover:bg-[#EDF4FD]"
            >
                Limpiar filtros
            </button>
        </div>
    </form>

    {{-- Indicadores --}}
    <div class="mt-6 grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($indicadores as $indicador)
            @php
                $datos = $tarjetas[$indicador['clave']];
                $tendencia = $datos['tendencia'] ?? null;
                $conDesglose = array_key_exists('bobinas_kg', $datos);
            @endphp

            <article wire:key="indicador-{{ $indicador['clave'] }}" class="{{ $tarjeta }} flex min-h-[208px] flex-col p-2">
                <span class="flex h-[50px] w-[50px] items-center justify-center rounded-full" style="background-color: {{ $indicador['fondo'] }}">
                    <x-icon :name="$indicador['icono']" class="h-5 w-5" stroke="1.6" style="color: {{ $indicador['color'] }}" />
                </span>

                <h2 class="mt-[11px] text-[12px] leading-[12px] font-medium text-[#0C0C0C]">{{ $indicador['titulo'] }}</h2>
                <p class="mt-[19px] text-[36px] leading-[36px] font-bold text-[#0C0C0C]">{{ Numero::formato($datos['cantidad'], 0) }}</p>

                @if ($conDesglose)
                    <dl class="mt-[3px] text-[12px] leading-[15px] font-medium text-[#0C0C0C]">
                        <div class="flex justify-between gap-2"><dt>Bobinas:</dt><dd>{{ Numero::formato($datos['bobinas_kg'], 0) }} Kg</dd></div>
                        <div class="flex justify-between gap-2"><dt>Envases:</dt><dd>{{ Numero::formato($datos['envases_kg'], 0) }} Kg</dd></div>
                        <div class="flex justify-end"><dd>{{ Numero::formato($datos['envases_ud'], 0) }} Ud</dd></div>
                    </dl>
                @endif

                <p class="mt-auto mb-[5px] flex items-center gap-1 text-[10px] leading-[12px] text-[#5C5C5C]">
                    @if ($indicador['clave'] === 'procesadas')
                        en el período seleccionado
                    @elseif ($tendencia === null)
                        sin datos del período anterior
                    @else
                        <x-icon :name="$tendencia >= 0 ? 'arrow-up' : 'arrow-down'" class="h-2.5 w-2.5 shrink-0 {{ $tendencia >= 0 ? 'text-[#1A864E]' : 'text-[#F20000]' }}" />
                        <span class="{{ $tendencia >= 0 ? 'text-[#1A864E]' : 'text-[#F20000]' }}">{{ $tendencia }}%</span>
                        vs. el período anterior
                    @endif
                </p>
            </article>
        @endforeach
    </div>

    {{-- Graficos --}}
    <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-[288fr_288fr_600fr]">
        {{-- Con vs sin OC, sobre lo aprobado --}}
        <section class="{{ $tarjeta }} min-h-[208px] p-2">
            <h2 class="pt-px text-[12px] leading-[12px] font-medium text-[#0C0C0C]">Cotizaciones Con vs Sin OC</h2>

            @if ($oc['total'] === 0)
                <p class="flex h-[160px] items-center justify-center text-center text-xs text-slate-400">No hay cotizaciones aprobadas en el período.</p>
            @else
                <div class="mt-[30px] flex items-center gap-6 pl-[13px]">
                    <svg viewBox="0 0 128 128" class="h-32 w-32 shrink-0" role="img" aria-label="{{ $oc['con'] }} con OC y {{ $oc['sin'] }} sin OC de {{ $oc['total'] }} aprobadas">
                        <g transform="rotate(-90 64 64)" fill="none" stroke-width="29">
                            <circle cx="64" cy="64" r="{{ $radio }}" stroke="#B9D8F9" />
                            @if ($oc['con'] > 0)
                                <circle cx="64" cy="64" r="{{ $radio }}" stroke="#22577C" stroke-dasharray="{{ $circunferencia * $proporcionOc }} {{ $circunferencia }}" />
                            @endif
                        </g>
                        <text x="64" y="60" text-anchor="middle" class="fill-[#0C0C0C] text-[16px] font-medium">{{ $oc['total'] }}</text>
                        <text x="64" y="74" text-anchor="middle" class="fill-[#5C5C5C] text-[10px]">aprobadas</text>
                    </svg>

                    <dl class="flex flex-col gap-[38px] text-[10px] leading-[12px] text-[#0C0C0C]">
                        <div>
                            <dt class="flex items-center gap-1.5"><span class="h-[7px] w-[7px] rounded-full bg-[#0172AD]"></span>Con OC</dt>
                            <dd class="mt-1.5 text-[12px] text-[#5C5C5C]"><span class="font-semibold text-[#0C0C0C]">{{ $oc['con'] }}</span> ({{ $pct($oc['con']) }}%)</dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1.5"><span class="h-[7px] w-[7px] rounded-full bg-[#BAD8F8]"></span>Sin OC</dt>
                            <dd class="mt-1.5 text-[12px] text-[#5C5C5C]"><span class="font-semibold text-[#0C0C0C]">{{ $oc['sin'] }}</span> ({{ $pct($oc['sin']) }}%)</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </section>

        {{-- Nuevos prospectos por vendedor: barras verticales --}}
        <section class="{{ $tarjeta }} min-h-[208px] p-2">
            <h2 class="pt-px text-[12px] leading-[12px] font-medium text-[#0C0C0C]">Nuevos prospectos x vendedor</h2>

            @if (array_sum(array_column($prospectosPorVendedor, 'cantidad')) === 0)
                <p class="flex h-[160px] items-center justify-center text-center text-xs text-slate-400">No se cargaron prospectos en el período.</p>
            @else
                @php
                    [$paso, $tope] = $escala(max(array_column($prospectosPorVendedor, 'cantidad')), 4);
                    $alto = fn (int $valor) => round($valor / $tope * 100, 2);
                @endphp

                {{-- El pt deja lugar a la marca de arriba: el scroll horizontal recorta lo que sobresale. --}}
                <div class="mt-[27px] flex gap-[19px] overflow-x-auto scroll-sutil pt-[6px]">
                    {{-- Eje Y: cada marca a la altura de su valor --}}
                    <div class="relative h-[120px] w-[14px] shrink-0">
                        @foreach (range(0, $tope, $paso) as $marca)
                            <span class="absolute right-0 translate-y-1/2 text-[10px] leading-none text-[#0C0C0C]" style="bottom: {{ $alto($marca) }}%">{{ $marca }}</span>
                        @endforeach
                    </div>

                    <div class="flex flex-1 justify-around gap-2">
                        @foreach ($prospectosPorVendedor as $fila)
                            <div class="flex min-w-[44px] flex-col items-center">
                                <div class="relative h-[120px] w-5">
                                    <div class="absolute inset-x-0 bottom-0 bg-[#22577C]" style="height: {{ $alto($fila['cantidad']) }}%"></div>
                                    <span class="absolute left-1/2 mb-1 -translate-x-1/2 text-[10px] leading-none text-[#0C0C0C]" style="bottom: {{ $alto($fila['cantidad']) }}%">{{ $fila['cantidad'] }}</span>
                                </div>
                                <span class="mt-[7px] text-center text-[10px] leading-none whitespace-nowrap text-[#0C0C0C]">{{ $fila['nombre'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Cotizaciones por vendedor: barras horizontales --}}
        <section class="{{ $tarjeta }} min-h-[208px] p-2 md:col-span-2 xl:col-span-1">
            <h2 class="pt-px text-[12px] leading-[12px] font-medium text-[#0C0C0C]">Cotizaciones x vendedor</h2>

            @if (array_sum(array_column($cotizacionesPorVendedor, 'cantidad')) === 0)
                <p class="flex h-[160px] items-center justify-center text-center text-xs text-slate-400">No hay cotizaciones en el período.</p>
            @else
                @php
                    [$paso, $tope] = $escala(max(array_column($cotizacionesPorVendedor, 'cantidad')), 7);
                    $marcas = range(0, $tope, $paso);
                @endphp

                <div class="mt-[30px] pr-8">
                    <div class="flex flex-col gap-[15px]">
                        @foreach ($cotizacionesPorVendedor as $fila)
                            <div class="flex items-center gap-2">
                                <span class="w-[58px] shrink-0 text-right text-[10px] leading-[7px] whitespace-nowrap text-[#0C0C0C]">{{ $fila['nombre'] }}</span>
                                <div class="relative h-[7px] flex-1">
                                    @if ($fila['cantidad'] > 0)
                                        <div class="h-full bg-[#22577C]" style="width: {{ round($fila['cantidad'] / $tope * 100, 2) }}%"></div>
                                    @endif
                                    <span class="absolute top-1/2 -translate-y-1/2 pl-1 text-[10px] leading-[7px] text-[#0C0C0C]" style="left: {{ round($fila['cantidad'] / $tope * 100, 2) }}%">{{ $fila['cantidad'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Eje X --}}
                    <div class="mt-[14px] flex gap-2">
                        <span class="w-[58px] shrink-0"></span>
                        <div class="relative h-3 flex-1">
                            @foreach ($marcas as $marca)
                                <span class="absolute -translate-x-1/2 text-[10px] leading-[10px] text-[#0C0C0C]" style="left: {{ round($marca / $tope * 100, 2) }}%">{{ $marca }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>

    {{-- Detalle por prospecto --}}
    <h2 class="mt-[24px] mb-[19px] px-2 text-[20px] leading-[24px] font-medium text-[#0C0C0C]">Detalle por prospecto</h2>

    <div class="overflow-x-auto scroll-sutil bg-white">
        <table class="w-full min-w-[720px] text-left">
            <thead>
                <tr class="h-12 border-b border-[#E5E7EB] bg-[#F9FAFC] text-[12px] tracking-[0.08em] text-[#6A7282] uppercase">
                    <th class="w-[33.6%] pl-[62px] font-medium">Razón social</th>
                    <th class="w-[25.3%] font-medium">Vendedor</th>
                    <th class="w-[26%] font-medium">Creado</th>
                    <th class="font-medium">Último contacto</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($detalle as $fila)
                    <tr wire:key="prospecto-{{ $fila['id'] }}" class="h-12 border-b border-[#E5E7EB] text-[14px] leading-5 text-[#6A7282] {{ $fila['sin_seguimiento'] ? 'bg-[#FEF2F2]' : 'bg-white' }}">
                        <td class="pl-[62px]">
                            <a href="{{ route('prospectos.edit', $fila['id']) }}" wire:navigate class="hover:text-[#22577C] hover:underline">{{ $fila['razon_social'] }}</a>
                        </td>
                        <td>{{ $fila['vendedor'] }}</td>
                        <td>{{ $fila['creado'] }}</td>
                        <td>{{ $fila['ultimo_contacto'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-10 text-center text-sm text-slate-400">No se cargaron prospectos en el período.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (collect($detalle)->contains('sin_seguimiento', true))
        <p class="mt-2 flex items-center gap-2 px-2 text-xs text-[#6A7282]">
            <span class="h-3 w-3 rounded-sm border border-[#F5D0D0] bg-[#FEF2F2]"></span>
            Sin seguimiento: no tuvieron ningún contacto después del día en que se cargaron.
        </p>
    @endif

    {{-- Comision por vendedor --}}
    <h2 class="mt-[24px] mb-[19px] px-2 text-[20px] leading-[24px] font-medium text-[#0C0C0C]">Comisión Proyectada</h2>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($comisiones as $comision)
            <article wire:key="comision-{{ $loop->index }}" class="{{ $tarjeta }} min-h-[111px] px-2 pt-2 pb-[13px]">
                <div class="flex items-start justify-between gap-3 pt-[3px]">
                    <span class="text-[12px] leading-[12px] font-medium text-[#0C0C0C]">Vendedor</span>
                    <span class="truncate text-[18px] leading-[18px] font-medium text-[#0C0C0C]">{{ $comision['vendedor'] }}</span>
                </div>
                <dl class="mt-[19px] flex flex-col gap-2.5 text-[16px] leading-5 text-[#0C0C0C]">
                    <div class="flex justify-between gap-3"><dt>Comisión real</dt><dd>{{ Numero::usd($comision['real'], prefijo: 'U$S ') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Comisión proyectada</dt><dd>{{ Numero::usd($comision['proyectada'], prefijo: 'U$S ') }}</dd></div>
                </dl>
            </article>
        @empty
            <p class="col-span-full rounded-[4px] border border-dashed border-[#D9D9D9] px-4 py-8 text-center text-sm text-slate-400">
                No hay cotizaciones aprobadas con vendedor en el período.
            </p>
        @endforelse
    </div>
</div>
