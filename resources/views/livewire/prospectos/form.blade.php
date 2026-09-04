<div class="mx-auto w-full max-w-[1224px]">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('prospectos.index') }}" wire:navigate aria-label="Volver al listado" class="text-slate-700 hover:text-slate-900">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $contacto ? $codigo : 'Nuevo Prospecto' }}</h1>
    </div>

    @if (session('status'))
        <p class="mb-4 text-sm font-medium text-green-600">{{ session('status') }}</p>
    @endif

    <form wire:submit="guardar">
        <x-datos-generales
            :provincias="$provincias"
            :rubros="$rubros"
            :tipos="$tipos"
            :vendedores="$vendedores"
            :creando="$creando"
        />

        <div class="mt-4 flex items-center gap-3">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="flex h-10 items-center gap-2 rounded-md bg-[#1c5480] px-5 text-sm font-medium text-white hover:bg-[#174567] disabled:opacity-70"
            >
                <x-icon name="loader-circle" class="h-4 w-4 animate-spin" wire:loading wire:target="guardar" />
                {{ $contacto ? 'Guardar cambios' : 'Crear prospecto' }}
            </button>
            <a
                href="{{ route('prospectos.index') }}"
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
</div>
