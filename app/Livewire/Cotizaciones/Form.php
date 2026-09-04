<?php

namespace App\Livewire\Cotizaciones;

use App\Models\Ajuste;
use App\Models\Contacto;
use App\Models\ContactoDireccion;
use App\Models\ContactoProducto;
use App\Models\FleteTramo;
use App\Models\FleteZona;
use App\Models\Insumo;
use App\Models\InsumoItem;
use App\Models\InsumoPrecio;
use App\Models\Parametro;
use App\Models\Proveedor;
use Illuminate\Support\Collection;
use App\Models\Vendedor;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Alta de cotizaciones. Todavia no persiste: falta definir las formulas de los
 * campos calculados y los catalogos reales de cada select.
 */
#[Layout('components.layouts.panel')]
#[Title('Nueva Cotización')]
class Form extends Component
{
    use WithFileUploads;

    /**
     * Cada tipo despliega su propio bloque de campos y destraba las solapas.
     */
    public const TIPOS_PRODUCTO = [
        'bobinas' => 'Bobinas',
        'confeccion-dpk' => 'Confección DPK',
        'confeccion-pouch' => 'Confección Pouch',
        'confeccion-4-costuras' => 'Confección 4 Costuras',
    ];

    /**
     * Solapas del detalle. Todas menos la primera esperan al tipo de producto.
     */
    public const SOLAPAS = [
        'datos' => 'Datos a completar',
        'costos' => 'Costos',
        'cotizacion' => 'Cotización',
        'orden-de-compra' => 'Orden de Compra',
        'entrega' => 'Entrega',
    ];

    /**
     * Opciones de los selects. Son las de la maqueta: cuando cada una tenga su
     * tabla o su lista definitiva, salen de ahi.
     */
    public const OPCIONES = [
        'si_no' => ['Si', 'No'],
        'categorias' => ['A', 'B', 'C', 'OTRO'],
        'laminaciones' => ['Simple', 'Bi.', 'Tri.'],
        'canales' => ['Whats app'],
        'productos' => ['Flowpack bilaminado impreso Carrefour Caseras x 700g'],
        'dias_ff' => ['30'],
    ];

    /**
     * Grupos de Configuracion > Ajustes que se pueden ampliar desde la misma
     * cotizacion, y el campo de bobinas que completa cada uno.
     */
    private const AJUSTES_AL_VUELO = [
        'mangas' => 'desarrollo',
        'bujes' => 'buje',
    ];

    /** Cantidad de columnas material / proveedor del bloque de bobinas. */
    private const MATERIALES = 3;

    public string $solapa = 'datos';

    public string $numero = '';

    public ?int $cliente_id = null;

    public string $categoria = '';

    public string $ajuste_categoria = '0.00';

    public ?int $vendedor_id = null;

    public string $ajuste_vendedor = '0.00';

    public string $fecha = '';

    public string $referencia = '';

    public string $tipo_producto = '';

    /**
     * Campos propios de Bobinas. Los que la maqueta muestra en gris son
     * calculados: quedan de solo lectura hasta que definamos la formula.
     */
    public array $bobinas = [];

    /** Cada entrega es una fila de "Forma de entrega". */
    public array $entregas = [];

    /** Las tres condiciones de pago de la maqueta. */
    public array $pagos = [];

    /**
     * Textos del tab de Cotizacion: se arman con los datos cargados y el
     * usuario los puede retocar antes de mandar la cotizacion.
     */
    public array $cotizacion = [];

    /** Datos del tab de Orden de Compra. */
    public array $oc = [];

    /** Archivo de la OC que adjunta el vendedor. */
    public $archivo_oc = null;

    /** Alta al vuelo: 'mangas' (global) o 'producto' (del cliente elegido). */
    public ?string $creando = null;

    public string $nuevoValor = '';

    /** Edicion en linea del valor que se le suma al ancho refilado. */
    public bool $editandoExtra = false;

    public string $nuevoExtra = '';

    public function mount(): void
    {
        $this->numero = $this->siguienteNumero();
        $this->fecha = now()->format('Y-m-d');

        $this->bobinas = $this->bobinasVacias();
        $this->cotizacion = $this->cotizacionVacia();
        $this->oc = ['canal' => '', 'fecha_recibo' => '', 'quien' => '', 'numero' => ''];
        $this->entregas = [$this->entregaVacia(), $this->entregaVacia()];
        $this->pagos = [
            ['valor_kgrs' => '', 'dias_ff' => '', 'ac' => '', 'financiacion' => '', 'costo_total' => ''],
            ['valor_kgrs' => '', 'dias_ff' => '', 'ac' => '', 'financiacion' => '', 'costo_total' => ''],
            ['valor_kgrs' => '', 'dias_ff' => '', 'ac' => '', 'financiacion' => '', 'costo_total' => ''],
        ];
    }

    /**
     * El producto y sus campos dependen del cliente: sin cliente no se avanza.
     */
    #[Computed]
    public function sinCliente(): bool
    {
        return $this->cliente_id === null;
    }

    /**
     * Sin cliente ni tipo de producto no hay nada que costear ni cotizar.
     */
    #[Computed]
    public function bloqueado(): bool
    {
        return $this->sinCliente || $this->tipo_producto === '';
    }

    /**
     * Cambiar de cliente cambia la lista de productos, asi que se empieza de nuevo.
     */
    public function updatedClienteId(): void
    {
        $this->tipo_producto = '';
        $this->bobinas['producto_id'] = null;
        $this->solapa = 'datos';
        $this->cancelarAlta();
    }

    /**
     * Alta al vuelo de una manga o de un producto del cliente.
     */
    public function abrirAlta(string $catalogo): void
    {
        if ($catalogo !== 'producto' && ! isset(self::AJUSTES_AL_VUELO[$catalogo])) {
            return;
        }

        $this->creando = $catalogo;
        $this->nuevoValor = '';
        $this->resetValidation('nuevoValor');
    }

    public function cancelarAlta(): void
    {
        $this->creando = null;
        $this->nuevoValor = '';
        $this->resetValidation('nuevoValor');
    }

    public function guardarAlta(): void
    {
        if ($this->creando === 'producto') {
            $this->guardarProducto();

            return;
        }

        if (isset(self::AJUSTES_AL_VUELO[$this->creando])) {
            $this->guardarAjuste($this->creando);
        }
    }

    /**
     * Queda disponible para todo el sistema, igual que los cargados desde Ajustes.
     *
     * Si el valor ya existe no es un error: se elige el que ya está.
     */
    private function guardarAjuste(string $grupo): void
    {
        $this->validate(
            ['nuevoValor' => ['required', 'numeric', 'min:0']],
            [],
            ['nuevoValor' => 'valor'],
        );

        $valor = (float) $this->nuevoValor;

        Ajuste::firstOrCreate(['grupo' => $grupo, 'valor' => $valor]);

        // Mismo formato que las opciones del select para que quede seleccionado.
        $this->bobinas[self::AJUSTES_AL_VUELO[$grupo]] = (string) $valor;

        $this->cancelarAlta();
    }

    /**
     * El producto queda guardado solo para el cliente elegido.
     *
     * Si ya lo tenía cargado no es un error: se elige el que ya está.
     */
    private function guardarProducto(): void
    {
        abort_if($this->sinCliente, 403);

        $this->validate(
            ['nuevoValor' => ['required', 'string', 'max:255']],
            [],
            ['nuevoValor' => 'producto'],
        );

        $producto = ContactoProducto::firstOrCreate([
            'contacto_id' => $this->cliente_id,
            'nombre' => trim($this->nuevoValor),
        ]);

        $this->bobinas['producto_id'] = $producto->id;

        $this->cancelarAlta();
    }

    /**
     * Los productos no tienen ABM propio: se borran desde la misma cotizacion.
     */
    public function eliminarProducto(): void
    {
        if ($this->sinCliente || ! $this->bobinas['producto_id']) {
            return;
        }

        ContactoProducto::where('contacto_id', $this->cliente_id)
            ->whereKey($this->bobinas['producto_id'])
            ->delete();

        $this->bobinas['producto_id'] = null;
    }

    /**
     * Direcciones que el cliente tiene agendadas, con su zona de flete.
     */
    #[Computed]
    public function direccionesCliente(): Collection
    {
        return $this->sinCliente
            ? collect()
            : ContactoDireccion::where('contacto_id', $this->cliente_id)
                ->orderBy('direccion')
                ->get(['id', 'flete_zona_id', 'direccion']);
    }

    /**
     * Solo los fletes de las zonas donde el cliente tiene direcciones.
     */
    #[Computed]
    public function zonasCliente(): Collection
    {
        $zonas = $this->direccionesCliente->pluck('flete_zona_id')->filter()->unique();

        return $zonas->isEmpty()
            ? collect()
            : FleteZona::whereIn('id', $zonas)->orderBy('nombre')->pluck('nombre', 'id');
    }

    /**
     * A una zona solo le corresponden sus propias direcciones.
     */
    public function direccionesDeZona(int|string|null $zonaId): Collection
    {
        return $zonaId
            ? $this->direccionesCliente->where('flete_zona_id', (int) $zonaId)->pluck('direccion', 'id')
            : collect();
    }

    /**
     * El ancho refilado y el de lamina se calculan solos.
     */
    public function updatedBobinas(mixed $valor, string $clave): void
    {
        if (in_array($clave, ['ancho', 'modulos_ancho'], true)) {
            $this->recalcularAnchos();
        }

        // Cada material tiene sus proveedores: al cambiarlo se propone el elegido.
        if (preg_match('/^materiales\.(\d+)\.material_id$/', $clave, $partes)) {
            $indice = (int) $partes[1];
            $elegido = InsumoItem::whereKey($this->bobinas['materiales'][$indice]['material_id'] ?: 0)
                ->value('proveedor_elegido_id');

            $this->bobinas['materiales'][$indice]['proveedor_id'] = (string) ($elegido ?? '');
        }
    }

    /**
     * Materiales cargados en Configuración > Insumos, ordenados por familia.
     */
    #[Computed]
    public function materiales(): Collection
    {
        return InsumoItem::with('familia')
            ->whereHas('familia.insumo', fn ($consulta) => $consulta->where('nombre', Insumo::MATERIALES))
            ->get()
            ->sortBy(fn (InsumoItem $item) => $item->familia->nombre.' '.$item->nombre)
            ->pluck('nombre', 'id');
    }

    /**
     * Por que el select de proveedor esta vacio, si lo esta.
     */
    public function ayudaProveedor(int|string|null $itemId): ?string
    {
        if (! $itemId) {
            return 'Elegí primero el material';
        }

        return $this->proveedoresDeMaterial($itemId)->isEmpty()
            ? 'Este material no tiene proveedores cargados en Insumos'
            : null;
    }

    /**
     * Solo los proveedores que tienen cargado ese material.
     */
    public function proveedoresDeMaterial(int|string|null $itemId): Collection
    {
        if (! $itemId) {
            return collect();
        }

        return Proveedor::whereIn('id', InsumoPrecio::where('insumo_item_id', $itemId)->pluck('proveedor_id'))
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    /**
     * Sin impresion no hay datos tecnicos que completar.
     */
    #[Computed]
    public function sinImpresion(): bool
    {
        return ($this->bobinas['impresion'] ?? '') !== 'Si';
    }

    /**
     * Ancho refilado = ancho x modulos ancho. Ancho lamina = refilado + el extra.
     */
    private function recalcularAnchos(): void
    {
        $ancho = (float) ($this->bobinas['ancho'] ?: 0);
        $modulos = (float) ($this->bobinas['modulos_ancho'] ?: 0);

        if ($ancho <= 0 || $modulos <= 0) {
            $this->bobinas['ancho_refilado'] = '';
            $this->bobinas['ancho_lamina'] = '';

            return;
        }

        $refilado = $ancho * $modulos;

        $this->bobinas['ancho_refilado'] = $this->sinCerosDeMas($refilado);
        $this->bobinas['ancho_lamina'] = $this->sinCerosDeMas($refilado + $this->anchoLaminaExtra);
    }

    private function sinCerosDeMas(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }

    /**
     * Cuanto se le suma al ancho refilado para llegar al de lamina.
     */
    #[Computed]
    public function anchoLaminaExtra(): float
    {
        return Parametro::valor(Parametro::ANCHO_LAMINA_EXTRA);
    }

    public function abrirExtra(): void
    {
        $this->editandoExtra = true;
        $this->nuevoExtra = $this->sinCerosDeMas($this->anchoLaminaExtra);
        $this->resetValidation('nuevoExtra');
    }

    public function cancelarExtra(): void
    {
        $this->editandoExtra = false;
        $this->nuevoExtra = '';
        $this->resetValidation('nuevoExtra');
    }

    /**
     * El valor es del sistema: cambia el ancho de lamina de todas las cotizaciones.
     */
    public function guardarExtra(): void
    {
        $this->validate(
            ['nuevoExtra' => ['required', 'numeric', 'min:0']],
            [],
            ['nuevoExtra' => 'valor'],
        );

        Parametro::guardar(Parametro::ANCHO_LAMINA_EXTRA, (float) $this->nuevoExtra);

        unset($this->anchoLaminaExtra);

        $this->recalcularAnchos();
        $this->cancelarExtra();
    }

    /**
     * Cambiar la zona de una entrega invalida la direccion que tenia elegida.
     */
    public function updatedEntregas(mixed $valor, string $clave): void
    {
        if (! str_ends_with($clave, '.flete_zona_id')) {
            return;
        }

        $indice = (int) strtok($clave, '.');

        $this->entregas[$indice]['direccion_id'] = '';
    }

    /**
     * Sin Cantidad (mts) no hay nada que repartir entre las entregas.
     */
    #[Computed]
    public function sinCantidad(): bool
    {
        return trim($this->bobinas['cantidad'] ?? '') === '';
    }

    /**
     * Lo que suman las entregas cargadas.
     */
    #[Computed]
    public function cantidadRepartida(): float
    {
        return collect($this->entregas)->sum(fn (array $entrega) => (float) ($entrega['cantidad'] ?? 0));
    }

    /**
     * Lo que falta repartir. Negativo si se pasaron de la cantidad del producto.
     */
    #[Computed]
    public function cantidadPendiente(): float
    {
        return (float) ($this->bobinas['cantidad'] ?? 0) - $this->cantidadRepartida;
    }

    /**
     * El numero que va a la derecha de Colores: (diseños + cambios) x colores.
     */
    #[Computed]
    public function coloresTotal(): string
    {
        $disenos = $this->bobinas['disenos'] ?? '';
        $cambios = $this->bobinas['cambios'] ?? '';
        $colores = $this->bobinas['colores'] ?? '';

        // Sin nada cargado no hay resultado que mostrar.
        if (trim($disenos) === '' && trim($cambios) === '' && trim($colores) === '') {
            return '';
        }

        return (string) (((float) $disenos + (float) $cambios) * (float) $colores);
    }

    /**
     * El boton principal de la cabecera cambia segun la solapa.
     */
    #[Computed]
    public function accionPrincipal(): string
    {
        return in_array($this->solapa, ['orden-de-compra', 'entrega'], true) ? 'Enviar pedido' : 'Descargar PDF';
    }

    public function verSolapa(string $solapa): void
    {
        if (! isset(self::SOLAPAS[$solapa]) || ($this->bloqueado && $solapa !== 'datos')) {
            return;
        }

        $this->solapa = $solapa;
    }

    /**
     * Al cambiar el tipo se vuelve a Datos, y si se limpia se traban las solapas.
     */
    public function updatedTipoProducto(): void
    {
        $this->solapa = 'datos';
    }

    public function updatedAjusteCategoria(): void
    {
        $this->ajuste_categoria = $this->conDosDecimales($this->ajuste_categoria);
    }

    public function updatedAjusteVendedor(): void
    {
        $this->ajuste_vendedor = $this->conDosDecimales($this->ajuste_vendedor);
    }

    private function conDosDecimales(string $valor): string
    {
        return is_numeric($valor) ? number_format((float) $valor, 2, '.', '') : '0.00';
    }

    public function agregarEntrega(): void
    {
        $this->entregas[] = $this->entregaVacia();
    }

    public function quitarEntrega(int $indice): void
    {
        // Siempre queda al menos una entrega.
        if (count($this->entregas) > 1) {
            unset($this->entregas[$indice]);

            $this->entregas = array_values($this->entregas);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function bobinasVacias(): array
    {
        return [
            'ancho' => '',
            'paso' => '',
            'modulos_desarrollo' => '',
            'producto_id' => null,
            'modulos_ancho' => '',
            'desarrollo' => '',
            'materiales' => array_fill(0, self::MATERIALES, ['material_id' => '', 'mic' => '', 'proveedor_id' => '']),
            'cantidad' => '',
            'peso' => '',
            'peso_neto' => '',
            'por_bobina' => '',
            'buje' => '',
            'impresion' => '',
            'reprint' => '',
            'contiene_liquido' => '',
            'solvente' => '',
            'laminacion' => '',
            'disenos' => '',
            'cambios' => '',
            'colores' => '',
            'porcentaje_impreso' => '',
            'blanco' => '',
            'ancho_refilado' => '',
            'ancho_lamina' => '',
            'impresion_scrap' => '',
            'impresion_scrap_usd' => '',
            'laminacion_scrap' => '',
            'laminacion_scrap_usd' => '',
            'bilaminacion_scrap' => '',
            'bilaminacion_scrap_usd' => '',
            'mangas' => '',
        ];
    }

    /**
     * Cada entrega guarda tambien como se describe en el tab de Cotizacion.
     *
     * @return array<string, string>
     */
    private function entregaVacia(): array
    {
        return [
            'flete_zona_id' => '',
            'direccion_id' => '',
            'cantidad' => '',
            'flete_tramo_id' => '',
            'fecha_entrega' => '',
            'texto_lugar' => '',
            'texto_cantidad' => '',
            'texto_direccion' => '',
            // Solapa Entrega: lo que efectivamente se entrego.
            'producto' => '',
            'cantidad_a_entregar' => '',
            'cantidad_entregada' => '',
            'fecha_real' => '',
            'zona' => '',
            'direccion_entrega' => '',
            'codigo_postal' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function cotizacionVacia(): array
    {
        return [
            'tipo_producto' => '',
            'producto' => '',
            'materiales' => '',
            'anchos' => '',
            'paso' => '',
            'impresion' => '',
            'laminacion' => '',
            'cantidad' => '',
            'precio' => '',
        ];
    }

    /**
     * Correlativo del año, tomado de la maqueta del listado.
     */
    private function siguienteNumero(): string
    {
        $anio = now()->year;

        $ultimo = collect(Index::MAQUETA)
            ->map(fn (array $cotizacion) => explode('/', $cotizacion['numero']))
            ->filter(fn (array $partes) => (int) ($partes[1] ?? 0) === $anio)
            ->max(fn (array $partes) => (int) $partes[0]);

        return sprintf('%06d/%d', (int) $ultimo + 1, $anio);
    }

    public function render()
    {
        return view('livewire.cotizaciones.form', [
            'clientes' => Contacto::enEstado(Contacto::CLIENTE)
                ->orderBy('razon_social')
                ->get(['id', 'razon_social']),
            'vendedores' => Vendedor::orderBy('nombre')->get(['id', 'nombre']),
            'mangas' => Ajuste::opciones('mangas'),
            'bujes' => Ajuste::opciones('bujes'),
            // Tramos de kg / pallets de Configuración > Flete Insumos.
            'tramos' => FleteTramo::orderBy('kg')->get()->pluck('etiqueta', 'id'),
            // Los productos son propios del cliente elegido.
            'productos' => $this->sinCliente
                ? collect()
                : ContactoProducto::where('contacto_id', $this->cliente_id)->orderBy('nombre')->pluck('nombre', 'id'),
        ])->title('Cotización '.$this->numero);
    }
}
