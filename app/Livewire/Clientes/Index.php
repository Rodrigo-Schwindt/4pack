<?php

namespace App\Livewire\Clientes;

use App\Livewire\Contactos\Listado;
use App\Models\Contacto;
use App\Models\Vendedor;
use Livewire\Attributes\Title;

#[Title('Clientes')]
class Index extends Listado
{
    protected function estado(): string
    {
        return Contacto::CLIENTE;
    }

    public function render()
    {
        return view('livewire.clientes.index', [
            'clientes' => $this->listado($this->vendedorActivo),
            'ficha' => $this->ficha(),
            'vendedores' => Vendedor::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }
}
