<?php

namespace App\Livewire\Vendedores;

use App\Models\Vendedor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Gestión de Vendedores')]
class Index extends Component
{
    public function eliminar(int $id): void
    {
        Vendedor::findOrFail($id)->delete();

        session()->flash('status', 'Vendedor eliminado.');
    }

    public function render()
    {
        return view('livewire.vendedores.index', [
            'vendedores' => Vendedor::orderBy('nombre')->get(['id', 'nombre', 'comision', 'activo']),
        ]);
    }
}
