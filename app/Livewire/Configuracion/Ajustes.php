<?php

namespace App\Livewire\Configuracion;

use App\Models\Ajuste;
use App\Models\AjusteTexto;
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
        AjusteTexto::CANALES_OC => 'Canales de OC',
    ];

    /** Grupos que son una lista de textos en vez de numeros. */
    public const TEXTOS = [AjusteTexto::CANALES_OC];

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
        if ($this->esTexto()) {
            $this->validate([
                'valor' => ['required', 'string', 'max:60', Rule::unique('ajuste_textos', 'texto')->where('grupo', $this->grupo)],
            ]);

            AjusteTexto::create(['grupo' => $this->grupo, 'texto' => trim($this->valor)]);
            $this->reset('valor');
            session()->flash('status', 'Opción agregada.');

            return;
        }

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
        if ($this->esTexto()) {
            AjusteTexto::delGrupo($this->grupo)->findOrFail($id)->delete();
            session()->flash('status', 'Opción eliminada.');

            return;
        }

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

    private function esTexto(): bool
    {
        return in_array($this->grupo, self::TEXTOS, true);
    }

    public function render()
    {
        return view('livewire.configuracion.ajustes', [
            'grupos' => self::GRUPOS,
            'valores' => match (true) {
                $this->grupo === self::VARIABLES => collect(),
                // Los de texto se muestran igual: id y "valor".
                $this->esTexto() => AjusteTexto::delGrupo($this->grupo)->orderBy('texto')->get(['id', 'texto as valor']),
                default => Ajuste::delGrupo($this->grupo)->orderBy('valor')->get(['id', 'valor']),
            },
            'esTexto' => $this->esTexto(),
            'secciones' => Parametro::todas(),
        ]);
    }
}
