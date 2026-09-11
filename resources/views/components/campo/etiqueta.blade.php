@props(['para' => null, 'requerido' => false])

{{-- Los campos que habilitan a otros van resaltados con asterisco. --}}
<label
    @if ($para) for="{{ $para }}" @endif
    class="text-sm {{ $requerido ? 'font-semibold text-[#22577C]' : 'text-slate-600' }}"
>
    {{ $slot }}{{ $requerido ? ' *' : '' }}
</label>
