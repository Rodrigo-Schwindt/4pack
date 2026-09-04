<?php

namespace App\Livewire\Prospectos;

use App\Livewire\Contactos\Listado;
use App\Models\Contacto;
use App\Models\Vendedor;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Prospectos')]
class Index extends Listado
{
    /** Solapa activa: id del vendedor o null para "Todos". */
    #[Url(as: 'vendedor', except: null)]
    public ?int $vendedorActivo = null;

    protected function estado(): string
    {
        return Contacto::PROSPECTO;
    }

    public function filtrar(?int $vendedorId): void
    {
        $this->vendedorActivo = $vendedorId;
    }

    public function render()
    {
        return view('livewire.prospectos.index', [
            'prospectos' => $this->listado($this->vendedorActivo),
            'vendedores' => Vendedor::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }
}
