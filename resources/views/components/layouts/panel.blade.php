@php
    // Las vistas todavia no desarrolladas no navegan a ningun lado.
    $items = [
        ['titulo' => 'Dashboard', 'icono' => 'dashboard', 'href' => '/dashboard'],
        ['titulo' => 'Prospectos', 'icono' => 'contacto', 'href' => '/prospectos'],
        ['titulo' => 'Clientes', 'icono' => 'contacto', 'href' => '/clientes'],
        ['titulo' => 'Cotizaciones', 'icono' => 'cotizaciones', 'href' => '/cotizaciones'],
        ['titulo' => 'Estadísticas', 'icono' => 'estadisticas', 'href' => null],
        ['titulo' => 'Configuración', 'icono' => 'configuracion', 'href' => '/configuracion'],
    ];

    // Los iconos del diseño miden distinto: el ancho fijo mantiene los titulos alineados.
    $cajaIcono = 'flex w-[17px] shrink-0 items-center justify-center';
    $tituloItem = 'text-[#364153] text-[16px] leading-[24px] font-medium';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F3F4F6] antialiased">
        <div x-data="{ aside: false }" @keydown.escape.window="aside = false">
            {{-- Aside deslizante --}}
            <div
                @click="aside = false"
                class="fixed inset-0 z-40 bg-slate-900/30 transition-opacity duration-200"
                :class="aside ? 'opacity-100' : 'pointer-events-none opacity-0'"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-[260px] flex-col bg-white shadow-xl transition-transform duration-200"
                :class="aside ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="flex items-start justify-between border-b border-[#E5E7EB] px-6 py-4">
                    <div>
                        <p class="text-[#101828] text-[20px] font-bold leading-[28px]">4 Pack</p>
                        <p class="text-[#6A7282] text-[12px] font-normal leading-[16px]">Sistema de Cotizaciones</p>
                    </div>
                    <button type="button" @click="aside = false" aria-label="Cerrar menú" class="pt-4 text-slate-500 hover:text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
  <path d="M10.8333 0.833313L0.833313 10.8333" stroke="#4A5565" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M0.833313 0.833313L10.8333 10.8333" stroke="#4A5565" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
                    </button>
                </div>

                <nav class="flex-1 px-4 py-6">
                    <ul class="flex flex-col gap-1">
                        @foreach ($items as $item)
                            @php
                                // /prospectos/3/edit tambien marca Prospectos como activo
                                $activo = $item['href'] && request()->is(ltrim($item['href'], '/').'*');
                                $clases = 'flex w-full items-center gap-3 rounded-md px-3 py-2.5 '
                                    .$tituloItem.($activo ? ' bg-slate-100' : ' hover:bg-slate-50');
                            @endphp
                            <li>
                                @if ($item['href'])
                                    <a href="{{ $item['href'] }}" wire:navigate class="{{ $clases }}">
                                        <span class="{{ $cajaIcono }}"><x-icon-aside :name="$item['icono']" /></span>
                                        {{ $item['titulo'] }}
                                    </a>
                                @else
                                    <button type="button" disabled title="Próximamente" class="{{ $clases }} cursor-default">
                                        <span class="{{ $cajaIcono }}"><x-icon-aside :name="$item['icono']" /></span>
                                        {{ $item['titulo'] }}
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="border-t border-[#E5E7EB] px-4 py-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-center font-[Inter,_sans-serif] {{ $tituloItem }} hover:bg-slate-50">
                            <span class="{{ $cajaIcono }}"><x-icon-aside name="logout" /></span>
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </aside>

            <header class="border-b border-[#E5E7EB] bg-white px-6 py-3">
                <div class="mx-auto flex w-full max-w-[1224px] items-center gap-4">
                    <button type="button" @click="aside = true" aria-label="Abrir menú" class="p-1 text-slate-700 hover:text-slate-900">
                        <x-icon name="menu" class="h-6 w-6" />
                    </button>
                    <div>
                       <p class="text-[#101828] text-[24px] font-bold leading-[28px]">4 Pack</p>
                        <p class="text-[#6A7282] text-[12px] font-normal leading-[16px]">Sistema de Cotizaciones</p>
                    </div>
                </div>
            </header>

            <main class="p-6">{{ $slot }}</main>
        </div>

        @fluxScripts
    </body>
</html>
