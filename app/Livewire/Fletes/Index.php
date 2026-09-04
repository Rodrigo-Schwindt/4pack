<?php

namespace App\Livewire\Fletes;

use App\Models\Ajuste;
use App\Models\FletePrecio;
use App\Models\FleteTramo;
use App\Models\FleteZona;
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

    /** Zona en edicion; 0 mientras se carga una nueva. */
    public ?int $editando = null;

    public string $nombre = '';

    /** @var array<int, string> precio por id de tramo */
    public array $precios = [];

    public bool $mostrarTramos = false;

    public string $tramoKg = '';

    public string $tramoPallets = '';

    public function mount(): void
    {
        $this->dolar = $this->comoTexto(Ajuste::valorDe(self::GRUPO_DOLAR));
    }

    public function updatedDolar(): void
    {
        $this->validateOnly('dolar', ['dolar' => ['required', 'numeric', 'min:0.01']]);

        Ajuste::definir(self::GRUPO_DOLAR, $this->dolar);
    }

    public function nueva(): void
    {
        $this->reset('nombre', 'precios');
        $this->resetValidation();
        $this->editando = 0;
    }

    public function editar(int $id): void
    {
        $zona = FleteZona::with('precios')->findOrFail($id);

        $this->resetValidation();
        $this->editando = $zona->id;
        $this->nombre = $zona->nombre;
        $this->precios = array_map($this->comoTexto(...), $zona->preciosPorTramo());
    }

    public function cancelar(): void
    {
        $this->reset('editando', 'nombre', 'precios');
        $this->resetValidation();
    }

    public function guardar(): void
    {
        // Si la zona ya existe (por ejemplo, creada desde un cliente) no es un
        // error: se le cargan los precios a esa en vez de pedir otro nombre.
        if (! $this->editando) {
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
            ? tap(FleteZona::findOrFail($this->editando))->update(['nombre' => $datos['nombre']])
            : FleteZona::create(['nombre' => $datos['nombre']]);

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

        session()->flash('status', $this->editando ? 'Zona actualizada.' : 'Zona creada.');

        $this->cancelar();
    }

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
        $zonas = FleteZona::with('precios')
            ->when($this->buscar !== '', fn ($query) => $query->where('nombre', 'like', '%'.trim($this->buscar).'%'))
            ->orderByRaw('(select coalesce(min(precio), 1e12) from flete_precios where flete_zona_id = flete_zonas.id) asc')
            ->orderBy('nombre')
            ->get();

        return view('livewire.fletes.index', [
            'tramos' => FleteTramo::orderBy('kg')->orderBy('pallets')->get(),
            // Zonas dadas de alta desde otra pantalla que todavia no tienen precios.
            'pendientes' => FleteZona::doesntHave('precios')->orderBy('nombre')->get(['id', 'nombre']),
            'zonas' => $zonas,
            'cotizacion' => is_numeric($this->dolar) ? (float) $this->dolar : 0.0,
        ]);
    }
}
