@php
    $campo = 'h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none';
    $soyYo = $usuario?->is(auth()->user());
@endphp

<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('usuarios.index') }}" wire:navigate aria-label="Volver a Gestión de Usuarios" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $usuario ? $usuario->name : 'Nuevo Usuario' }}</h1>
    </div>

    <form wire:submit="guardar">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-6 text-[16px] leading-[normal] font-medium text-black">Datos del usuario</h2>

            <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="flex flex-col gap-1.5">
                    <label for="name" class="text-sm text-slate-600">Nombre</label>
                    <input id="name" autofocus wire:model="name" placeholder="Nombre y apellido" class="{{ $campo }}" />
                    @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="username" class="text-sm text-slate-600">Usuario</label>
                    <input id="username" wire:model="username" autocomplete="off" placeholder="Con el que inicia sesión" class="{{ $campo }}" />
                    @error('username') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-sm text-slate-600">Email</label>
                    <input id="email" type="email" wire:model="email" placeholder="nombre@4pack.com" class="{{ $campo }}" />
                    @error('email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="rol_id" class="text-sm text-slate-600">Rol</label>
                    <select id="rol_id" wire:model="rol_id" class="{{ $campo }}">
                        <option value="">Elegir rol...</option>
                        @foreach ($roles as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('rol_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="activo" class="text-sm text-slate-600">Estado</label>
                    <select id="activo" wire:model="activo" @disabled($soyYo) class="{{ $campo }} disabled:bg-slate-50 disabled:text-slate-400">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                    @if ($soyYo)
                        <p class="text-xs text-slate-400">No podés desactivar tu propio usuario.</p>
                    @else
                        <p class="text-xs text-slate-400">Un usuario inactivo no puede iniciar sesión.</p>
                    @endif
                    @error('activo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="mt-4 rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-[16px] leading-[normal] font-medium text-black">Contraseña</h2>
            <p class="mb-6 text-sm text-slate-500">
                {{ $usuario ? 'Dejala vacía para no cambiarla.' : 'Mínimo 8 caracteres.' }}
            </p>

            <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-sm text-slate-600">{{ $usuario ? 'Nueva contraseña' : 'Contraseña' }}</label>
                    <input id="password" type="password" wire:model="password" autocomplete="new-password" class="{{ $campo }}" />
                    @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password_confirmation" class="text-sm text-slate-600">Repetir contraseña</label>
                    <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" class="{{ $campo }}" />
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
                {{ $usuario ? 'Guardar cambios' : 'Crear usuario' }}
            </button>
            <a
                href="{{ route('usuarios.index') }}"
                wire:navigate
                class="flex h-10 items-center rounded-md border border-slate-200 bg-white px-5 text-sm text-slate-600 hover:bg-slate-50"
            >
                Cancelar
            </a>
        </div>
    </form>
</div>
