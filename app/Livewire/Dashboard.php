<?php

namespace App\Livewire;

use App\Models\Contacto;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Toneladas y alertas siguen hardcodeadas: se reemplazan por consultas reales
 * cuando existan los modelos de cotizaciones.
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
            'prospectos' => $this->nuevosProspectos(),
        ]);
    }

    /**
     * Prospectos dados de alta en los ultimos 7 dias, el mas nuevo primero.
     */
    private function nuevosProspectos(): Collection
    {
        return Contacto::with('vendedor')
            ->enEstado(Contacto::PROSPECTO)
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Contacto $contacto) => [
                'id' => $contacto->id,
                'vendedor' => $contacto->vendedor?->nombre ?? 'Sin vendedor',
                'empresa' => $contacto->razon_social,
                'contacto' => $contacto->celular ?: $contacto->telefono ?: $contacto->email ?: '-',
                'dias' => (int) $contacto->created_at->startOfDay()->diffInDays(now()->startOfDay()),
            ]);
    }
}
