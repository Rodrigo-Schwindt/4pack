<?php

namespace App\Livewire\Cotizaciones;

use App\Models\Cotizacion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Cotizaciones')]
class Index extends Component
{
    public string $busqueda = '';

    public function eliminar(int $id): void
    {
        Cotizacion::findOrFail($id)->delete();

        session()->flash('status', 'Cotización eliminada.');
    }

    public function cambiarEstado(int $id): void
    {
        $cotizacion = Cotizacion::findOrFail($id);

        // Pendiente -> Aprobada -> Finalizada -> Pendiente.
        $estados = array_keys(Cotizacion::ESTADOS);
        $siguiente = $estados[(array_search($cotizacion->estado, $estados, true) + 1) % count($estados)];

        $cotizacion->update([
            'estado' => $siguiente,
            'aprobada_en' => $siguiente === Cotizacion::APROBADA ? now() : ($siguiente === Cotizacion::PENDIENTE ? null : $cotizacion->aprobada_en),
        ]);
    }

    public function render()
    {
        $texto = trim($this->busqueda);

        $cotizaciones = Cotizacion::with(['cliente', 'vendedor'])
            ->when($texto !== '', function ($query) use ($texto) {
                $query->where(function ($query) use ($texto) {
                    $query->where('numero', 'like', "%{$texto}%")
                        ->orWhere('referencia', 'like', "%{$texto}%")
                        ->orWhereHas('cliente', fn ($q) => $q->where('razon_social', 'like', "%{$texto}%"))
                        ->orWhereHas('vendedor', fn ($q) => $q->where('nombre', 'like', "%{$texto}%"));
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Cotizacion $cotizacion) => [
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero,
                'fecha' => $cotizacion->fecha->format('d/m/Y'),
                'cliente' => $cotizacion->cliente?->razon_social ?? '-',
                'vendedor' => $cotizacion->vendedor?->nombre ?? '-',
                'estado' => $cotizacion->estado,
                'estado_nombre' => Cotizacion::ESTADOS[$cotizacion->estado] ?? ucfirst($cotizacion->estado),
            ]);

        return view('livewire.cotizaciones.index', ['cotizaciones' => $cotizaciones, 'buscando' => $texto !== '']);
    }
}
