@php
    $encabezado = 'h-[41px] text-left text-[12px] leading-4 font-medium tracking-[0.6px] text-[#6A7282] uppercase';
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-[45px] flex items-center gap-[23px]">
        <a href="{{ route('configuracion') }}" wire:navigate aria-label="Volver a Configuración" class="text-[#364153] hover:text-[#101828]">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-[24px] leading-8 font-bold text-[#101828]">Gestión de Usuarios</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <section class="min-h-[508px] rounded-t-[10px] border border-[#E5E7EB] bg-white pt-[15px] pr-[18px] pb-6 pl-[23px]">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-[18px] leading-7 font-semibold text-[#101828]">Usuarios del Sistema</h2>
            <a
                href="{{ route('usuarios.create') }}"
                wire:navigate
                class="flex h-10 w-[174px] items-center justify-center gap-2 rounded-[10px] bg-[#22577C] text-[16px] leading-6 font-medium text-white hover:bg-[#1c4868]"
            >
                <x-icon name="plus" class="h-[18px] w-[18px]" />
                Nuevo Usuario
            </a>
        </div>

        @error('lista') <p class="mt-4 text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="mt-4 overflow-x-auto scroll-sutil">
            <table class="w-full min-w-[960px]">
                <thead>
                    <tr class="border-y border-[#E5E7EB] bg-[#F9FAFB]">
                        <th class="{{ $encabezado }} w-[237px] pl-[17px]">Nombre</th>
                        <th class="{{ $encabezado }} w-[183px]">Usuario</th>
                        <th class="{{ $encabezado }} w-[263px]">Email</th>
                        <th class="{{ $encabezado }} w-[218px]">Rol</th>
                        <th class="{{ $encabezado }}">Estado</th>
                        <th class="{{ $encabezado }} w-[120px]"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        @php $soyYo = $usuario->is(auth()->user()); @endphp

                        <tr wire:key="usuario-{{ $usuario->id }}" class="h-16 border-b border-[#E5E7EB]">
                            <td class="pl-[17px] text-[14px] leading-5 font-medium text-[#101828]">
                                {{ $usuario->name }}
                                @if ($soyYo) <span class="ml-1 text-[12px] font-normal text-[#6A7282]">(vos)</span> @endif
                            </td>
                            <td class="text-[14px] leading-5 text-[#6A7282]">{{ $usuario->username }}</td>
                            <td class="text-[14px] leading-5 text-[#6A7282]">{{ $usuario->email }}</td>
                            <td class="text-[14px] leading-5 text-[#6A7282]">{{ $usuario->rol?->nombre ?? 'Sin rol' }}</td>
                            <td>
                                <span class="inline-flex h-7 items-center rounded-[14px] px-[7px] text-[12px] leading-5 font-semibold {{ $usuario->activo ? 'bg-[#DCFCE7] text-[#016630]' : 'bg-[#F3F4F6] text-[#6A7282]' }}">
                                    {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="pr-[13px]">
                                <div class="flex items-center justify-end gap-7">
                                    <a href="{{ route('usuarios.edit', $usuario) }}" wire:navigate aria-label="Editar {{ $usuario->name }}" class="text-[#707070] hover:text-[#22577C]">
                                        <x-icon name="square-pen" class="h-4 w-4" />
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="eliminar({{ $usuario->id }})"
                                        wire:confirm="¿Eliminar al usuario {{ $usuario->name }}?"
                                        @disabled($soyYo)
                                        title="{{ $soyYo ? 'No podés eliminar tu propio usuario' : 'Eliminar' }}"
                                        aria-label="Eliminar {{ $usuario->name }}"
                                        class="text-[#B22B3E] hover:text-red-700 disabled:cursor-default disabled:opacity-30"
                                    >
                                        <x-icon name="trash-2" class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($usuarios->hasPages())
        <div class="mt-4">{{ $usuarios->links() }}</div>
    @endif
</div>
