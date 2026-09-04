<?php

namespace App\Livewire\Vendedores;

use App\Models\Vendedor;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class Form extends Component
{
    public ?Vendedor $vendedor = null;

    public string $nombre = '';

    public string $comision = '';

    public bool $activo = true;

    public function mount(?Vendedor $vendedor = null): void
    {
        if (! $vendedor?->exists) {
            return;
        }

        $this->vendedor = $vendedor;
        $this->nombre = $vendedor->nombre;
        $this->comision = (string) (float) $vendedor->comision;
        $this->activo = $vendedor->activo;
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'comision' => ['required', 'numeric', 'min:0', 'max:100'],
            'activo' => ['required', 'boolean'],
        ]);

        if ($this->vendedor) {
            $this->vendedor->update($datos);

            session()->flash('status', 'Vendedor actualizado.');
        } else {
            Vendedor::create($datos);

            session()->flash('status', 'Vendedor creado.');
        }

        $this->redirectRoute('vendedores.index', navigate: true);
    }

    public function validationAttributes(): array
    {
        return ['nombre' => 'nombre', 'comision' => 'comisión', 'activo' => 'estado'];
    }

    public function render()
    {
        return view('livewire.vendedores.form')
            ->title($this->vendedor ? 'Editar vendedor' : 'Nuevo Vendedor');
    }
}
