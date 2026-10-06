{{--
    Paginado de todo el sistema. Pisa la vista de Livewire (livewire::tailwind),
    asi cualquier listado que use WithPagination y ->links() toma este estilo.
    Usa las mismas acciones de Livewire: previousPage, nextPage y gotoPage.
--}}
@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $subir = $scrollTo !== false
        ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()"
        : '';

    $pagina = $paginator->getPageName();
    $boton = 'flex h-9 min-w-9 items-center justify-center rounded-md px-2 text-sm';
    $activo = $boton.' bg-[#1c5480] font-medium text-white';
    $normal = $boton.' text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    $flecha = 'flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-[#1c5480] disabled:cursor-default disabled:text-slate-300 disabled:hover:bg-white';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Paginado" class="flex flex-wrap items-center justify-between gap-3 py-1">
            <p class="text-sm text-slate-500">
                Mostrando <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>
                a <span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span>
                de <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
            </p>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    wire:click="previousPage('{{ $pagina }}')"
                    x-on:click="{{ $subir }}"
                    wire:loading.attr="disabled"
                    @disabled($paginator->onFirstPage())
                    aria-label="Página anterior"
                    class="{{ $flecha }}"
                >
                    <x-icon name="chevron-right" class="h-4 w-4 rotate-180" />
                </button>

                {{-- En pantallas chicas alcanza con "pagina X de Y". --}}
                <span class="px-2 text-sm text-slate-500 sm:hidden">
                    {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
                </span>

                <div class="hidden items-center gap-1 sm:flex">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="{{ $boton }} cursor-default text-slate-400" aria-hidden="true">…</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $numero => $url)
                                <span wire:key="paginado-{{ $pagina }}-{{ $numero }}">
                                    @if ($numero == $paginator->currentPage())
                                        <span aria-current="page" class="{{ $activo }}">{{ $numero }}</span>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="gotoPage({{ $numero }}, '{{ $pagina }}')"
                                            x-on:click="{{ $subir }}"
                                            aria-label="Ir a la página {{ $numero }}"
                                            class="{{ $normal }}"
                                        >
                                            {{ $numero }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach
                </div>

                <button
                    type="button"
                    wire:click="nextPage('{{ $pagina }}')"
                    x-on:click="{{ $subir }}"
                    wire:loading.attr="disabled"
                    @disabled(! $paginator->hasMorePages())
                    aria-label="Página siguiente"
                    class="{{ $flecha }}"
                >
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </button>
            </div>
        </nav>
    @endif
</div>
