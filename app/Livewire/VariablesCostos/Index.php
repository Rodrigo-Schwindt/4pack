<?php

namespace App\Livewire\VariablesCostos;

use App\Models\VariableCosto;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Variables Costos')]
class Index extends Component
{
    public string $tipo = VariableCosto::MARGEN;

    /** Fila en edicion; 0 mientras se carga una nueva. */
    public ?int $editando = null;

    public string $clave = '';

    public string $valor = '';

    public function seleccionar(string $tipo): void
    {
        if (! isset(VariableCosto::TIPOS[$tipo])) {
            return;
        }

        $this->tipo = $tipo;
        $this->cancelar();
    }

    public function nueva(): void
    {
        $this->reset('clave', 'valor');
        $this->resetValidation();
        $this->editando = 0;
    }

    public function editar(int $id): void
    {
        $fila = VariableCosto::delTipo($this->tipo)->findOrFail($id);

        $this->resetValidation();
        $this->editando = $fila->id;
        $this->clave = $fila->clave;
        $this->valor = rtrim(rtrim(number_format((float) $fila->valor, 4, '.', ''), '0'), '.');
    }

    public function cancelar(): void
    {
        $this->reset('editando', 'clave', 'valor');
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $reglasClave = ['required', 'string', 'max:255'];

        // Volumen y financiacion se indexan por numero; volumen admite ademas "resto".
        if ($this->tipo === VariableCosto::FINANCIACION) {
            $reglasClave[] = 'integer';
            $reglasClave[] = 'min:1';
        } elseif ($this->tipo === VariableCosto::VOLUMEN && $this->clave !== VariableCosto::RESTO) {
            $reglasClave[] = 'numeric';
            $reglasClave[] = 'min:1';
        }

        $reglasClave[] = Rule::unique('variables_costos', 'clave')->where('tipo', $this->tipo)->ignore($this->editando ?: null);

        $datos = $this->validate([
            'clave' => $reglasClave,
            'valor' => ['required', 'numeric', 'min:-100', 'max:1000'],
        ]);

        $datos['clave'] = trim($datos['clave']);

        if ($this->editando) {
            VariableCosto::delTipo($this->tipo)->findOrFail($this->editando)->update($datos);

            session()->flash('status', 'Valor actualizado.');
        } else {
            VariableCosto::create($datos + ['tipo' => $this->tipo]);

            session()->flash('status', 'Valor agregado.');
        }

        $this->cancelar();
    }

    public function eliminar(int $id): void
    {
        VariableCosto::delTipo($this->tipo)->findOrFail($id)->delete();

        if ($this->editando === $id) {
            $this->cancelar();
        }

        session()->flash('status', 'Valor eliminado.');
    }

    public function validationAttributes(): array
    {
        return ['clave' => strtolower(VariableCosto::TIPOS[$this->tipo][1]), 'valor' => 'porcentaje'];
    }

    public function render()
    {
        return view('livewire.variables-costos.index', [
            'tipos' => VariableCosto::TIPOS,
            'filas' => VariableCosto::listado($this->tipo),
        ]);
    }
}
