<?php

namespace App\Livewire;

use App\Models\Contacto;
use App\Models\Cotizacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Kilos cotizados, con orden de compra y entregados en un rango de fechas,
 * cotizaciones pendientes hace mas de 7 dias y prospectos de la semana.
 */
#[Layout('components.layouts.panel')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    /** Rango del panel de kilos; arranca en el mes en curso. */
    public string $desde = '';

    public string $hasta = '';

    public function mount(): void
    {
        $this->desde = now()->startOfMonth()->format('Y-m-d');
        $this->hasta = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedDesde(): void
    {
        $this->validarRango();
    }

    public function updatedHasta(): void
    {
        $this->validarRango();
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'ingreso' => $this->ingresoKg(),
            'alertas' => $this->alertas(),
            'prospectos' => $this->nuevosProspectos(),
        ]);
    }

    private function validarRango(): void
    {
        $this->validate(
            [
                'desde' => ['required', 'date'],
                'hasta' => ['required', 'date', 'after_or_equal:desde'],
            ],
            ['hasta.after_or_equal' => 'El hasta no puede ser anterior al desde.'],
            ['desde' => 'desde', 'hasta' => 'hasta'],
        );
    }

    /**
     * Kilos del rango: lo cotizado por fecha de cotizacion, lo que entro por
     * orden de compra y lo que se entrego, cada uno con su propia fecha.
     *
     * @return array{cotizado: float, oc: float, entregado: float}
     */
    private function ingresoKg(): array
    {
        [$desde, $hasta] = $this->rango();

        $cotizado = 0.0;
        $oc = 0.0;
        $entregado = 0.0;

        foreach (Cotizacion::all() as $cotizacion) {
            if ($cotizacion->fecha->betweenIncluded($desde, $hasta)) {
                $cotizado += $cotizacion->pesoKg();
            }

            $ingresoOc = $cotizacion->fechaOrdenCompra();

            if ($ingresoOc !== null && $ingresoOc->betweenIncluded($desde, $hasta)) {
                $oc += $cotizacion->pesoKg();
            }

            $entregado += $cotizacion->kgEntregadosEntre($desde, $hasta);
        }

        return ['cotizado' => $cotizado, 'oc' => $oc, 'entregado' => $entregado];
    }

    /**
     * El rango elegido. Si todavia no es una fecha valida, el mes en curso.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rango(): array
    {
        $desde = $this->comoFecha($this->desde) ?? now()->startOfMonth();
        $hasta = $this->comoFecha($this->hasta) ?? now()->endOfMonth();

        return $hasta->lt($desde) ? [$desde, $desde->copy()] : [$desde, $hasta];
    }

    private function comoFecha(string $valor): ?Carbon
    {
        try {
            return $valor === '' ? null : Carbon::parse($valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
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
