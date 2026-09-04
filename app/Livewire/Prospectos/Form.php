<?php

namespace App\Livewire\Prospectos;

use App\Livewire\Contactos\Formulario;
use App\Models\Contacto;

class Form extends Formulario
{
    protected function estado(): string
    {
        return Contacto::PROSPECTO;
    }

    protected function ruta(): string
    {
        return 'prospectos';
    }

    public function render()
    {
        return view('livewire.prospectos.form', $this->datosVista())
            ->title($this->contacto ? 'Prospecto '.$this->codigo : 'Nuevo Prospecto');
    }
}
