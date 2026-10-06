<?php

namespace App\Livewire\Configuracion;

use App\Models\Rol;
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
        $tarjetas = [
            ['titulo' => 'Usuarios', 'descripcion' => 'Administrar usuarios', 'icono' => 'users', 'href' => '/usuarios', 'permiso' => Rol::USUARIOS],
            ['titulo' => 'Roles', 'descripcion' => 'Administrar roles', 'icono' => 'shield', 'href' => '/roles', 'permiso' => Rol::USUARIOS],
            ['titulo' => 'Insumos', 'descripcion' => 'Administrar Insumos', 'icono' => 'package', 'href' => '/insumos', 'permiso' => Rol::COSTOS],
            ['titulo' => 'Flete Insumos', 'descripcion' => 'Administrar Flete Insumos', 'icono' => 'truck', 'href' => '/flete-insumos', 'permiso' => Rol::COSTOS],
            ['titulo' => 'Operativos', 'descripcion' => 'Costos operativos por sector', 'icono' => 'list', 'href' => '/operativos', 'permiso' => Rol::COSTOS],
            ['titulo' => 'Ajuste cambiario', 'descripcion' => 'Administrar Flete Insumos', 'icono' => 'list', 'href' => null, 'permiso' => Rol::COSTOS],
            ['titulo' => 'Impresión', 'descripcion' => 'Administrar Flete Insumos', 'icono' => 'list', 'href' => null, 'permiso' => Rol::COSTOS],
            ['titulo' => 'Varios', 'descripcion' => 'Cajas y otros insumos sueltos', 'icono' => 'list', 'href' => ($varios = \App\Models\Insumo::where('nombre', 'Varios')->value('id')) ? '/insumos/'.$varios : '/insumos', 'permiso' => Rol::COSTOS],
            ['titulo' => 'Variables Costos', 'descripcion' => 'Márgenes, descuentos y financiación', 'icono' => 'list', 'href' => '/variables-costos', 'permiso' => Rol::COSTOS],
            ['titulo' => 'Ajustes', 'descripcion' => 'Valores de los selects del sistema', 'icono' => 'sliders-horizontal', 'href' => '/configuracion/ajustes', 'permiso' => Rol::COSTOS],
            ['titulo' => 'Vendedores', 'descripcion' => 'Administrar vendedores', 'icono' => 'users', 'href' => '/vendedores', 'permiso' => Rol::VENDEDORES],
            ['titulo' => 'Entregas', 'descripcion' => 'Entregas de los pedidos y comisiones', 'icono' => 'truck', 'href' => '/entregas', 'permiso' => Rol::VENDEDORES],
        ];

        // Solo lo que el usuario puede administrar.
        return view('livewire.configuracion.index', [
            'tarjetas' => array_values(array_filter($tarjetas, fn (array $tarjeta) => auth()->user()->puede($tarjeta['permiso']))),
        ]);
    }
}
