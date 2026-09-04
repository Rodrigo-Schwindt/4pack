@props(['label' => null, 'modelo', 'calculado' => false, 'placeholder' => null, 'derecha' => false, 'tipo' => 'text', 'paso' => null, 'modificador' => null])

{{-- Los campos que la maqueta muestra en gris son calculados: van de solo lectura. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($label !== null)
        <label for="{{ $modelo }}" class="text-sm text-slate-600">{{ $label }}</label>
    @endif

    <input
        id="{{ $modelo }}"
        type="{{ $tipo }}"
        wire:model{{ $modificador ? '.'.$modificador : '' }}="{{ $modelo }}"
        @if ($paso) step="{{ $paso }}" @endif
        @readonly($calculado)
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm {{ $derecha ? 'text-right' : '' }} {{ $calculado
            ? 'cursor-default bg-slate-50 text-slate-400'
            : 'bg-white text-slate-800 placeholder:text-slate-400 focus:border-[#1c5480] focus:ring-1 focus:ring-[#1c5480] focus:outline-none' }}"
    />

    @error($modelo) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>
