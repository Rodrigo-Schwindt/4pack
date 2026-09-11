<?php

namespace App\Livewire\Prospectos;

use App\Livewire\Contactos\Listado;
use App\Models\Contacto;
use App\Models\Vendedor;
use Livewire\Attributes\Title;

#[Title('Prospectos')]
class Index extends Listado
{
    protected function estado(): string
    {
        return Contacto::PROSPECTO;
    }

    public function render()
    {
        return view('livewire.prospectos.index', [
            'prospectos' => $this->listado($this->vendedorActivo),
            'ficha' => $this->ficha(),
            'vendedores' => Vendedor::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }
}
