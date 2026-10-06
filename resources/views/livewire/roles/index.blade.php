@php
    // Como en el diseño: tres permisos a la vista y el resto en "+N más".
    $visibles = 3;
    $chip = 'inline-flex h-[23px] max-w-full items-center truncate rounded-[4px] px-[5px] text-[12px] leading-4';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-[45px] flex items-center gap-[23px]">
        <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-[#364153] hover:text-[#101828]">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-[24px] leading-8 font-bold text-[#101828]">Gestión de roles</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <section class="min-h-[354px] rounded-[10px] border border-[#E5E7EB] bg-white pt-[15px] pr-[18px] pb-6 pl-[23px]">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-[18px] leading-7 font-semibold text-[#101828]">Roles del Sistema</h2>
            <a
                href="{{ route('roles.create') }}"
                wire:navigate
                class="flex h-10 w-[139px] items-center justify-center gap-2 rounded-[10px] bg-[#22577C] text-[16px] leading-6 font-medium text-white hover:bg-[#1c4868]"
            >
                <x-icon name="plus" class="h-[18px] w-[18px]" />
                Nuevo Rol
            </a>
        </div>

        @error('lista') <p class="mt-4 text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="mt-4 grid gap-[25px] md:grid-cols-2 xl:grid-cols-3">
            @foreach ($roles as $rol)
                @php
                    $permisos = $rol->nombresDePermisos();
                    $enUso = $rol->usuarios_count > 0;
                @endphp

                <article wire:key="rol-{{ $rol->id }}" class="flex min-h-[185px] flex-col rounded-[10px] border border-[#E5E7EB] bg-white px-4 pt-4 pb-[15px]">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-[18px] leading-7 font-semibold text-[#101828]">{{ $rol->nombre }}</h3>
                        <div class="flex shrink-0 items-center gap-5 pt-1.5 pr-0.5">
                            <a href="{{ route('roles.edit', $rol) }}" wire:navigate aria-label="Editar {{ $rol->nombre }}" class="text-[#707070] hover:text-[#22577C]">
                                <x-icon name="square-pen" class="h-4 w-4" />
                            </a>
                            <button
                                type="button"
                                wire:click="eliminar({{ $rol->id }})"
                                wire:confirm="¿Eliminar el rol {{ $rol->nombre }}?"
                                @disabled($enUso)
                                title="{{ $enUso ? 'Lo tienen '.$rol->usuarios_count.' '.($rol->usuarios_count === 1 ? 'usuario' : 'usuarios').': no se puede eliminar' : 'Eliminar' }}"
                                aria-label="Eliminar {{ $rol->nombre }}"
                                class="text-[#B22B3E] hover:text-red-700 disabled:cursor-default disabled:opacity-30"
                            >
                                <x-icon name="trash-2" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <p class="mt-[3px] truncate text-[14px] leading-5 text-[#6A7282]" title="{{ $rol->descripcion }}">{{ $rol->descripcion ?: 'Sin descripción' }}</p>

                    <hr class="mt-[13px] border-[#E5E7EB]" />

                    <p class="mt-[11px] text-[12px] leading-4 font-medium text-[#101828]">
                        Permisos ({{ count($permisos) }})
                        <span class="font-normal text-[#6A7282]">· {{ $rol->usuarios_count }} {{ $rol->usuarios_count === 1 ? 'usuario' : 'usuarios' }}</span>
                    </p>

                    <ul class="mt-[9px] flex flex-wrap gap-x-2 gap-y-[5px]">
                        @foreach (array_slice($permisos, 0, $visibles) as $permiso)
                            <li class="{{ $chip }} bg-[#DCFCE7] text-[#016630]">{{ $permiso }}</li>
                        @endforeach

                        @if (count($permisos) > $visibles)
                            <li class="{{ $chip }} bg-[#F3F4F6] text-[#364153]" title="{{ implode(', ', array_slice($permisos, $visibles)) }}">+{{ count($permisos) - $visibles }} más</li>
                        @endif
                    </ul>
                </article>
            @endforeach
        </div>
    </section>
</div>
