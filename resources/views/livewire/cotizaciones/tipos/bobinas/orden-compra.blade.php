@php
    use App\Livewire\Cotizaciones\Form;
    use App\Livewire\Cotizaciones\MaquetaBobinas;

    $orden = MaquetaBobinas::ordenCompra();

    $linea = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
@endphp

<section class="rounded-lg bg-white shadow-sm">
    <h2 class="px-6 py-5 text-[16px] leading-[normal] font-medium text-black">Orden de compra</h2>
    <hr class="border-slate-100" />

    <div class="grid gap-x-6 gap-y-5 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
        <x-campo.select label="Canal recibo de OC" modelo="oc.canal" :opciones="Form::OPCIONES['canales']" />

        <div class="flex flex-col gap-1.5">
            <label for="oc.fecha_recibo" class="text-sm text-slate-600">Fecha recibo OC</label>
            <div class="relative">
                <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input id="oc.fecha_recibo" type="date" wire:model="oc.fecha_recibo" class="{{ $linea }} campo-fecha pl-9" />
            </div>
        </div>

        <x-campo.texto label="Quien recibió OC" modelo="oc.quien" />

        <div class="flex flex-col gap-1.5">
            <span class="text-sm text-slate-600">Adjuntar OC</span>
            <label class="flex h-10 cursor-pointer items-center justify-between rounded-md border border-slate-200 bg-white px-3 text-sm">
                <span class="{{ $archivo_oc ? 'text-slate-800' : 'text-slate-400' }}">
                    {{ $archivo_oc?->getClientOriginalName() ?? 'Seleccionar archivo' }}
                </span>
                <x-icon name="download" class="h-4 w-4 shrink-0 text-slate-500" wire:loading.remove wire:target="archivo_oc" />
                <x-icon name="loader-circle" class="h-4 w-4 shrink-0 animate-spin text-slate-500" wire:loading wire:target="archivo_oc" />
                <input type="file" wire:model="archivo_oc" class="sr-only" />
            </label>
            @error('archivo_oc') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-campo.texto label="N° OC" modelo="oc.numero" />

        {{-- Una columna por entrega: el lugar es el mismo que muestra la cotizacion. --}}
        @foreach ($entregas as $indice => $entrega)
            <div class="flex items-end gap-4" wire:key="oc-entrega-{{ $indice }}">
                <x-campo.texto :label="'Entrega '.($indice + 1)" :modelo="'entregas.'.$indice.'.texto_lugar'" class="w-[100px] shrink-0" />

                <div class="flex flex-1 flex-col gap-1.5">
                    <label for="entrega-fecha-{{ $indice }}" class="text-sm text-slate-600">Fecha de entrega</label>
                    <input
                        id="entrega-fecha-{{ $indice }}"
                        type="date"
                        wire:model="entregas.{{ $indice }}.fecha_entrega"
                        class="{{ $linea }} campo-fecha"
                    />
                </div>
            </div>
        @endforeach
    </div>

    <div class="overflow-x-auto px-6 pb-6">
        <table class="w-full min-w-[860px]">
            <thead>
                <tr class="border-y border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                    @foreach ($orden['columnas'] as $indice => $columna)
                        <th class="px-3 py-3 font-medium {{ $indice > 2 ? 'text-right' : 'text-left' }}">{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($orden['filas'] as $indice => $fila)
                    <tr wire:key="oc-fila-{{ $indice }}" class="border-b border-slate-100 last:border-0 align-top">
                        <td class="px-3 py-4 text-sm text-slate-700">{{ $fila['tipo'] }}</td>
                        <td class="max-w-[220px] px-3 py-4 text-sm text-slate-700">{{ $fila['producto'] }}</td>
                        <td class="px-3 py-4 text-sm text-slate-700">
                            @foreach ($fila['caracteristicas'] as $caracteristica)
                                <p>{{ $caracteristica }}</p>
                            @endforeach
                        </td>
                        <td class="px-3 py-4 text-right text-sm text-slate-600">{{ $fila['cantidad'] }}</td>
                        <td class="px-3 py-4 text-right text-sm text-slate-600">{{ $fila['precio_unitario'] }}</td>
                        <td class="px-3 py-4 text-right text-sm text-slate-600">{{ $fila['importe_total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
