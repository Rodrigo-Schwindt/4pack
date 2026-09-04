<?php

namespace App\Livewire\Configuracion;

use App\Models\Ajuste;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Ajustes')]
class Ajustes extends Component
{
    /**
     * Grupos de valores disponibles. Cada uno alimenta un select del sistema.
     */
    public const GRUPOS = [
        'mangas' => 'Mangas',
        'bujes' => 'Bujes',
    ];

    public string $grupo = 'mangas';

    public string $valor = '';

    public function seleccionar(string $grupo): void
    {
        if (! array_key_exists($grupo, self::GRUPOS)) {
            return;
        }

        $this->grupo = $grupo;
        $this->reset('valor');
        $this->resetValidation();
    }

    public function agregar(): void
    {
        $this->validate([
            'grupo' => ['required', Rule::in(array_keys(self::GRUPOS))],
            'valor' => [
                'required',
                'numeric',
                'min:0',
                Rule::unique('ajustes', 'valor')->where('grupo', $this->grupo),
            ],
        ]);

        Ajuste::create(['grupo' => $this->grupo, 'valor' => $this->valor]);

        $this->reset('valor');

        session()->flash('status', 'Valor agregado.');
    }

    public function eliminar(int $id): void
    {
        Ajuste::delGrupo($this->grupo)->findOrFail($id)->delete();

        session()->flash('status', 'Valor eliminado.');
    }

    public function validationAttributes(): array
    {
        return ['grupo' => 'grupo', 'valor' => 'valor'];
    }

    public function messages(): array
    {
        return ['valor.unique' => 'Ese valor ya está cargado en este grupo.'];
    }

    public function render()
    {
        return view('livewire.configuracion.ajustes', [
            'grupos' => self::GRUPOS,
            'valores' => Ajuste::delGrupo($this->grupo)->orderBy('valor')->get(['id', 'valor']),
        ]);
    }
}
