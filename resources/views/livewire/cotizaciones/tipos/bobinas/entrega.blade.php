@php
    // Las entregas de lo comprado: lo pactado viene de Datos y la OC; lo entregado se carga aca.
    $entregasPedido = $this->entregasDelPedido;
    $numero = fn (?float $valor) => $valor === null ? '' : \App\Support\Numero::corto($valor);
    $usd = fn (?float $valor) => \App\Support\Numero::usd($valor, prefijo: 'U$S ');
    $pct = fn (?float $valor) => $valor === null ? '-' : \App\Support\Numero::corto($valor, 1).'%';

    $gris = 'h-10 w-full cursor-default rounded-md border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800';
    $linea = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';

    // Total: el cumplimiento se mide sobre las entregas que ya tienen algo cargado.
    $conDato = collect($entregasPedido)->whereNotNull('entregada');
    $aEntregarConDato = $conDato->sum('a_entregar');
    $totalCumplimiento = $aEntregarConDato > 0 ? $conDato->sum('entregada') / $aEntregarConDato * 100 : null;
    $totalProyectada = collect($entregasPedido)->sum(fn ($e) => $e['proyectada'] ?? 0);
    $totalReal = $conDato->sum(fn ($e) => $e['real'] ?? 0);
@endphp

<section class="rounded-lg bg-white shadow-sm">
    <h2 class="px-6 py-5 text-[16px] leading-[normal] font-medium text-black">Entrega</h2>
    <hr class="border-slate-100" />

    <div class="flex flex-col gap-6 px-6 py-6">
        @forelse ($entregasPedido as $indice => $entrega)
            @php $m = $entrega['modelo']; @endphp

            <div wire:key="entregada-{{ $m }}" class="flex flex-col gap-4">
                <p class="text-sm font-medium text-slate-800">{{ $entrega['titulo'] }}</p>

                <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-sm text-slate-600">Producto</span>
                        <input readonly value="{{ $entrega['producto'] }}" placeholder="-" aria-label="Producto de la {{ $entrega['titulo'] }}" class="{{ $gris }}" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <span class="text-sm text-slate-600">Cantidad a entregar</span>
                        <input readonly value="{{ $entrega['a_entregar'] > 0 ? $numero($entrega['a_entregar']).' '.$entrega['unidad'] : '' }}" placeholder="-" aria-label="Cantidad a entregar de la {{ $entrega['titulo'] }}" class="{{ $gris }} text-right" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="entregada-{{ $indice }}" class="text-sm text-slate-600">Cantidad entregada ({{ $entrega['unidad'] }})</label>
                        <input id="entregada-{{ $indice }}" type="number" step="0.01" min="0" wire:model.live.blur="{{ $m }}.cantidad_entregada" class="{{ $linea }} text-right" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="entrega-real-{{ $indice }}" class="text-sm text-slate-600">Fecha entrega</label>
                        <div class="relative">
                            <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input id="entrega-real-{{ $indice }}" type="date" wire:model="{{ $m }}.fecha_real" class="{{ $linea }} campo-fecha pl-9" />
                        </div>
                    </div>

                    {{-- Arrancan con lo pactado en Datos; se cambian si se entrego en otro lado. --}}
                    <div class="flex flex-col gap-1.5">
                        <label for="entrega-zona-{{ $indice }}" class="text-sm text-slate-600">Zona</label>
                        <input id="entrega-zona-{{ $indice }}" wire:model="{{ $m }}.zona" class="{{ $linea }}" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="entrega-direccion-{{ $indice }}" class="text-sm text-slate-600">Dirección de entrega</label>
                        <input id="entrega-direccion-{{ $indice }}" wire:model="{{ $m }}.direccion_entrega" class="{{ $linea }}" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="entrega-cp-{{ $indice }}" class="text-sm text-slate-600">Código postal</label>
                        <input id="entrega-cp-{{ $indice }}" wire:model="{{ $m }}.codigo_postal" class="{{ $linea }}" />
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-400">Todavía no hay entregas: se cargan en Forma de entrega, en Datos a completar.</p>
        @endforelse
    </div>
</section>

<h2 class="mt-8 mb-4 text-[16px] leading-[normal] font-bold text-black">Conclusiones</h2>

<section class="rounded-lg bg-white shadow-sm">
    <div class="overflow-x-auto scroll-sutil">
        <table class="w-full min-w-[640px]">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                    <th class="px-8 py-3 text-left font-medium">N° entrega</th>
                    <th class="px-8 py-3 text-right font-medium">Cumplimiento de la entrega</th>
                    <th class="px-8 py-3 text-right font-medium">Comisión proyectada</th>
                    <th class="px-8 py-3 text-right font-medium">Comisión real</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entregasPedido as $entrega)
                    <tr wire:key="conclusion-{{ $entrega['modelo'] }}" class="border-b border-slate-100">
                        <td class="px-8 py-5 text-sm text-slate-600">{{ $entrega['nombre'] }}</td>
                        <td class="px-8 py-5 text-right text-sm text-slate-600">{{ $pct($entrega['cumplimiento']) }}</td>
                        <td class="px-8 py-5 text-right text-sm text-slate-600">{{ $usd($entrega['proyectada']) }}</td>
                        <td class="px-8 py-5 text-right text-sm text-slate-600">{{ $entrega['entregada'] === null ? '-' : $usd($entrega['real']) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="px-8 py-5 text-sm text-slate-600">Total</td>
                    <td class="px-8 py-5 text-right text-sm text-slate-600">{{ $pct($totalCumplimiento) }}</td>
                    <td class="px-8 py-5 text-right text-sm text-slate-600">{{ $usd($totalProyectada) }}</td>
                    <td class="px-8 py-5 text-right text-sm text-slate-600">{{ $conDato->isEmpty() ? '-' : $usd($totalReal) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
