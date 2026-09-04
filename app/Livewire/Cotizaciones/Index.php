<?php

namespace App\Livewire\Cotizaciones;

use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Vista estatica hasta que exista el modulo de cotizaciones.
 */
#[Layout('components.layouts.panel')]
#[Title('Cotizaciones')]
class Index extends Component
{
    /**
     * Filas de la maqueta. El formulario las usa para saber cual es el
     * siguiente numero; desaparecen cuando exista la tabla de cotizaciones.
     */
    public const MAQUETA = [
        ['id' => 1, 'numero' => '002014/2026', 'fecha' => '13/07/2026', 'cliente' => 'Dos Anclas', 'vendedor' => 'Cristian Aguero', 'estado' => 'Pendiente'],
        ['id' => 2, 'numero' => '002013/2026', 'fecha' => '13/07/2026', 'cliente' => 'Dos Anclas', 'vendedor' => 'Cristian Aguero', 'estado' => 'Pendiente'],
        ['id' => 3, 'numero' => '002012/2026', 'fecha' => '13/07/2026', 'cliente' => 'Dos Anclas', 'vendedor' => 'Cristian Aguero', 'estado' => 'Pendiente'],
        ['id' => 4, 'numero' => '002011/2026', 'fecha' => '13/07/2026', 'cliente' => 'Dos Anclas', 'vendedor' => 'Cristian Aguero', 'estado' => 'Pendiente'],
        ['id' => 5, 'numero' => '002010/2026', 'fecha' => '13/07/2026', 'cliente' => 'Dos Anclas', 'vendedor' => 'Cristian Aguero', 'estado' => 'Pendiente'],
        ['id' => 6, 'numero' => '002009/2026', 'fecha' => '13/07/2026', 'cliente' => 'Dos Anclas', 'vendedor' => 'Cristian Aguero', 'estado' => 'Finalizado'],
        ['id' => 7, 'numero' => '002008/2026', 'fecha' => '13/07/2026', 'cliente' => 'Dos Anclas', 'vendedor' => 'Cristian Aguero', 'estado' => 'Finalizado'],
        ['id' => 8, 'numero' => '002007/2026', 'fecha' => '13/07/2026', 'cliente' => 'Dos Anclas', 'vendedor' => 'Cristian Aguero', 'estado' => 'Finalizado'],
    ];

    public string $busqueda = '';

    public function render()
    {
        $cotizaciones = collect(self::MAQUETA);

        $texto = trim($this->busqueda);

        if ($texto !== '') {
            $cotizaciones = $cotizaciones->filter(fn (array $cotizacion) => collect([
                $cotizacion['numero'], $cotizacion['cliente'], $cotizacion['vendedor'],
            ])->contains(fn (string $valor) => Str::contains($valor, $texto, ignoreCase: true)));
        }

        return view('livewire.cotizaciones.index', ['cotizaciones' => $cotizaciones]);
    }
}
