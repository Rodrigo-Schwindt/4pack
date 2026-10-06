<?php

namespace App\Livewire\Concerns;

use Livewire\WithPagination;

/**
 * Paginado de los listados del sistema: misma cantidad de filas por pagina en
 * todos y la vista propia (resources/views/vendor/livewire/tailwind.blade.php).
 */
trait Paginado
{
    use WithPagination;

    /** Filas por pagina de todos los listados. */
    public const POR_PAGINA = 20;
}
