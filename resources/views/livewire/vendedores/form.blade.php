@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('vendedores.index') }}" wire:navigate aria-label="Volver a Gestión de Vendedores" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $vendedor ? $vendedor->nombre : 'Nuevo Vendedor' }}</h1>
    </div>

    <form wire:submit="guardar">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-6 font-[Inter,_sans-serif] text-[16px] leading-[normal] font-medium text-black">Datos del vendedor</h2>

            <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="flex flex-col gap-1.5">
                    <label for="nombre" class="text-sm text-slate-600">Nombre</label>
                    <input id="nombre" autofocus wire:model="nombre" placeholder="Nombre del vendedor" class="{{ $campo }}" />
                    @error('nombre') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="comision" class="text-sm text-slate-600">Comisión (%)</label>
                    <input id="comision" type="number" step="0.01" min="0" max="100" wire:model="comision" placeholder="0" class="{{ $campo }}" />
                    @error('comision') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="activo" class="text-sm text-slate-600">Estado</label>
                    <select id="activo" wire:model="activo" class="{{ $campo }}">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                    @error('activo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="mt-4 flex items-center gap-3">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white hover:bg-[#174567] disabled:opacity-70"
            >
                <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="guardar" />
                {{ $vendedor ? 'Guardar cambios' : 'Crear vendedor' }}
            </button>
            <a
                href="{{ route('vendedores.index') }}"
                wire:navigate
                class="flex h-10 items-center rounded-md border border-slate-200 bg-white px-5 text-sm text-slate-600 hover:bg-slate-50"
            >
                Cancelar
            </a>
        </div>
    </form>
</div>
