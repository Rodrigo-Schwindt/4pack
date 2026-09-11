<?php

namespace App\Livewire\Contactos;

use App\Models\Contacto;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Listado compartido por prospectos y clientes.
 */
#[Layout('components.layouts.panel')]
abstract class Listado extends Component
{
    /** Solapa activa: id del vendedor o null para "Todos". */
    #[Url(as: 'vendedor', except: null)]
    public ?int $vendedorActivo = null;

    /** Contacto::PROSPECTO o Contacto::CLIENTE */
    abstract protected function estado(): string;

    /** Contacto abierto en la ficha de solo lectura. */
    public ?int $viendo = null;

    public function filtrar(?int $vendedorId): void
    {
        $this->vendedorActivo = $vendedorId;
    }

    public function ver(int $id): void
    {
        $this->viendo = Contacto::enEstado($this->estado())->whereKey($id)->exists() ? $id : null;
    }

    public function cerrar(): void
    {
        $this->viendo = null;
    }

    public function eliminar(int $id): void
    {
        Contacto::enEstado($this->estado())->findOrFail($id)->delete();

        if ($this->viendo === $id) {
            $this->cerrar();
        }

        session()->flash('status', ucfirst($this->estado()).' eliminado.');
    }

    /**
     * Datos del contacto abierto, listos para mostrar.
     *
     * @return array<string, mixed>|null
     */
    protected function ficha(): ?array
    {
        if ($this->viendo === null) {
            return null;
        }

        $contacto = Contacto::with(['vendedor', 'rubro', 'tipo', 'direcciones.zona'])
            ->enEstado($this->estado())
            ->find($this->viendo);

        if ($contacto === null) {
            $this->viendo = null;

            return null;
        }

        return [
            'id' => $contacto->id,
            'codigo' => $contacto->codigo,
            'razon_social' => $contacto->razon_social,
            'campos' => [
                'Nombre comercial' => $contacto->nombre_comercial,
                'CUIT' => $contacto->cuit,
                'Email' => $contacto->email,
                'Teléfono' => $contacto->telefono,
                'Celular' => $contacto->celular,
                'Página web' => $contacto->pagina_web,
                'Dirección' => $contacto->direccion,
                'Provincia' => $contacto->provincia,
                'Localidad' => $contacto->localidad,
                'Rubro' => $contacto->rubro?->nombre,
                'Tipo' => $contacto->tipo?->nombre,
                'Vendedor' => $contacto->vendedor?->nombre,
            ],
            'observaciones' => $contacto->observaciones,
            'direcciones' => $contacto->direcciones->map(fn ($direccion) => [
                'zona' => $direccion->zona?->nombre,
                'direccion' => $direccion->direccion,
                'codigo_postal' => $direccion->codigo_postal,
            ])->all(),
        ];
    }

    /**
     * Filas del listado, ya aplanadas para la tabla.
     */
    protected function listado(?int $vendedorId = null): Collection
    {
        return Contacto::with(['vendedor', 'rubro'])
            ->enEstado($this->estado())
            ->when($vendedorId, fn ($query) => $query->where('vendedor_id', $vendedorId))
            ->orderByDesc('codigo')
            ->get()
            ->map(fn (Contacto $contacto) => [
                'id' => $contacto->id,
                'codigo' => $contacto->codigo,
                'razon_social' => $contacto->razon_social,
                'localidad' => $contacto->localidad,
                'rubro' => $contacto->rubro?->nombre,
                'vendedor' => $contacto->vendedor?->nombre,
            ]);
    }
}
