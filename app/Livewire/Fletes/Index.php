<?php

namespace App\Livewire\Fletes;

use App\Models\Ajuste;
use App\Models\FletePrecio;
use App\Models\FleteTramo;
use App\Models\FleteZona;
use App\Services\DolarOficial;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Gestión de Flete Insumos')]
class Index extends Component
{
    /** Grupo de Ajustes donde vive la cotizacion del dolar. */
    public const GRUPO_DOLAR = 'dolar';

    public string $buscar = '';

    public string $dolar = '';

    /** Si el dolar se toma solo de la cotizacion oficial (BNA) en vez de cargarse a mano. */
    public bool $dolarAutomatico = false;

    /** Zona en edicion; 0 mientras se carga una nueva. */
    public ?int $editando = null;

    /** Zona de la que cuelga la subzona que se esta cargando. */
    public ?int $zonaPadre = null;

    public string $nombre = '';

    /** @var array<int, string> precio por id de tramo */
    public array $precios = [];

    public bool $mostrarTramos = false;

    public string $tramoKg = '';

    public string $tramoPallets = '';

    public function mount(): void
    {
        $this->dolar = $this->comoTexto(Ajuste::valorDe(self::GRUPO_DOLAR));
        $this->dolarAutomatico = DolarOficial::automatico();
    }

    /**
     * Al pasar a automatico se trae la cotizacion en el momento; si la API no
     * responde queda el valor que habia y se avisa.
     */
    public function updatedDolarAutomatico(): void
    {
        DolarOficial::definirAutomatico($this->dolarAutomatico);

        if ($this->dolarAutomatico) {
            $this->actualizarDolar();
        }
    }

    public function actualizarDolar(): void
    {
        $valor = app(DolarOficial::class)->actualizar();

        if ($valor === null) {
            $this->addError('dolar', 'No se pudo consultar la cotización oficial. Se mantiene el valor cargado.');

            return;
        }

        $this->resetErrorBag('dolar');
        $this->dolar = $this->comoTexto($valor);
    }

    public function updatedDolar(): void
    {
        $this->validateOnly('dolar', ['dolar' => ['required', 'numeric', 'min:0.01']]);

        Ajuste::definir(self::GRUPO_DOLAR, $this->dolar);
    }

    public function nueva(): void
    {
        $this->reset('nombre', 'precios', 'zonaPadre');
        $this->resetValidation();
        $this->editando = 0;
    }

    /**
     * Subzona de una zona: misma tabla de precios, colgada de la principal.
     */
    public function nuevaSubzona(int $padreId): void
    {
        $this->reset('nombre', 'precios');
        $this->resetValidation();
        $this->editando = 0;
        $this->zonaPadre = FleteZona::principales()->whereKey($padreId)->value('id');
    }

    public function editar(int $id): void
    {
        $zona = FleteZona::with('precios')->findOrFail($id);

        $this->resetValidation();
        $this->editando = $zona->id;
        $this->zonaPadre = $zona->zona_padre_id;
        $this->nombre = $zona->nombre;
        $this->precios = array_map($this->comoTexto(...), $zona->preciosPorTramo());
    }

    public function cancelar(): void
    {
        $this->reset('editando', 'nombre', 'precios', 'zonaPadre');
        $this->resetValidation();
    }

    public function guardar(): void
    {
        // Si la zona ya existe (por ejemplo, creada desde un cliente) no es un
        // error: se le cargan los precios a esa en vez de pedir otro nombre.
        // Con una subzona nueva no aplica: se crea colgada de su zona.
        if (! $this->editando && $this->zonaPadre === null) {
            $this->editando = FleteZona::where('nombre', trim($this->nombre))->value('id') ?? 0;
        }

        $datos = $this->validate([
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('flete_zonas', 'nombre')->ignore($this->editando ?: null),
            ],
            'precios' => ['array'],
            'precios.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $zona = $this->editando
            ? tap(FleteZona::findOrFail($this->editando))->update(['nombre' => $datos['nombre'], 'zona_padre_id' => $this->zonaPadre])
            : FleteZona::create(['nombre' => $datos['nombre'], 'zona_padre_id' => $this->zonaPadre]);

        foreach (FleteTramo::pluck('id') as $tramoId) {
            $precio = $datos['precios'][$tramoId] ?? null;

            if ($precio === null || $precio === '') {
                $zona->precios()->where('flete_tramo_id', $tramoId)->delete();

                continue;
            }

            FletePrecio::updateOrCreate(
                ['flete_zona_id' => $zona->id, 'flete_tramo_id' => $tramoId],
                ['precio' => $precio],
            );
        }

        $que = $this->zonaPadre === null ? 'Zona' : 'Subzona';
        session()->flash('status', $this->editando ? $que.' actualizada.' : $que.' creada.');

        $this->cancelar();
    }

    /** Se lleva puestas sus subzonas con sus precios. */
    public function eliminar(int $id): void
    {
        FleteZona::findOrFail($id)->delete();

        if ($this->editando === $id) {
            $this->cancelar();
        }

        session()->flash('status', 'Zona eliminada.');
    }

    public function agregarTramo(): void
    {
        $this->validate([
            'tramoKg' => ['required', 'numeric', 'min:0.01'],
            'tramoPallets' => ['required', 'integer', 'min:1'],
        ]);

        FleteTramo::firstOrCreate(['kg' => $this->tramoKg, 'pallets' => $this->tramoPallets]);

        $this->reset('tramoKg', 'tramoPallets');

        session()->flash('status', 'Tramo agregado.');
    }

    /** Se lleva puestos los precios cargados en esa columna. */
    public function eliminarTramo(int $id): void
    {
        FleteTramo::findOrFail($id)->delete();

        unset($this->precios[$id]);

        session()->flash('status', 'Tramo eliminado.');
    }

    public function validationAttributes(): array
    {
        return [
            'nombre' => 'zona',
            'dolar' => 'cotización del dólar',
            'tramoKg' => 'kg',
            'tramoPallets' => 'pallets',
            'precios.*' => 'precio',
        ];
    }

    private function comoTexto(float|string|null $valor): string
    {
        return $valor === null || $valor === '' ? '' : (string) (float) $valor;
    }

    public function render()
    {
        // Como en la maqueta: de la zona mas barata a la mas cara, y alfabetico si empatan.
        // Las subzonas van debajo de la suya, no como filas sueltas.
        $buscado = trim($this->buscar);
        $zonas = FleteZona::with(['precios', 'subzonas.precios'])
            ->principales()
            ->when($buscado !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('nombre', 'like', '%'.$buscado.'%')
                ->orWhereHas('subzonas', fn ($sub) => $sub->where('nombre', 'like', '%'.$buscado.'%'))))
            ->orderByRaw('(select coalesce(min(precio), 1e12) from flete_precios where flete_zona_id = flete_zonas.id) asc')
            ->orderBy('nombre')
            ->get();

        return view('livewire.fletes.index', [
            'tramos' => FleteTramo::orderBy('kg')->orderBy('pallets')->get(),
            // Zonas dadas de alta desde otra pantalla que todavia no tienen precios.
            'pendientes' => FleteZona::principales()->doesntHave('precios')->orderBy('nombre')->get(['id', 'nombre']),
            'zonas' => $zonas,
            'cotizacion' => is_numeric($this->dolar) ? (float) $this->dolar : 0.0,
            'dolarUltima' => DolarOficial::ultima(),
        ]);
    }
}
