<?php

namespace App\Livewire\Configuracion;

use App\Models\Ajuste;
use App\Models\Parametro;
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

    /** Solapa de las constantes de las formulas, ademas de los grupos. */
    public const VARIABLES = 'variables';

    public string $grupo = 'mangas';

    public string $valor = '';

    /**
     * Valor de cada variable de calculo, editable en linea.
     *
     * @var array<string, string>
     */
    public array $variables = [];

    public function mount(): void
    {
        foreach (Parametro::todas() as $seccion) {
            foreach ($seccion as $clave => $variable) {
                $this->variables[$clave] = $this->sinCerosDeMas($variable['valor']);
            }
        }
    }

    public function seleccionar(string $grupo): void
    {
        if ($grupo !== self::VARIABLES && ! array_key_exists($grupo, self::GRUPOS)) {
            return;
        }

        $this->grupo = $grupo;
        $this->reset('valor');
        $this->resetValidation();
    }

    /**
     * Cada variable se guarda al salir del campo.
     */
    public function updatedVariables(mixed $valor, ?string $clave = null): void
    {
        if ($clave === null || Parametro::defecto($clave) === null) {
            return;
        }

        $this->validate(['variables.'.$clave => ['required', 'numeric', 'min:0']], attributes: ['variables.'.$clave => 'valor']);

        Parametro::guardar($clave, (float) $valor);
    }

    public function restaurar(string $clave): void
    {
        $defecto = Parametro::defecto($clave);

        if ($defecto === null) {
            return;
        }

        Parametro::guardar($clave, $defecto);

        $this->variables[$clave] = $this->sinCerosDeMas($defecto);
        $this->resetValidation('variables.'.$clave);
    }

    private function sinCerosDeMas(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 4, '.', ''), '0'), '.');
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
            'valores' => $this->grupo === self::VARIABLES
                ? collect()
                : Ajuste::delGrupo($this->grupo)->orderBy('valor')->get(['id', 'valor']),
            'secciones' => Parametro::todas(),
        ]);
    }
}
