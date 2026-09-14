<?php

namespace App\Livewire\Configuracion;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Configuración')]
class Index extends Component
{
    public function render()
    {
        // Sin href, la tarjeta todavia no tiene vista.
        return view('livewire.configuracion.index', [
            'tarjetas' => [
                ['titulo' => 'Usuarios', 'descripcion' => 'Administrar usuarios', 'icono' => 'users', 'href' => null],
                ['titulo' => 'Roles', 'descripcion' => 'Administrar roles', 'icono' => 'shield', 'href' => null],
                ['titulo' => 'Insumos', 'descripcion' => 'Administrar Insumos', 'icono' => 'package', 'href' => '/insumos'],
                ['titulo' => 'Flete Insumos', 'descripcion' => 'Administrar Flete Insumos', 'icono' => 'truck', 'href' => '/flete-insumos'],
                ['titulo' => 'Operativos', 'descripcion' => 'Costos operativos por sector', 'icono' => 'list', 'href' => '/operativos'],
                ['titulo' => 'Ajuste cambiario', 'descripcion' => 'Administrar Flete Insumos', 'icono' => 'list', 'href' => null],
                ['titulo' => 'Impresión', 'descripcion' => 'Administrar Flete Insumos', 'icono' => 'list', 'href' => null],
                ['titulo' => 'Varios', 'descripcion' => 'Administrar Flete Insumos', 'icono' => 'list', 'href' => null],
                ['titulo' => 'Variables Costos', 'descripcion' => 'Márgenes, descuentos y financiación', 'icono' => 'list', 'href' => '/variables-costos'],
                ['titulo' => 'Ajustes', 'descripcion' => 'Valores de los selects del sistema', 'icono' => 'sliders-horizontal', 'href' => '/configuracion/ajustes'],
                ['titulo' => 'Vendedores', 'descripcion' => 'Administrar vendedores', 'icono' => 'users', 'href' => '/vendedores'],
            ],
        ]);
    }
}
