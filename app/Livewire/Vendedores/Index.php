<?php

namespace App\Livewire\Vendedores;

use App\Livewire\Concerns\Paginado;
use App\Models\Vendedor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Gestión de Vendedores')]
class Index extends Component
{
    use Paginado;

    public function eliminar(int $id): void
    {
        Vendedor::findOrFail($id)->delete();

        session()->flash('status', 'Vendedor eliminado.');
    }

    public function render()
    {
        return view('livewire.vendedores.index', [
            'vendedores' => Vendedor::orderBy('nombre')->paginate(self::POR_PAGINA, ['id', 'nombre', 'comision_bobinas', 'comision_dpk', 'comision_pouch', 'comision_4_costuras', 'activo']),
        ]);
    }
}
