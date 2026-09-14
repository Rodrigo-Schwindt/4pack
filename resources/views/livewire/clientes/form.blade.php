@php
    $estiloSolapa = fn (bool $activa) => 'border-b-2 px-1 pb-2 text-sm '
        .($activa ? 'border-[#1c5480] text-slate-900' : 'border-slate-200 text-slate-400 hover:text-slate-600');
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('clientes.index') }}" wire:navigate aria-label="Volver al listado" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $contacto ? $codigo : 'Nuevo Cliente' }}</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <nav class="mb-6 flex items-center gap-8">
        <button type="button" wire:click="verSolapa('datos')" class="{{ $estiloSolapa($solapa === 'datos') }}">
            Datos generales
        </button>
        <button
            type="button"
            wire:click="verSolapa('productos')"
            @disabled(! $contacto)
            title="{{ $contacto ? '' : 'Disponible después de crear el cliente' }}"
            class="{{ $estiloSolapa($solapa === 'productos') }}{{ $contacto ? '' : ' cursor-default opacity-50' }}"
        >
            Productos
        </button>
    </nav>

    @if ($solapa === 'datos')
        <form wire:submit="guardar">
            <x-datos-generales
                :provincias="$provincias"
                :rubros="$rubros"
                :tipos="$tipos"
                :vendedores="$vendedores"
                :creando="$creando"
            />

            <x-direcciones-entrega :zonas="$zonas" :direcciones="$direcciones" :editando="$editandoDireccion" :creando-zona="$creandoZonaEn" />

            <div class="mt-6 flex items-center gap-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white hover:bg-[#174567] disabled:opacity-70"
                >
                    <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="guardar" />
                    {{ $contacto ? 'Guardar cambios' : 'Crear cliente' }}
                </button>
                <a
                    href="{{ route('clientes.index') }}"
                    wire:navigate
                    class="flex h-10 items-center rounded-md border border-slate-200 bg-white px-5 text-sm text-slate-600 hover:bg-slate-50"
                >
                    Cancelar
                </a>
            </div>
        </form>

        @if ($contacto)
            <x-actividad-contacto :actividades="$actividades" />
        @endif
    @else
        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto scroll-sutil">
                <table class="w-full min-w-[720px] text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] tracking-wide text-slate-500 uppercase">
                            <th class="px-6 py-3 font-medium">Producto</th>
                            <th class="px-6 py-3 font-medium">Ficha</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productos as $producto)
                            <tr wire:key="producto-{{ $producto['id'] }}" class="border-b border-slate-100 last:border-0">
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $producto['nombre'] }}</td>
                                <td class="px-6 py-4">
                                    <button type="button" disabled title="Próximamente" aria-label="Ver ficha del producto" class="cursor-default text-[#1c5480]">
                                        <x-icon name="eye" class="h-5 w-5" />
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" disabled title="Próximamente" class="h-9 cursor-default rounded-md bg-[#1c5480] px-6 text-sm font-medium text-white opacity-90">
                                        Cotizar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
