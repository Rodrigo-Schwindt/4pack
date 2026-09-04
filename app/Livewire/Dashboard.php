<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Datos hardcodeados: se reemplazan por consultas reales cuando existan
 * los modelos de cotizaciones.
 */
#[Layout('components.layouts.panel')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'toneladas' => ['objetivo' => 2, 'aprobadas' => 0.2],
            'alertas' => [
                ['codigo' => 'COT-041', 'estado' => 'Pendiente', 'cliente' => 'Industrias López S.A.', 'toneladas' => 0.8, 'dias' => 14],
                ['codigo' => 'COT-038', 'estado' => 'Pendiente', 'cliente' => 'Metalúrgica del Sur', 'toneladas' => 1.2, 'dias' => 11],
                ['codigo' => 'COT-035', 'estado' => 'Pendiente', 'cliente' => 'Grupo Fernandez', 'toneladas' => 0.5, 'dias' => 9],
            ],
            'prospectos' => [
                ['vendedor' => 'Cristian Aguero', 'empresa' => 'Construcciones Rivas', 'contacto' => 'M. Rivas', 'dias' => 1],
                ['vendedor' => 'Juan Garcia', 'empresa' => 'Plásticos del Norte', 'contacto' => 'A. Gomez', 'dias' => 2],
                ['vendedor' => 'Rodrigo Lopez', 'empresa' => 'Autopartes Mendoza', 'contacto' => 'C. Varela', 'dias' => 4],
                ['vendedor' => 'Cristian Aguero', 'empresa' => 'Embalajes Patagonia', 'contacto' => 'R. Torres', 'dias' => 6],
            ],
        ]);
    }
}
