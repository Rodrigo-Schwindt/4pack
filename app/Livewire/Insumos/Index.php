<?php

namespace App\Livewire\Insumos;

use App\Models\Insumo;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Gestión de insumos')]
class Index extends Component
{
    public string $buscar = '';

    /** Insumo en edicion; 0 mientras se carga uno nuevo. */
    public ?int $editando = null;

    public string $nombre = '';

    public string $singular = '';

    public function nuevo(): void
    {
        $this->reset('nombre', 'singular');
        $this->resetValidation();
        $this->editando = 0;
    }

    public function editar(int $id): void
    {
        $insumo = Insumo::findOrFail($id);

        $this->resetValidation();
        $this->editando = $insumo->id;
        $this->nombre = $insumo->nombre;
        $this->singular = $insumo->getRawOriginal('singular') ?? '';
    }

    public function cancelar(): void
    {
        $this->reset('editando', 'nombre', 'singular');
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('insumos', 'nombre')->ignore($this->editando ?: null)],
            'singular' => ['nullable', 'string', 'max:255'],
        ]);

        $datos['singular'] = $datos['singular'] ?: null;

        if ($this->editando) {
            Insumo::findOrFail($this->editando)->update($datos);

            session()->flash('status', 'Insumo actualizado.');
        } else {
            Insumo::create($datos);

            session()->flash('status', 'Insumo creado.');
        }

        $this->cancelar();
    }

    public function eliminar(int $id): void
    {
        Insumo::findOrFail($id)->delete();

        if ($this->editando === $id) {
            $this->cancelar();
        }

        session()->flash('status', 'Insumo eliminado.');
    }

    public function validationAttributes(): array
    {
        return ['nombre' => 'nombre', 'singular' => 'singular'];
    }

    public function render()
    {
        return view('livewire.insumos.index', [
            'insumos' => Insumo::query()
                ->when($this->buscar !== '', fn ($query) => $query->where('nombre', 'like', '%'.trim($this->buscar).'%'))
                ->orderBy('id')
                ->get(),
        ]);
    }
}
