<?php

namespace App\Livewire;

use App\Models\Contacto;
use App\Models\Cotizacion;
use App\Models\Parametro;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Toneladas aprobadas hoy, cotizaciones pendientes hace mas de 7 dias y
 * prospectos de la semana, todo desde la base.
 */
#[Layout('components.layouts.panel')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'toneladas' => $this->toneladasHoy(),
            'alertas' => $this->alertas(),
            'prospectos' => $this->nuevosProspectos(),
        ]);
    }

    /**
     * Toneladas de las cotizaciones aprobadas hoy contra el objetivo diario.
     *
     * @return array{objetivo: float, aprobadas: float}
     */
    private function toneladasHoy(): array
    {
        $kilos = Cotizacion::where('estado', Cotizacion::APROBADA)
            ->whereDate('aprobada_en', today())
            ->get()
            ->sum(fn (Cotizacion $cotizacion) => $cotizacion->pesoKg());

        return [
            'objetivo' => max(Parametro::valor(Parametro::OBJETIVO_TONELADAS_DIA), 0.01),
            'aprobadas' => round($kilos / 1000, 2),
        ];
    }

    /**
     * Cotizaciones pendientes hace mas de 7 dias, la mas vieja primero.
     */
    private function alertas(): Collection
    {
        return Cotizacion::with('cliente')
            ->where('estado', Cotizacion::PENDIENTE)
            ->where('fecha', '<', today()->subDays(7))
            ->orderBy('fecha')
            ->get()
            ->map(fn (Cotizacion $cotizacion) => [
                'id' => $cotizacion->id,
                'codigo' => $cotizacion->numero,
                'estado' => Cotizacion::ESTADOS[$cotizacion->estado],
                'cliente' => $cotizacion->cliente?->razon_social ?? '-',
                'toneladas' => round($cotizacion->pesoKg() / 1000, 2),
                'dias' => (int) $cotizacion->fecha->startOfDay()->diffInDays(today()),
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
