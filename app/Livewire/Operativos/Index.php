<?php

namespace App\Livewire\Operativos;

use App\Models\Operativo;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Gestión de Operativos')]
class Index extends Component
{
    public string $buscar = '';

    /** Sector en edicion; 0 mientras se carga uno nuevo. */
    public ?int $editando = null;

    public string $sector = '';

    public string $valor_hora = '';

    public string $produccion_mts_hora = '';

    public string $setup_horas = '';

    public string $scrap_pct = '';

    public function nuevo(): void
    {
        $this->reset('sector', 'valor_hora', 'produccion_mts_hora', 'setup_horas', 'scrap_pct');
        $this->resetValidation();
        $this->editando = 0;
    }

    public function editar(int $id): void
    {
        $operativo = Operativo::findOrFail($id);

        $this->resetValidation();
        $this->editando = $operativo->id;
        $this->sector = $operativo->sector;
        $this->valor_hora = $this->comoTexto($operativo->valor_hora);
        $this->produccion_mts_hora = $this->comoTexto($operativo->produccion_mts_hora);
        $this->setup_horas = $this->comoTexto($operativo->setup_horas);
        $this->scrap_pct = $this->comoTexto($operativo->scrap_pct);
    }

    public function cancelar(): void
    {
        $this->reset('editando', 'sector', 'valor_hora', 'produccion_mts_hora', 'setup_horas', 'scrap_pct');
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'sector' => ['required', 'string', 'max:255', Rule::unique('operativos', 'sector')->ignore($this->editando ?: null)],
            'valor_hora' => ['required', 'numeric', 'min:0'],
            'produccion_mts_hora' => ['nullable', 'numeric', 'min:0'],
            'setup_horas' => ['required', 'numeric', 'min:0'],
            'scrap_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $datos['produccion_mts_hora'] = $datos['produccion_mts_hora'] !== '' && $datos['produccion_mts_hora'] !== null
            ? $datos['produccion_mts_hora']
            : null;

        if ($this->editando) {
            Operativo::findOrFail($this->editando)->update($datos);

            session()->flash('status', 'Sector actualizado.');
        } else {
            Operativo::create($datos);

            session()->flash('status', 'Sector creado.');
        }

        $this->cancelar();
    }

    public function eliminar(int $id): void
    {
        Operativo::findOrFail($id)->delete();

        if ($this->editando === $id) {
            $this->cancelar();
        }

        session()->flash('status', 'Sector eliminado.');
    }

    public function validationAttributes(): array
    {
        return [
            'sector' => 'sector',
            'valor_hora' => 'valor hora',
            'produccion_mts_hora' => 'producción',
            'setup_horas' => 'setup',
            'scrap_pct' => 'scrap',
        ];
    }

    private function comoTexto(float|string|null $valor): string
    {
        return $valor === null || $valor === '' ? '' : (string) (float) $valor;
    }

    public function render()
    {
        return view('livewire.operativos.index', [
            'operativos' => Operativo::query()
                ->when($this->buscar !== '', fn ($query) => $query->where('sector', 'like', '%'.trim($this->buscar).'%'))
                ->orderBy('id')
                ->get(),
        ]);
    }
}
