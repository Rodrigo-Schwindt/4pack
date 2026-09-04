<?php

namespace App\Livewire\Contactos;

use App\Models\Contacto;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Listado compartido por prospectos y clientes.
 */
#[Layout('components.layouts.panel')]
abstract class Listado extends Component
{
    /** Contacto::PROSPECTO o Contacto::CLIENTE */
    abstract protected function estado(): string;

    public function eliminar(int $id): void
    {
        Contacto::enEstado($this->estado())->findOrFail($id)->delete();

        session()->flash('status', ucfirst($this->estado()).' eliminado.');
    }

    /**
     * Filas del listado, ya aplanadas para la tabla.
     */
    protected function listado(?int $vendedorId = null): Collection
    {
        return Contacto::with('vendedor')
            ->enEstado($this->estado())
            ->when($vendedorId, fn ($query) => $query->where('vendedor_id', $vendedorId))
            ->orderByDesc('codigo')
            ->get()
            ->map(fn (Contacto $contacto) => [
                'id' => $contacto->id,
                'codigo' => $contacto->codigo,
                'razon_social' => $contacto->razon_social,
                'localidad' => $contacto->localidad,
                'vendedor' => $contacto->vendedor?->nombre,
            ]);
    }
}
