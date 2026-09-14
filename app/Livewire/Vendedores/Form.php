<?php

namespace App\Livewire\Vendedores;

use App\Models\Vendedor;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class Form extends Component
{
    /**
     * Etiqueta de cada comision, en el orden en que se muestran.
     */
    public const COMISIONES = [
        'comision_bobinas' => 'Bobinas',
        'comision_dpk' => 'DPK',
        'comision_pouch' => 'Pouch',
        'comision_4_costuras' => '4 Costuras',
    ];

    public ?Vendedor $vendedor = null;

    public string $nombre = '';

    /** @var array<string, string> comision (%) por tipo de producto */
    public array $comisiones = [
        'comision_bobinas' => '',
        'comision_dpk' => '',
        'comision_pouch' => '',
        'comision_4_costuras' => '',
    ];

    public bool $activo = true;

    public function mount(?Vendedor $vendedor = null): void
    {
        if (! $vendedor?->exists) {
            return;
        }

        $this->vendedor = $vendedor;
        $this->nombre = $vendedor->nombre;
        $this->activo = $vendedor->activo;

        foreach (array_keys(self::COMISIONES) as $columna) {
            $this->comisiones[$columna] = (string) (float) $vendedor->{$columna};
        }
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'comisiones.*' => ['required', 'numeric', 'min:0', 'max:100'],
            'activo' => ['required', 'boolean'],
        ]);

        $datos = ['nombre' => $datos['nombre'], 'activo' => $datos['activo']] + $datos['comisiones'];

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
        $atributos = ['nombre' => 'nombre', 'activo' => 'estado'];

        foreach (self::COMISIONES as $columna => $etiqueta) {
            $atributos['comisiones.'.$columna] = 'comisión '.$etiqueta;
        }

        return $atributos;
    }

    public function render()
    {
        return view('livewire.vendedores.form', ['comisionesEtiquetas' => self::COMISIONES])
            ->title($this->vendedor ? 'Editar vendedor' : 'Nuevo Vendedor');
    }
}
