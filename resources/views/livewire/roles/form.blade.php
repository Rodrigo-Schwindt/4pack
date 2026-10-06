@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('roles.index') }}" wire:navigate aria-label="Volver a Gestión de roles" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $rol ? $rol->nombre : 'Nuevo Rol' }}</h1>
    </div>

    <form wire:submit="guardar">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-6 text-[16px] leading-[normal] font-medium text-black">Datos del rol</h2>

            <div class="grid gap-x-6 gap-y-5 md:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="nombre" class="text-sm text-slate-600">Nombre</label>
                    <input id="nombre" autofocus wire:model="nombre" placeholder="Ej.: Vendedor" class="{{ $campo }}" />
                    @error('nombre') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="descripcion" class="text-sm text-slate-600">Descripción</label>
                    <input id="descripcion" wire:model="descripcion" placeholder="Para qué sirve el rol" class="{{ $campo }}" />
                    @error('descripcion') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            @if ($rol)
                <p class="mt-4 text-xs text-slate-400">
                    Lo {{ $usuarios === 1 ? 'tiene 1 usuario' : 'tienen '.$usuarios.' usuarios' }}: los cambios de permisos les llegan en el próximo clic.
                </p>
            @endif
        </section>

        <section class="mt-4 rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-[16px] leading-[normal] font-medium text-black">Permisos</h2>
                    <p class="mt-1 text-sm text-slate-500">Qué pantallas ve y qué puede hacer quien tenga este rol.</p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <button type="button" wire:click="todos" class="text-[#1c5480] hover:underline">Marcar todos</button>
                    <span class="text-slate-300">|</span>
                    <button type="button" wire:click="ninguno" class="text-[#1c5480] hover:underline">Ninguno</button>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($catalogo as $clave => [$nombre, $detalle])
                    <label
                        wire:key="permiso-{{ $clave }}"
                        class="flex cursor-pointer items-start gap-3 rounded-[10px] border p-4 transition-colors {{ in_array($clave, $permisos, true) ? 'border-[#016630]/30 bg-[#F0FDF4]' : 'border-[#E5E7EB] hover:bg-slate-50' }}"
                    >
                        <input type="checkbox" wire:model.live="permisos" value="{{ $clave }}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#22577C] focus:ring-[#22577C]" />
                        <span>
                            <span class="block text-sm font-medium text-[#101828]">{{ $nombre }}</span>
                            <span class="mt-0.5 block text-xs text-[#6A7282]">{{ $detalle }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('permisos') <p class="mt-4 text-sm text-red-600">{{ $message }}</p> @enderror
        </section>

        <div class="mt-4 flex items-center gap-3">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white hover:bg-[#174567] disabled:opacity-70"
            >
                <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="guardar" />
                {{ $rol ? 'Guardar cambios' : 'Crear rol' }}
            </button>
            <a
                href="{{ route('roles.index') }}"
                wire:navigate
                class="flex h-10 items-center rounded-md border border-slate-200 bg-white px-5 text-sm text-slate-600 hover:bg-slate-50"
            >
                Cancelar
            </a>
        </div>
    </form>
</div>
