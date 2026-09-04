<div class="max-h-[495px] w-full max-w-[448px] rounded-lg bg-white p-[32px] shadow-2xl">
    <div class="flex flex-col items-center pt-[36px]">
        <img src="/logo-4pack.png" alt="4 Pack Envases Flexibles" class="h-[65px] w-auto" />
        <h1 class="mt-[36px] text-center font-[Inter,_sans-serif] text-[24px] leading-[24px]  text-[#4A5565]">
            Sistema de Cotizaciones
        </h1>
    </div>

    @if (session('status'))
        <div class="mt-4 text-center text-sm font-medium text-green-600">{{ session('status') }}</div>
    @endif

    <form wire:submit="login" class="mt-6 flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
            <label for="username" class="text-[14px] font-medium text-[#364153]">Usuario</label>
            <div class="relative">
                <svg name="user" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" 
                 xmlns="http://www.w3.org/2000/svg" width="14" height="17" viewBox="0 0 14 17" fill="none">
  <path d="M12.5 15.8334V14.1667C12.5 13.2827 12.1489 12.4348 11.5237 11.8097C10.8986 11.1846 10.0508 10.8334 9.16671 10.8334H4.16671C3.28265 10.8334 2.43481 11.1846 1.80968 11.8097C1.18456 12.4348 0.833374 13.2827 0.833374 14.1667V15.8334" stroke="#99A1AF" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M6.66671 7.50004C8.50766 7.50004 10 6.00766 10 4.16671C10 2.32576 8.50766 0.833374 6.66671 0.833374C4.82576 0.833374 3.33337 2.32576 3.33337 4.16671C3.33337 6.00766 4.82576 7.50004 6.66671 7.50004Z" stroke="#99A1AF" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
                <input
                    id="username"
                    wire:model="username"
                    type="text"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Ingrese su usuario"
                    class="h-[49px] w-full rounded-md border border-slate-200 bg-white pr-3 pl-9 text-sm text-slate-800 placeholder:text-[16px] placeholder:leading-[normal] placeholder:font-normal placeholder:text-[rgba(10,10,10,0.50)] focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                />
            </div>
            @error('username') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium text-slate-700">Contraseña</label>
            <div class="relative">
                <x-icon name="lock" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[#99A1AF]" />
                <input
                    id="password"
                    wire:model="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingrese su contraseña"
                    class="h-[49px] w-full rounded-md border border-slate-200 bg-white pr-3 pl-9 text-sm text-slate-800 placeholder:text-[16px] placeholder:leading-[normal] placeholder:font-normal placeholder:text-[rgba(10,10,10,0.50)] focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none"
                />
            </div>
            @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="mt-2 flex h-[49px] w-full items-center justify-center gap-2 rounded-md bg-[#22577C] text-[16px] font-medium text-white transition-colors hover:bg-[#174567] disabled:opacity-70"
        >
            <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading />
            Iniciar Sesión
        </button>
    </form>
</div>
