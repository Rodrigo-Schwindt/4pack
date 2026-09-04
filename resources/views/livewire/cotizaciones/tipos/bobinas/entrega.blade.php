@php
    use App\Livewire\Cotizaciones\MaquetaBobinas;

    $conclusiones = MaquetaBobinas::conclusiones();

    $linea = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
@endphp

<section class="rounded-lg bg-white shadow-sm">
    <h2 class="px-6 py-5 text-[16px] leading-[normal] font-medium text-black">Entrega</h2>
    <hr class="border-slate-100" />

    <div class="flex flex-col gap-5 px-6 py-6">
        @foreach ($entregas as $indice => $entrega)
            <div wire:key="entregada-{{ $indice }}" class="flex flex-col gap-5">
                <p class="text-xs text-slate-500">{{ $indice + 1 }}° entrega</p>

                {{-- Lo pactado llega calculado; lo entregado lo carga el usuario. --}}
                <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
                    <x-campo.texto label="Producto" :modelo="'entregas.'.$indice.'.producto'" calculado />
                    <x-campo.texto label="Cantidad a entregar" :modelo="'entregas.'.$indice.'.cantidad_a_entregar'" calculado derecha />
                    <x-campo.texto label="Cantidad entregada" :modelo="'entregas.'.$indice.'.cantidad_entregada'" derecha />

                    <div class="flex flex-col gap-1.5">
                        <label for="entrega-real-{{ $indice }}" class="text-sm text-slate-600">Fecha entrega</label>
                        <div class="relative">
                            <x-icon name="calendar" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                id="entrega-real-{{ $indice }}"
                                type="date"
                                wire:model="entregas.{{ $indice }}.fecha_real"
                                class="{{ $linea }} campo-fecha pl-9"
                            />
                        </div>
                    </div>

                    <x-campo.texto label="Zona" :modelo="'entregas.'.$indice.'.zona'" />
                    <x-campo.texto label="Dirección de entrega" :modelo="'entregas.'.$indice.'.direccion_entrega'" />
                    <x-campo.texto label="Código postal" :modelo="'entregas.'.$indice.'.codigo_postal'" />
                </div>
            </div>
        @endforeach
    </div>
</section>

<h2 class="mt-8 mb-4 text-[16px] leading-[normal] font-bold text-black">Conclusiones</h2>

<section class="rounded-lg bg-white shadow-sm">
    <x-tabla-costos :columnas="$conclusiones['columnas']" :filas="$conclusiones['filas']" :detalle="0" />
</section>
