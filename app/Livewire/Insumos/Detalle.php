<?php

namespace App\Livewire\Insumos;

use App\Models\Insumo;
use App\Models\InsumoFamilia;
use App\Models\InsumoItem;
use App\Models\InsumoPrecio;
use App\Models\Proveedor;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class Detalle extends Component
{
    public Insumo $insumo;

    /**
     * Proveedor abierto en cada familia: es la unica columna con todos los
     * campos a la vista; el resto muestra solo el total.
     *
     * @var array<int, int>
     */
    public array $expandido = [];

    /**
     * Campos editables del proveedor abierto, por item.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $fila = [];

    /**
     * Toneladas desde las que rige el precio por volumen, por familia. Se
     * guarda apenas se cambia.
     *
     * @var array<int, string>
     */
    public array $volumenDesde = [];

    public bool $creandoFamilia = false;

    public string $familiaNombre = '';

    public bool $creandoItem = false;

    public string $itemNombre = '';

    public ?int $itemFamiliaId = null;

    /** Item cuyo nombre se esta editando. */
    public ?int $editandoItem = null;

    public string $itemNombreEditado = '';

    /** Familia a la que se le esta agregando una columna de proveedor. */
    public ?int $agregandoProveedorEn = null;

    public ?int $proveedorId = null;

    public string $nuevoProveedor = '';

    /** Proveedor cuyo nombre se esta editando. */
    public ?int $editandoProveedor = null;

    public string $proveedorNombre = '';

    public function mount(Insumo $insumo): void
    {
        $this->insumo = $insumo;

        foreach ($this->familias() as $familia) {
            $this->volumenDesde[$familia->id] = $this->comoTexto($familia->volumen_desde_tn);

            if ($primero = $familia->proveedores->first()) {
                $this->expandir($familia->id, $primero->id);
            }
        }
    }

    /**
     * Abre un proveedor en la familia y carga sus precios en el formulario.
     */
    public function expandir(int $familiaId, int $proveedorId): void
    {
        $this->expandido[$familiaId] = $proveedorId;
        $this->resetValidation();

        $familia = $this->familias()->firstWhere('id', $familiaId);

        foreach ($familia?->items ?? [] as $item) {
            $precio = $item->preciosPorProveedor()[$proveedorId] ?? null;

            $this->fila[$this->clave($item->id, $proveedorId)] = [
                'costo' => $this->comoTexto($precio?->costo),
                'peso_especifico' => $this->comoTexto($item->peso_especifico),
                'costo_volumen' => $this->comoTexto($precio?->costo_volumen),
                'flete' => (int) (bool) $precio?->flete,
                'donde' => (string) $precio?->donde,
                'costo_flete' => $this->comoTexto($precio?->costo_flete),
            ];
        }
    }

    /**
     * Umbral del precio por volumen: si lo que se escribe no sirve, vuelve al guardado.
     */
    public function updatedVolumenDesde(mixed $valor, string $familiaId): void
    {
        $familia = $this->insumo->familias()->findOrFail((int) $familiaId);

        $this->resetErrorBag('volumenDesde.'.$familiaId);

        if (! is_numeric($valor) || (float) $valor <= 0 || (float) $valor > 99999) {
            $this->addError('volumenDesde.'.$familiaId, 'Las toneladas tienen que ser un número mayor a cero.');
            $this->volumenDesde[$familia->id] = $this->comoTexto($familia->volumen_desde_tn);

            return;
        }

        $familia->update(['volumen_desde_tn' => (float) $valor]);

        $this->volumenDesde[$familia->id] = $this->comoTexto($familia->fresh()->volumen_desde_tn);
    }

    public function colapsar(int $familiaId): void
    {
        unset($this->expandido[$familiaId]);
    }

    public function guardarPrecios(int $familiaId): void
    {
        $proveedorId = $this->expandido[$familiaId] ?? null;

        if (! $proveedorId) {
            return;
        }

        $familia = $this->familias()->firstWhere('id', $familiaId);

        abort_unless($familia !== null, 404);

        $this->validate($this->reglasPrecios($familia, $proveedorId));

        foreach ($familia->items as $item) {
            $datos = $this->fila[$this->clave($item->id, $proveedorId)] ?? null;

            if ($datos === null) {
                continue;
            }

            $flete = (bool) $datos['flete'];

            // Dato del material: vale para todos sus proveedores.
            $item->update(['peso_especifico' => $datos['peso_especifico'] !== '' ? $datos['peso_especifico'] : null]);

            // Sin costo la celda queda vacia: no se guarda un precio en blanco.
            if ($datos['costo'] === '' || $datos['costo'] === null) {
                $item->precios()->where('proveedor_id', $proveedorId)->delete();

                continue;
            }

            InsumoPrecio::updateOrCreate(
                ['insumo_item_id' => $item->id, 'proveedor_id' => $proveedorId],
                [
                    'costo' => $datos['costo'],
                    'costo_volumen' => $datos['costo_volumen'] !== '' ? $datos['costo_volumen'] : null,
                    'flete' => $flete,
                    'donde' => $flete && $datos['donde'] !== '' ? $datos['donde'] : null,
                    'costo_flete' => $flete && $datos['costo_flete'] !== '' ? $datos['costo_flete'] : null,
                ],
            );
        }

        session()->flash('status', 'Precios guardados.');
    }

    /**
     * Proveedor con el que se trabaja ese item: es el total que queda resaltado.
     */
    public function elegir(int $itemId, int $proveedorId): void
    {
        $item = InsumoItem::findOrFail($itemId);

        $item->update([
            'proveedor_elegido_id' => $item->proveedor_elegido_id === $proveedorId ? null : $proveedorId,
        ]);
    }

    public function abrirFamilia(): void
    {
        $this->creandoFamilia = true;
        $this->familiaNombre = '';
        $this->resetValidation('familiaNombre');
    }

    public function guardarFamilia(): void
    {
        $this->validate([
            'familiaNombre' => [
                'required', 'string', 'max:255',
                Rule::unique('insumo_familias', 'nombre')->where('insumo_id', $this->insumo->id),
            ],
        ], attributes: ['familiaNombre' => 'nombre']);

        $familia = $this->insumo->familias()->create(['nombre' => trim($this->familiaNombre)]);

        $this->volumenDesde[$familia->id] = $this->comoTexto($familia->volumen_desde_tn);
        $this->creandoFamilia = false;
        $this->familiaNombre = '';
    }

    public function eliminarFamilia(int $familiaId): void
    {
        InsumoFamilia::where('insumo_id', $this->insumo->id)->findOrFail($familiaId)->delete();

        unset($this->expandido[$familiaId]);

        session()->flash('status', 'Familia eliminada.');
    }

    public function abrirItem(?int $familiaId = null): void
    {
        $this->creandoItem = true;
        $this->itemNombre = '';
        $this->itemFamiliaId = $familiaId ?? $this->familias()->first()?->id;
        $this->resetValidation();
    }

    public function guardarItem(): void
    {
        $this->validate([
            'itemFamiliaId' => ['required', Rule::in($this->familias()->pluck('id')->all())],
            'itemNombre' => [
                'required', 'string', 'max:255',
                Rule::unique('insumo_items', 'nombre')->where('insumo_familia_id', $this->itemFamiliaId),
            ],
        ], attributes: ['itemNombre' => 'nombre', 'itemFamiliaId' => 'familia']);

        InsumoItem::create([
            'insumo_familia_id' => $this->itemFamiliaId,
            'nombre' => trim($this->itemNombre),
        ]);

        $this->creandoItem = false;
        $this->itemNombre = '';

        if ($proveedorId = $this->expandido[$this->itemFamiliaId] ?? null) {
            $this->expandir($this->itemFamiliaId, $proveedorId);
        }
    }

    public function editarItem(int $itemId): void
    {
        $this->editandoItem = $itemId;
        $this->itemNombreEditado = InsumoItem::findOrFail($itemId)->nombre;
        $this->resetValidation('itemNombreEditado');
    }

    public function guardarItemEditado(): void
    {
        $item = InsumoItem::findOrFail($this->editandoItem);

        $this->validate([
            'itemNombreEditado' => [
                'required', 'string', 'max:255',
                Rule::unique('insumo_items', 'nombre')
                    ->where('insumo_familia_id', $item->insumo_familia_id)
                    ->ignore($item->id),
            ],
        ], attributes: ['itemNombreEditado' => 'nombre']);

        $item->update(['nombre' => trim($this->itemNombreEditado)]);

        $this->editandoItem = null;
    }

    public function eliminarItem(int $itemId): void
    {
        InsumoItem::findOrFail($itemId)->delete();

        $this->editandoItem = null;

        session()->flash('status', 'Item eliminado.');
    }

    public function abrirProveedor(int $familiaId): void
    {
        $this->agregandoProveedorEn = $familiaId;
        $this->proveedorId = null;
        $this->nuevoProveedor = '';
        $this->resetValidation();
    }

    public function cancelarProveedor(): void
    {
        $this->agregandoProveedorEn = null;
        $this->proveedorId = null;
        $this->nuevoProveedor = '';
        $this->resetValidation();
    }

    /**
     * Se elige del catalogo global o se crea al vuelo; si ya existe se usa ese.
     */
    public function guardarProveedor(): void
    {
        $familia = $this->familias()->firstWhere('id', $this->agregandoProveedorEn);

        abort_unless($familia !== null, 404);

        if (trim($this->nuevoProveedor) !== '') {
            $proveedor = Proveedor::firstOrCreate(['nombre' => trim($this->nuevoProveedor)]);
        } else {
            $this->validate(
                ['proveedorId' => ['required', 'exists:proveedores,id']],
                attributes: ['proveedorId' => 'proveedor']
            );

            $proveedor = Proveedor::findOrFail($this->proveedorId);
        }

        $familia->proveedores()->syncWithoutDetaching($proveedor->id);

        $this->cancelarProveedor();
        $this->expandir($familia->id, $proveedor->id);
    }

    /** Saca la columna de la familia; el proveedor sigue en el catalogo. */
    public function quitarProveedor(int $familiaId, int $proveedorId): void
    {
        $familia = $this->familias()->firstWhere('id', $familiaId);

        abort_unless($familia !== null, 404);

        $familia->proveedores()->detach($proveedorId);

        InsumoPrecio::where('proveedor_id', $proveedorId)
            ->whereIn('insumo_item_id', $familia->items->pluck('id'))
            ->delete();

        InsumoItem::where('insumo_familia_id', $familiaId)
            ->where('proveedor_elegido_id', $proveedorId)
            ->update(['proveedor_elegido_id' => null]);

        if (($this->expandido[$familiaId] ?? null) === $proveedorId) {
            unset($this->expandido[$familiaId]);
        }

        session()->flash('status', 'Proveedor quitado de la familia.');
    }

    public function editarProveedor(int $proveedorId): void
    {
        $this->editandoProveedor = $proveedorId;
        $this->proveedorNombre = Proveedor::findOrFail($proveedorId)->nombre;
        $this->resetValidation('proveedorNombre');
    }

    public function guardarProveedorEditado(): void
    {
        $this->validate([
            'proveedorNombre' => [
                'required', 'string', 'max:255',
                Rule::unique('proveedores', 'nombre')->ignore($this->editandoProveedor),
            ],
        ], attributes: ['proveedorNombre' => 'nombre']);

        Proveedor::findOrFail($this->editandoProveedor)->update(['nombre' => trim($this->proveedorNombre)]);

        $this->editandoProveedor = null;
    }

    public function cancelarEdiciones(): void
    {
        $this->editandoItem = null;
        $this->editandoProveedor = null;
        $this->creandoItem = false;
        $this->creandoFamilia = false;
        $this->cancelarProveedor();
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    private function reglasPrecios(InsumoFamilia $familia, int $proveedorId): array
    {
        $reglas = [];

        foreach ($familia->items as $item) {
            $clave = 'fila.'.$this->clave($item->id, $proveedorId);

            $reglas[$clave.'.costo'] = ['nullable', 'numeric', 'min:0'];
            $reglas[$clave.'.peso_especifico'] = ['nullable', 'numeric', 'min:0'];
            $reglas[$clave.'.costo_volumen'] = ['nullable', 'numeric', 'min:0'];
            $reglas[$clave.'.costo_flete'] = ['nullable', 'numeric', 'min:0'];
            $reglas[$clave.'.donde'] = ['nullable', 'string', 'max:255'];
        }

        return $reglas;
    }

    private function clave(int $itemId, int $proveedorId): string
    {
        return $itemId.'_'.$proveedorId;
    }

    private function comoTexto(float|string|null $valor): string
    {
        return $valor === null || $valor === '' ? '' : (string) (float) $valor;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, InsumoFamilia>
     */
    private function familias()
    {
        return $this->insumo->familias()->with(['proveedores', 'items.precios'])->get();
    }

    public function render()
    {
        return view('livewire.insumos.detalle', [
            'familias' => $this->familias(),
            'catalogo' => Proveedor::orderBy('nombre')->get(),
        ])->title($this->insumo->nombre);
    }
}
