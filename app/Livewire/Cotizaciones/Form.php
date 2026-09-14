<?php

namespace App\Livewire\Cotizaciones;

use App\Livewire\Fletes\Index as Fletes;
use App\Models\Ajuste;
use App\Models\Contacto;
use App\Models\Cotizacion;
use App\Models\ContactoDireccion;
use App\Models\ContactoProducto;
use App\Models\FletePrecio;
use App\Models\FleteTramo;
use App\Models\FleteZona;
use App\Models\Insumo;
use App\Models\InsumoItem;
use App\Models\InsumoPrecio;
use App\Models\Operativo;
use App\Models\Parametro;
use App\Models\Proveedor;
use App\Models\VariableCosto;
use App\Models\Vendedor;
use App\Support\Numero;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Alta y edicion de cotizaciones. La cabecera va en columnas y el detalle del
 * tipo de producto en JSON; los calculos se rehacen al abrirla.
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
    /** Formas de entrega: con envio hay flete; con retiro en sucursal, no. */
    public const ENVIO = 'Envío';

    public const RETIRO = 'Retiro en sucursal';

    public const OPCIONES = [
        'si_no' => ['Si', 'No'],
        'categorias' => ['A', 'B', 'C', 'OTRO'],
        'laminaciones' => ['Simple', 'Bi.', 'Tri.'],
        'formas_entrega' => [self::ENVIO, self::RETIRO],
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

    /** Cantidad maxima de materiales del bloque de bobinas. */
    private const MATERIALES = 3;

    /** Cuantas laminas lleva el producto: define cuantos materiales se cargan. */
    public const LAMINADOS = [1 => 'Unilaminado', 2 => 'Bilaminado', 3 => 'Trilaminado'];

    /**
     * Insumos que usan los costos de impresion y laminacion, por nombre de
     * insumo e item en Configuración > Insumos.
     */
    public const INSUMOS = [
        'tela' => ['Tela', 'Tela Doble Fax'],
        'tintas' => ['Tintas', 'Costo tintas'],
        'tinta_blanca' => ['Tintas', 'Tinta blanca'],
        'barniz' => ['Tintas', 'Barniz'],
        'dy_alargue' => ['Diluyentes', 'DY Alargue'],
        'dy_limpieza' => ['Diluyentes', 'DY Limpieza'],
        'sl_compuesto' => ['Adhesivos', 'Compuesto solvent less'],
        'sl_catalizador' => ['Adhesivos', 'Catalizador solvent less'],
        'solvente_compuesto' => ['Adhesivos', 'Compuesto solvente'],
    ];

    /** La cotizacion guardada, cuando se esta editando una. */
    public ?Cotizacion $guardada = null;

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

    /** Campo de bobinas que completa el alta al vuelo. */
    public ?string $campoAlta = null;

    /** Entrega a la que se le esta agregando una direccion del cliente. */
    public ?int $agregandoDireccionEn = null;

    /**
     * Direccion nueva del cliente: con una zona del catalogo de flete o una
     * zona nueva, que queda pendiente de precios en Flete Insumos.
     *
     * @var array{flete_zona_id: string, nueva_zona: string, direccion: string, codigo_postal: string, observaciones: string}
     */
    public array $nuevaDireccion = self::DIRECCION_VACIA;

    private const DIRECCION_VACIA = ['flete_zona_id' => '', 'nueva_zona' => '', 'direccion' => '', 'codigo_postal' => '', 'observaciones' => ''];

    /** Edicion en linea del valor que se le suma al ancho refilado. */
    public bool $editandoExtra = false;

    public string $nuevoExtra = '';

    public function mount(?Cotizacion $guardada = null): void
    {
        $this->numero = Cotizacion::siguienteNumero();
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

        // El parametro se llama "guardada" porque $cotizacion ya es el bloque de textos.
        if ($guardada?->exists) {
            $this->cargar($guardada);
        }
    }

    /**
     * Vuelca una cotizacion guardada en el formulario. Los campos que se
     * agregaron despues de guardarla toman el valor vacio.
     */
    private function cargar(Cotizacion $cotizacion): void
    {
        $this->guardada = $cotizacion;
        $this->numero = $cotizacion->numero;
        $this->fecha = $cotizacion->fecha->format('Y-m-d');
        $this->cliente_id = $cotizacion->contacto_id;
        $this->vendedor_id = $cotizacion->vendedor_id;
        $this->categoria = (string) $cotizacion->categoria;
        $this->ajuste_categoria = number_format((float) $cotizacion->ajuste_categoria, 2, '.', '');
        $this->ajuste_vendedor = number_format((float) $cotizacion->ajuste_vendedor, 2, '.', '');
        $this->referencia = (string) $cotizacion->referencia;
        $this->tipo_producto = (string) $cotizacion->tipo_producto;

        $datos = $cotizacion->datos ?? [];

        $this->bobinas = array_replace($this->bobinas, $datos['bobinas'] ?? []);
        $this->bobinas['materiales'] = array_replace($this->bobinasVacias()['materiales'], $datos['bobinas']['materiales'] ?? []);
        $this->cotizacion = array_replace($this->cotizacion, $datos['cotizacion'] ?? []);
        $this->oc = array_replace($this->oc, $datos['oc'] ?? []);
        $this->pagos = array_replace($this->pagos, $datos['pagos'] ?? []);

        if (! empty($datos['entregas'])) {
            $this->entregas = array_map(fn (array $entrega) => array_replace($this->entregaVacia(), $entrega), $datos['entregas']);
        }

        // Los calculados se rehacen con los datos y parametros de hoy.
        $this->recalcularAnchos();
        $this->recalcularPeso();
        $this->recalcularScrap();
    }

    /**
     * Guarda la cotizacion (alta o edicion). Se puede guardar a medio
     * completar: solo exige cliente, vendedor y fecha.
     */
    public function guardar(): void
    {
        $esNueva = $this->guardada === null;

        $this->persistir();

        session()->flash('status', $esNueva ? 'Cotización '.$this->numero.' creada.' : 'Cotización guardada.');

        if ($esNueva) {
            $this->redirectRoute('cotizaciones.edit', $this->guardada, navigate: true);
        }
    }

    /**
     * Aprobar: guarda, marca la cotizacion como aprobada con fecha y hora, y
     * lo anota en la actividad del cliente. El dashboard cuenta las toneladas
     * aprobadas por dia con esa fecha.
     */
    public function aprobar(): void
    {
        $esNueva = $this->guardada === null;

        $this->persistir();

        if ($this->guardada->estado !== Cotizacion::APROBADA) {
            $this->guardada->update(['estado' => Cotizacion::APROBADA, 'aprobada_en' => now()]);

            $this->guardada->cliente?->actividades()->create([
                'fecha' => now(),
                'descripcion' => 'Se aprobó la cotización '.$this->numero,
                'autor' => $this->guardada->vendedor?->nombre ?? auth()->user()->name,
            ]);
        }

        session()->flash('status', 'Cotización '.$this->numero.' aprobada.');

        if ($esNueva) {
            $this->redirectRoute('cotizaciones.edit', $this->guardada, navigate: true);
        }
    }

    /**
     * Valida lo minimo, crea o actualiza, y deja la actividad del alta.
     */
    private function persistir(): void
    {
        $this->validate([
            'cliente_id' => ['required', 'exists:contactos,id'],
            'vendedor_id' => ['required', 'exists:vendedores,id'],
            'fecha' => ['required', 'date'],
            'tipo_producto' => ['nullable', 'string'],
        ], attributes: ['cliente_id' => 'cliente', 'vendedor_id' => 'vendedor', 'fecha' => 'fecha']);

        // Los textos y las condiciones de pago viajan ya completados.
        $this->completarTextos();
        $this->completarPagos();

        $datos = [
            'fecha' => $this->fecha,
            'contacto_id' => $this->cliente_id,
            'vendedor_id' => $this->vendedor_id,
            'categoria' => $this->categoria ?: null,
            'ajuste_categoria' => (float) $this->ajuste_categoria,
            'ajuste_vendedor' => (float) $this->ajuste_vendedor,
            'referencia' => $this->referencia ?: null,
            'tipo_producto' => $this->tipo_producto ?: null,
            'datos' => [
                'bobinas' => $this->bobinas,
                'entregas' => $this->entregas,
                'pagos' => $this->pagos,
                'cotizacion' => $this->cotizacion,
                'oc' => $this->oc,
            ],
        ];

        if ($this->guardada) {
            $this->guardada->update($datos);

            return;
        }

        // El numero se toma al guardar, por si otro la guardo antes.
        $this->numero = Cotizacion::siguienteNumero();
        $this->guardada = Cotizacion::create($datos + ['numero' => $this->numero]);

        Contacto::find($this->cliente_id)?->actividades()->create([
            'fecha' => now(),
            'descripcion' => 'Se creó la cotización '.$this->numero,
            'autor' => Vendedor::find($this->vendedor_id)?->nombre ?? auth()->user()->name,
        ]);
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
     * La cotizacion es de un vendedor: su comision entra en el costo.
     */
    #[Computed]
    public function sinVendedor(): bool
    {
        return $this->vendedor_id === null;
    }

    /**
     * Que falta elegir antes de poder cargar el producto, en texto.
     */
    #[Computed]
    public function faltaElegir(): ?string
    {
        return match (true) {
            $this->sinCliente && $this->sinVendedor => 'el cliente y el vendedor',
            $this->sinCliente => 'el cliente',
            $this->sinVendedor => 'el vendedor',
            default => null,
        };
    }

    /**
     * Sin cliente, vendedor ni tipo de producto no hay nada que costear ni cotizar.
     */
    #[Computed]
    public function bloqueado(): bool
    {
        return $this->faltaElegir !== null || $this->tipo_producto === '';
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
    public function abrirAlta(string $catalogo, ?string $campo = null): void
    {
        if ($catalogo !== 'producto' && ! isset(self::AJUSTES_AL_VUELO[$catalogo])) {
            return;
        }

        // Un mismo grupo puede alimentar varios campos (mangas: desarrollo y
        // mangas disponibles): el alta completa el que la abrio.
        $campo ??= self::AJUSTES_AL_VUELO[$catalogo] ?? null;

        $this->creando = $catalogo;
        $this->campoAlta = array_key_exists($campo, $this->bobinas) ? $campo : self::AJUSTES_AL_VUELO[$catalogo] ?? null;
        $this->nuevoValor = '';
        $this->resetValidation('nuevoValor');
    }

    public function cancelarAlta(): void
    {
        $this->creando = null;
        $this->campoAlta = null;
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
        $this->bobinas[$this->campoAlta ?? self::AJUSTES_AL_VUELO[$grupo]] = (string) $valor;

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

    public function abrirDireccion(int $indice): void
    {
        if ($this->sinCliente || ! isset($this->entregas[$indice])) {
            return;
        }

        $this->agregandoDireccionEn = $indice;
        $this->nuevaDireccion = self::DIRECCION_VACIA;
        $this->resetValidation('nuevaDireccion.*');
    }

    public function cancelarDireccion(): void
    {
        $this->agregandoDireccionEn = null;
        $this->nuevaDireccion = self::DIRECCION_VACIA;
        $this->resetValidation('nuevaDireccion.*');
    }

    /**
     * La direccion queda agendada en el cliente (igual que desde su ficha) y
     * elegida en la entrega que la pidio. Si ya la tenia, se elige la que esta.
     */
    public function guardarDireccion(): void
    {
        abort_if($this->sinCliente, 403);

        if ($this->agregandoDireccionEn === null || ! isset($this->entregas[$this->agregandoDireccionEn])) {
            return;
        }

        $this->validate(
            [
                'nuevaDireccion.flete_zona_id' => ['required_without:nuevaDireccion.nueva_zona', 'nullable', 'exists:flete_zonas,id'],
                'nuevaDireccion.nueva_zona' => ['required_without:nuevaDireccion.flete_zona_id', 'nullable', 'string', 'max:255'],
                'nuevaDireccion.direccion' => ['required', 'string', 'max:255'],
                'nuevaDireccion.codigo_postal' => ['nullable', 'string', 'max:20'],
                'nuevaDireccion.observaciones' => ['nullable', 'string', 'max:1000'],
            ],
            ['nuevaDireccion.flete_zona_id.required_without' => 'Elegí una zona o escribí una nueva.', 'nuevaDireccion.nueva_zona.required_without' => 'Elegí una zona o escribí una nueva.'],
            ['nuevaDireccion.flete_zona_id' => 'zona', 'nuevaDireccion.nueva_zona' => 'zona nueva', 'nuevaDireccion.direccion' => 'dirección', 'nuevaDireccion.codigo_postal' => 'código postal', 'nuevaDireccion.observaciones' => 'observaciones'],
        );

        // Zona nueva: entra al catalogo de flete sin precios, como desde la ficha del cliente.
        $zona = trim($this->nuevaDireccion['nueva_zona']) !== ''
            ? FleteZona::firstOrCreate(['nombre' => trim($this->nuevaDireccion['nueva_zona'])])
            : FleteZona::findOrFail($this->nuevaDireccion['flete_zona_id']);

        $direccion = ContactoDireccion::firstOrCreate(
            ['contacto_id' => $this->cliente_id, 'direccion' => trim($this->nuevaDireccion['direccion'])],
            [
                'flete_zona_id' => $zona->id,
                'codigo_postal' => $this->nuevaDireccion['codigo_postal'] !== '' ? $this->nuevaDireccion['codigo_postal'] : null,
                'observaciones' => trim($this->nuevaDireccion['observaciones']) !== '' ? trim($this->nuevaDireccion['observaciones']) : null,
            ],
        );

        // Si ya la tenia sin zona, ahora la tiene.
        if ($direccion->flete_zona_id === null) {
            $direccion->update(['flete_zona_id' => $zona->id]);
        }

        unset($this->direccionesCliente, $this->zonasCliente);

        $this->entregas[$this->agregandoDireccionEn]['flete_zona_id'] = (string) $direccion->flete_zona_id;
        $this->entregas[$this->agregandoDireccionEn]['direccion_id'] = (string) $direccion->id;

        $this->cancelarDireccion();
    }

    /**
     * Zona del catalogo que todavia no tiene precios en Flete Insumos: el
     * flete de esa entrega va a salir en blanco hasta que se carguen.
     */
    public function zonaSinPrecios(int|string|null $zonaId): bool
    {
        return (bool) $zonaId && FletePrecio::where('flete_zona_id', $zonaId)->doesntExist();
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
    public function updatedBobinas(mixed $valor, ?string $clave = null): void
    {
        // Livewire manda la clave null cuando se reemplaza el arreglo entero.
        if ($clave === null) {
            return;
        }

        if (in_array($clave, ['ancho', 'modulos_ancho'], true)) {
            $this->recalcularAnchos();
        }

        if ($clave === 'laminado') {
            $this->elegirLaminado((int) $valor);

            return;
        }

        // Con retiro en sucursal no hay flete: se limpian esos campos de cada entrega.
        if ($clave === 'forma_entrega' && $valor === self::RETIRO) {
            foreach ($this->entregas as $indice => $entrega) {
                $this->entregas[$indice]['flete_zona_id'] = '';
                $this->entregas[$indice]['direccion_id'] = '';
                $this->entregas[$indice]['flete_tramo_id'] = '';
            }
        }

        // Cada material tiene sus proveedores: al cambiarlo se propone el elegido.
        if (preg_match('/^materiales\.(\d+)\.material_id$/', $clave, $partes)) {
            $indice = (int) $partes[1];
            $elegido = InsumoItem::whereKey($this->bobinas['materiales'][$indice]['material_id'] ?: 0)
                ->value('proveedor_elegido_id');

            $this->bobinas['materiales'][$indice]['proveedor_id'] = (string) ($elegido ?? '');
        }

        // Peso y scrap comparten datos: se recalculan juntos.
        $disparaCalculo = in_array($clave, ['cantidad', 'disenos', 'cambios', 'impresion_scrap', 'laminacion_scrap', 'bilaminacion_scrap'], true)
            || preg_match('/^materiales\.\d+\.(material_id|mic|proveedor_id)$/', $clave);

        if ($disparaCalculo) {
            $this->recalcularPeso();
            $this->recalcularScrap();
        }
    }

    /**
     * Los materiales elegidos, indexados por id.
     *
     * @return Collection<int, InsumoItem>
     */
    #[Computed]
    public function itemsElegidos(): Collection
    {
        return InsumoItem::with('familia')->findMany(
            collect($this->bobinas['materiales'] ?? [])->pluck('material_id')->filter()->all()
        )->keyBy('id');
    }

    /**
     * Kilos que pesan 1000 metros de cada material, con el ancho refilado y el
     * micraje de esta cotizacion (columna "Kgrs x 1000 Mts" de la planilla).
     *
     * @return array<int, float>
     */
    #[Computed]
    public function kgrsPorMilMetros(): array
    {
        $anchoRefilado = (float) ($this->bobinas['ancho_refilado'] ?: 0);

        if ($anchoRefilado <= 0) {
            return [];
        }

        $kgrs = [];

        foreach ($this->bobinas['materiales'] ?? [] as $indice => $material) {
            $item = $this->itemsElegidos[$material['material_id'] ?? null] ?? null;
            $peso = $item?->kgrsPorMilMetros($anchoRefilado, (float) ($material['mic'] ?: 0));

            if ($peso !== null) {
                $kgrs[$indice] = $peso;
            }
        }

        return $kgrs;
    }

    /**
     * Por que el peso todavia no se puede calcular, si es que no se puede.
     */
    public function ayudaPeso(): ?string
    {
        if (($this->bobinas['peso'] ?? '') !== '') {
            return null;
        }

        $faltan = [];

        $conAncho = (float) ($this->bobinas['ancho'] ?: 0) > 0 && (float) ($this->bobinas['modulos_ancho'] ?: 0) > 0;

        if (! $conAncho) {
            $faltan[] = 'Ancho y Módulos Ancho';
        }

        if ((float) ($this->bobinas['cantidad'] ?: 0) <= 0) {
            $faltan[] = 'Cantidad (mts)';
        }

        $elegidos = collect($this->bobinas['materiales'] ?? [])->filter(fn (array $material) => ! empty($material['material_id']));

        if ($elegidos->isEmpty()) {
            $faltan[] = 'el material';
        } elseif ($conAncho && $this->kgrsPorMilMetros === []) {
            // Sin ancho no hay kilos: ahi lo que falta es el ancho, no esto.
            $faltan[] = 'el mic, o el peso esp. del material en Insumos';
        }

        return $faltan === [] ? null : 'Falta completar: '.implode(', ', $faltan).'.';
    }

    /**
     * Peso (kg) = suma de los kilos por 1000 metros de cada material, por la
     * cantidad en metros dividida 1000.
     */
    private function recalcularPeso(): void
    {
        unset($this->itemsElegidos, $this->kgrsPorMilMetros);

        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $kgrs = array_sum($this->kgrsPorMilMetros);

        $this->bobinas['peso'] = $cantidad > 0 && $kgrs > 0
            ? number_format($kgrs * $cantidad / 1000, 2, '.', '')
            : '';
    }

    /**
     * Cada scrap va atado a un material: impresion al 1, laminacion al 2 y
     * bilaminacion al 3. Como en la planilla:
     *
     *   U$S = peso esp x mic x (scrap cm / 100) x (metros a trabajar / 1000) x costo
     *
     * Los metros a trabajar son la cantidad mas 1500 por cada diseño y cambio
     * para impresion, y la cantidad mas 100 para los otros dos.
     */
    private function recalcularScrap(): void
    {
        unset($this->itemsElegidos);

        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);

        $scraps = [
            'impresion_scrap' => [0, $this->metrosATrabajar(0)],
            'laminacion_scrap' => [1, $this->metrosATrabajar(1)],
            'bilaminacion_scrap' => [2, $this->metrosATrabajar(2)],
        ];

        foreach ($scraps as $campo => [$indice, $metros]) {
            $material = $this->bobinas['materiales'][$indice] ?? [];
            $item = $this->itemsElegidos[$material['material_id'] ?? null] ?? null;
            $scrapCm = (float) ($this->bobinas[$campo] ?: 0);
            $mic = (float) ($material['mic'] ?? 0);
            $costo = $this->costoDelMaterial($item, $material['proveedor_id'] ?? null);

            $listo = $item?->peso_especifico !== null && $cantidad > 0 && $scrapCm > 0 && $mic > 0 && $costo !== null;

            $this->bobinas[$campo.'_usd'] = $listo
                ? number_format((float) $item->peso_especifico * $mic * ($scrapCm / 100) * ($metros / 1000) * $costo, 2, '.', '')
                : '';
        }
    }

    /**
     * Por que el U$S de un scrap todavia no se puede calcular, si es que no.
     */
    public function ayudaScrap(string $campo): ?string
    {
        if (($this->bobinas[$campo.'_usd'] ?? '') !== '') {
            return null;
        }

        $indice = ['impresion_scrap' => 0, 'laminacion_scrap' => 1, 'bilaminacion_scrap' => 2][$campo] ?? 0;
        $material = $this->bobinas['materiales'][$indice] ?? [];
        $etiqueta = 'material '.($indice + 1);

        $faltan = [];

        if ((float) ($this->bobinas[$campo] ?: 0) <= 0) {
            $faltan[] = 'el scrap en cm';
        }

        if ((float) ($this->bobinas['cantidad'] ?: 0) <= 0) {
            $faltan[] = 'Cantidad (mts)';
        }

        if (empty($material['material_id'])) {
            $faltan[] = 'el '.$etiqueta;

            return 'Falta completar: '.implode(', ', $faltan).'.';
        }

        if ((float) ($material['mic'] ?? 0) <= 0) {
            $faltan[] = 'el mic del '.$etiqueta;
        }

        if (empty($material['proveedor_id'])) {
            $faltan[] = 'el proveedor del '.$etiqueta;
        }

        $item = $this->itemsElegidos[$material['material_id']] ?? null;

        if ($item?->peso_especifico === null) {
            $faltan[] = 'el peso esp. del '.$etiqueta.' en Insumos';
        } elseif (! empty($material['proveedor_id']) && $this->costoDelMaterial($item, $material['proveedor_id']) === null) {
            $faltan[] = 'el costo del '.$etiqueta.' con ese proveedor en Insumos';
        }

        return $faltan === [] ? null : 'Falta completar: '.implode(', ', $faltan).'.';
    }

    /**
     * Formato de los numeros del tab de Costos.
     */
    private function num(?float $valor, int $decimales = 2): string
    {
        return Numero::formato($valor, $decimales);
    }

    private function usd(?float $valor): string
    {
        return Numero::usd($valor, prefijo: 'U$S ');
    }

    /**
     * Filas 15-17 de la planilla, en numeros: una por material (null si la
     * fila no tiene material).
     *
     * @return list<array<string, mixed>|null>
     */
    #[Computed]
    public function calculoProveedores(): array
    {
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $anchoLamina = (float) ($this->bobinas['ancho_lamina'] ?: 0);
        $scraps = ['impresion_scrap', 'laminacion_scrap', 'bilaminacion_scrap'];

        // Denominador de "Valor x kgrs": los kilos por 1000 metros de toda la lamina (SUMA(L15:L17)).
        $kgrsTotales = array_sum($this->kgrsPorMilMetros);

        $filas = [];

        foreach ($this->bobinas['materiales'] ?? [] as $indice => $material) {
            $item = $this->itemsElegidos[$material['material_id'] ?? null] ?? null;

            if ($item === null) {
                $filas[] = null;

                continue;
            }

            $mic = (float) ($material['mic'] ?: 0);
            $pesoEsp = $item->peso_especifico === null ? null : (float) $item->peso_especifico;
            $costo = $this->costoDelMaterial($item, $material['proveedor_id'] ?? null);
            $scrap = (float) ($this->bobinas[$scraps[$indice] ?? ''] ?: 0);
            $metros = $this->metrosATrabajar($indice);

            // K: kilos por 1000 m con ancho de lamina + scrap. L: con ancho refilado. J: K por los metros.
            $kgrsLamina = $pesoEsp !== null && $mic > 0 && $anchoLamina > 0 ? $pesoEsp * $mic * ($anchoLamina + $scrap) / 100 : null;
            $kgrsRefilado = $this->kgrsPorMilMetros[$indice] ?? null;
            $kgrsTrabajar = $kgrsLamina === null ? null : $kgrsLamina * $metros / 1000;

            // N: lo que cuesta el material por cada 1000 m de producto. P: eso por kilo de producto.
            $valorMil = $kgrsTrabajar !== null && $costo !== null && $cantidad > 0 ? $kgrsTrabajar * $costo / ($cantidad / 1000) * Parametro::valor(Parametro::MATERIAL_FACTOR) : null;
            $valorKg = $valorMil !== null && $kgrsTotales > 0 ? $valorMil / $kgrsTotales : null;

            $filas[] = compact('item', 'mic', 'costo', 'metros', 'kgrsTrabajar', 'kgrsLamina', 'kgrsRefilado', 'valorMil', 'valorKg');
        }

        return $filas;
    }

    /**
     * Bloque "Proveedores" del tab de Costos: la fila 15-17 de la planilla
     * calculada con los datos cargados. La incidencia queda pendiente porque
     * necesita el costo bruto de todos los bloques.
     *
     * @return array{titulo: string, columnas: list<string>, filas: list<list<string>>}
     */
    #[Computed]
    public function seccionProveedores(): array
    {
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $filas = [];

        foreach ($this->calculoProveedores as $fila) {
            $filas[] = $fila === null
                ? ['-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-']
                : [
                    $this->num(Parametro::valor(Parametro::MATERIAL_FACTOR), 0),
                    $fila['item']->nombre,
                    $fila['mic'] > 0 ? $this->num($fila['mic'], 0) : '-',
                    $this->usd($fila['costo']),
                    $cantidad > 0 ? $this->num($fila['metros'], 0) : '-',
                    $this->num($fila['kgrsTrabajar']),
                    $this->num($fila['kgrsLamina']),
                    $this->num($fila['kgrsRefilado']),
                    $this->usd($fila['valorMil']),
                    $this->usd($fila['valorKg']),
                    $this->incidencia($fila['valorKg']),
                ];
        }

        return [
            'titulo' => 'Proveedores',
            'columnas' => ['', 'Detalle', 'Mic', 'Valor x kgrs', 'Mts a trabajar', 'Kgrs a trabajar', 'Kgrs x 1000 mts', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor x kgrs', 'Incidencia'],
            'filas' => $filas,
        ];
    }

    /**
     * Metros a trabajar de cada material (H15:H17 de la planilla): la cantidad
     * mas los metros de arranque de cada diseño y cambio para el primero, y
     * un extra fijo para los otros dos.
     */
    private function metrosATrabajar(int $indice): float
    {
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);

        if ($indice === 0) {
            $arranques = (float) ($this->bobinas['disenos'] ?: 0) + (float) ($this->bobinas['cambios'] ?: 0);

            return $cantidad + Parametro::valor(Parametro::SCRAP_METROS_POR_ARRANQUE) * $arranques;
        }

        return $cantidad + Parametro::valor(Parametro::SCRAP_METROS_EXTRA);
    }

    /**
     * Horas y costo de un sector que trabaja toda la cantidad: preparacion +
     * produccion por el valor de la hora, llevado a 1000 metros y a kilo.
     *
     * @return array{prep: float, prod: float, valorHora: float, valorMil: float, valorKg: ?float}|null
     */
    private function costoDeSector(?Operativo $sector, float $prep): ?array
    {
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $produccion = (float) ($sector?->produccion_mts_hora ?: 0);

        if ($sector === null || $produccion <= 0 || $cantidad <= 0) {
            return null;
        }

        $prod = $cantidad / $produccion;
        $valorHora = (float) $sector->valor_hora;
        $valorMil = (($prep + $prod) * $valorHora) / ($cantidad / 1000);
        $kgrsTotales = array_sum($this->kgrsPorMilMetros);

        return [
            'prep' => $prep,
            'prod' => $prod,
            'valorHora' => $valorHora,
            'valorMil' => $valorMil,
            'valorKg' => $kgrsTotales > 0 ? $valorMil / $kgrsTotales : null,
        ];
    }

    /**
     * Fila 19 de la planilla (costo de impresora / reprint), en numeros.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function calculoReprint(): ?array
    {
        $imprime = ($this->bobinas['impresion'] ?? '') === 'Si' ? 1 : 0;
        $reprint = ($this->bobinas['reprint'] ?? '') === 'Si' ? 1 : 0;
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $disenos = (float) ($this->bobinas['disenos'] ?: 0);
        $cambios = (float) ($this->bobinas['cambios'] ?: 0);

        $impresora = Operativo::delSector(Operativo::IMPRESION);
        $setup = (float) ($impresora?->setup_horas ?? 0);

        $prep = ($setup * $disenos + Parametro::valor(Parametro::IMPRESION_HORAS_POR_CAMBIO) * $cambios) * $imprime;
        $costo = $this->costoDeSector($impresora, $prep);

        if ($costo === null) {
            return null;
        }

        $produccion = (float) $impresora->produccion_mts_hora;

        return [
            'flag' => $reprint,
            'prep' => $prep,
            'prod' => $costo['prod'] * $imprime,
            'prepReprint' => ($setup / max(Parametro::valor(Parametro::REPRINT_DIVISOR_SETUP), 1)) * $reprint,
            'prodReprint' => ($cantidad / $produccion) * $reprint,
            'valorHora' => $costo['valorHora'],
            'valorMil' => $costo['valorMil'] * $imprime,
            'valorKg' => $costo['valorKg'] === null ? null : $costo['valorKg'] * $imprime,
        ];
    }

    /**
     * Bloque "Impresion y Reprint" del tab de Costos (filas 19-22 de la
     * planilla). Por ahora solo la fila de Reprint / costo de impresora sale
     * de los datos: las otras tres esperan los precios de tela, tintas y
     * diluyentes en Insumos.
     *
     * @return array{titulo: string, columnas: list<string>, filas: list<list<string>>}
     */
    #[Computed]
    public function seccionImpresion(): array
    {
        $imprime = ($this->bobinas['impresion'] ?? '') === 'Si' ? 1 : 0;
        $reprint = ($this->bobinas['reprint'] ?? '') === 'Si' ? 1 : 0;
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $calculo = $this->calculoReprint;

        $filaReprint = $calculo !== null
            ? [(string) $reprint, 'Reprint', $this->num($calculo['prep']), $this->num($calculo['prod']), $this->num($calculo['prepReprint']), $this->num($calculo['prodReprint']), $this->usd($calculo['valorHora']), '', $this->usd($calculo['valorMil']), $this->usd($calculo['valorKg']), $this->incidencia($calculo['valorKg'])]
            : [(string) $reprint, 'Reprint', '-', '-', '-', '-', $this->usd(Operativo::delSector(Operativo::IMPRESION)?->valor_hora), '', '-', '-', '-'];

        $tela = $this->calculoTela;
        $tintas = $this->calculoTintas;
        $limpieza = $this->calculoLimpieza;

        return [
            'titulo' => 'Impresión y Reprint',
            'columnas' => ['', 'Detalle', 'Prep', 'Prod', '', '', 'Valor hs', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor kgrs', 'Incidencia'],
            'filas' => [
                $filaReprint,
                [(string) $imprime, 'Tela Doble Fax', '', '', $this->num($tela['porColorReprint'] ?? null, 3), '', 'x Color U$S', $this->num($tela['porColor'] ?? null, 3), '', $this->usd($tela['valorKg'] ?? null), $this->incidencia($tela['valorKg'] ?? null)],
                [(string) $imprime, 'Tintas + Diluyentes', '', '', $this->num($tintas['reprintGrsM2'] ?? null, 3), '', 'Valor grs/mts2', $this->num($tintas['grsM2'] ?? null, 3), $this->usd($tintas['valorMil'] ?? null), $this->usd($tintas['valorKg'] ?? null), $this->incidencia($tintas['valorKg'] ?? null)],
                [(string) $imprime, 'Limpieza Diluyentes', $cantidad > 0 ? $this->num(Parametro::litrosLimpieza($cantidad), 0) : '-', '', '', '', 'Valor Lts', $this->num($limpieza['precioLitro'] ?? null, 3), $this->usd($limpieza['valorMil'] ?? null), $this->usd($limpieza['valorKg'] ?? null), $this->incidencia($limpieza['valorKg'] ?? null)],
            ],
        ];
    }

    /**
     * Precio de un insumo de impresion / laminacion (ver self::INSUMOS): el
     * del proveedor elegido en Insumos o, si no hay elegido, el primero.
     */
    private function precioInsumo(string $clave): ?float
    {
        [$insumo, $nombre] = self::INSUMOS[$clave];

        $item = InsumoItem::with('precios')
            ->where('nombre', $nombre)
            ->whereHas('familia.insumo', fn ($consulta) => $consulta->where('nombre', $insumo))
            ->first();

        if ($item === null) {
            return null;
        }

        $precio = $item->precios->firstWhere('proveedor_id', $item->proveedor_elegido_id) ?? $item->precios->first();

        return $precio?->costo === null ? null : (float) $precio->costo;
    }

    /**
     * Fila 20 de la planilla: la tela doble faz que se usa por cada color.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function calculoTela(): ?array
    {
        $imprime = ($this->bobinas['impresion'] ?? '') === 'Si' ? 1 : 0;
        $reprint = ($this->bobinas['reprint'] ?? '') === 'Si' ? 1 : 0;
        $precio = $this->precioInsumo('tela');
        $anchoLamina = (float) ($this->bobinas['ancho_lamina'] ?: 0);
        $desarrollo = (float) ($this->bobinas['desarrollo'] ?: 0);
        $peso = (float) ($this->bobinas['peso'] ?: 0);
        $colores = $this->coloresPorArranque();

        if ($precio === null || $anchoLamina <= 0 || $desarrollo <= 0 || $peso <= 0) {
            return null;
        }

        $scrap = (float) ($this->bobinas['impresion_scrap'] ?: 0);
        $largo = max(Parametro::valor(Parametro::TELA_LARGO_ROLLO), 1);
        $ancho = max(Parametro::valor(Parametro::TELA_ANCHO_ROLLO), 1);

        // L20: precio del rollo prorrateado a la superficie de un color, por el factor de aprovechamiento.
        $porColor = ($precio / $largo / $ancho) * ($anchoLamina + $scrap) * $desarrollo * Parametro::valor(Parametro::TELA_FACTOR);

        return [
            'porColor' => $porColor,
            'porColorReprint' => $colores > 0 ? ($porColor / $colores) * $reprint : 0,
            'valorKg' => ($porColor * $colores / $peso) * $imprime,
        ];
    }

    /**
     * Total de colores a imprimir (S8 de la planilla):
     * (diseños x colores) + (variedades x cambios).
     */
    private function coloresPorArranque(): float
    {
        $colores = (float) ($this->bobinas['colores'] ?: 0);
        $disenos = (float) ($this->bobinas['disenos'] ?: 0);
        $variedades = (float) ($this->bobinas['variedades'] ?: 0);
        $cambios = (float) ($this->bobinas['cambios'] ?: 0);

        return $disenos * $colores + $variedades * $cambios;
    }

    /**
     * Fila 21 de la planilla: tintas y diluyentes por metro cuadrado.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function calculoTintas(): ?array
    {
        $imprime = ($this->bobinas['impresion'] ?? '') === 'Si' ? 1 : 0;
        $reprint = ($this->bobinas['reprint'] ?? '') === 'Si' ? 1 : 0;
        $tintas = $this->precioInsumo('tintas');
        $blanca = $this->precioInsumo('tinta_blanca');
        $anchoLamina = (float) ($this->bobinas['ancho_lamina'] ?: 0);
        $kgrsTotales = array_sum($this->kgrsPorMilMetros);

        if ($tintas === null || $blanca === null || $anchoLamina <= 0 || $kgrsTotales <= 0) {
            return null;
        }

        $caras = Parametro::valor(Parametro::TINTAS_CARAS);
        $blancoPct = (float) ($this->bobinas['blanco'] ?: 0);
        $impresoPct = (float) ($this->bobinas['porcentaje_impreso'] ?: 0);
        $colores = (float) ($this->bobinas['colores'] ?: 0);
        $scrap = (float) ($this->bobinas['impresion_scrap'] ?: 0);

        // L21: gramos por m2 valorizados, con la blanca segun su % y todo segun el % impreso.
        $grsM2 = (((($tintas - $blanca) / 1000 * $caras) + (($blanca / 1000 * $caras) * $blancoPct) / 100) * $impresoPct) / 100;

        // I21: barniz y alargue del reprint.
        $barniz = $this->precioInsumo('barniz') ?? 0;
        $alargue = $this->precioInsumo('dy_alargue') ?? 0;
        $reprintGrsM2 = (($barniz * Parametro::valor(Parametro::BARNIZ_PROPORCION) + $alargue * Parametro::valor(Parametro::ALARGUE_PROPORCION)) / 1000 * $caras) * $reprint;

        // N21: por cada 1000 m de lamina (ancho + scrap, en dm), escalado por los colores sobre la base.
        $base = max(Parametro::valor(Parametro::IMPRESION_COLORES_BASE), 1);
        $valorMil = ($grsM2 * (($anchoLamina + $scrap) * 10) * $imprime) * ($colores / $base);

        return [
            'grsM2' => $grsM2,
            'reprintGrsM2' => $reprintGrsM2,
            'valorMil' => $valorMil,
            'valorKg' => $valorMil / $kgrsTotales,
        ];
    }

    /**
     * Fila 22 de la planilla: los litros de limpieza que lleva la tirada.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function calculoLimpieza(): ?array
    {
        $imprime = ($this->bobinas['impresion'] ?? '') === 'Si' ? 1 : 0;
        $precio = $this->precioInsumo('dy_limpieza');
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $kgrsTotales = array_sum($this->kgrsPorMilMetros);

        if ($precio === null || $cantidad <= 0 || $kgrsTotales <= 0) {
            return null;
        }

        $valorMil = ($precio * Parametro::litrosLimpieza($cantidad) / $cantidad * 1000) * $imprime;

        return [
            'precioLitro' => $precio,
            'valorMil' => $valorMil,
            'valorKg' => $valorMil / $kgrsTotales,
        ];
    }

    /**
     * Pasadas de laminacion sin solvente y con solvente (J7 y N7 de la
     * planilla), segun Laminación (Simple / Bi. / Tri.) y Solvente (Si / No).
     *
     * @return array{sinSolvente: int, conSolvente: int}
     */
    private function pasadasDeLaminacion(): array
    {
        $pasadas = match ($this->bobinas['laminacion'] ?? '') {
            'Simple' => 1,
            'Bi.' => 2,
            'Tri.' => 3,
            default => 0,
        };

        $conSolvente = ($this->bobinas['solvente'] ?? '') === 'Si';

        return [
            'sinSolvente' => $conSolvente ? 0 : $pasadas,
            'conSolvente' => $conSolvente ? $pasadas : 0,
        ];
    }

    /**
     * Filas 23-25 de la planilla: la laminadora, el adhesivo solvent less y
     * el adhesivo con solvente.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function calculoLaminacion(): ?array
    {
        ['sinSolvente' => $sinSolvente, 'conSolvente' => $conSolvente] = $this->pasadasDeLaminacion();
        $laminadora = Operativo::delSector(Operativo::LAMINACION);
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $produccion = (float) ($laminadora?->produccion_mts_hora ?: 0);
        $kgrsTotales = array_sum($this->kgrsPorMilMetros);
        $anchoLamina = (float) ($this->bobinas['ancho_lamina'] ?: 0);

        if ($laminadora === null || $produccion <= 0 || $cantidad <= 0 || $kgrsTotales <= 0) {
            return null;
        }

        // Fila 23: horas de preparacion por pasada y de produccion por pasada.
        $prep = (float) $laminadora->setup_horas * ($sinSolvente + $conSolvente);
        $prod = $cantidad / $produccion * ($sinSolvente + $conSolvente);
        $valorHora = (float) $laminadora->valor_hora;
        $laminadoMil = (($prep + $prod) * $valorHora) / ($cantidad / 1000);

        // Fila 24: solvent less. La planilla multiplica por caras y pasadas solo al catalizador.
        $caras = Parametro::valor(Parametro::ADHESIVO_CARAS);
        $slCompuesto = $this->precioInsumo('sl_compuesto');
        $slCatalizador = $this->precioInsumo('sl_catalizador');
        $slGrsM2 = $slCompuesto === null || $slCatalizador === null ? null
            : ($slCompuesto / 1000 * Parametro::valor(Parametro::SOLVENTLESS_COMPUESTO))
                + ($slCatalizador / 1000 * Parametro::valor(Parametro::SOLVENTLESS_CATALIZADOR)) * $caras * $sinSolvente;
        $scrapLaminacion = (float) ($this->bobinas['laminacion_scrap'] ?: 0) + (float) ($this->bobinas['bilaminacion_scrap'] ?: 0);
        $slMil = $slGrsM2 === null ? null : $slGrsM2 * (($anchoLamina + $scrapLaminacion) * 10);
        $slKg = $slMil === null ? null : $slMil / $kgrsTotales * $sinSolvente;

        // Fila 25: con solvente.
        $solvCompuesto = $this->precioInsumo('solvente_compuesto');
        $solvGrsM2 = $solvCompuesto === null ? null : ($solvCompuesto / 1000) * $caras * $conSolvente;
        $solvMil = $solvGrsM2 === null ? null : $solvGrsM2 * ($anchoLamina * 10);
        $solvKg = $solvMil === null ? null : $solvMil / $kgrsTotales;

        return [
            'sinSolvente' => $sinSolvente,
            'conSolvente' => $conSolvente,
            'prep' => $prep,
            'prod' => $prod,
            'valorHora' => $valorHora,
            'laminadoMil' => $laminadoMil,
            'laminadoKg' => $laminadoMil / $kgrsTotales,
            'slGrsM2' => $slGrsM2,
            'slMil' => $slMil,
            'slKg' => $slKg,
            'solvGrsM2' => $solvGrsM2,
            'solvMil' => $solvMil,
            'solvKg' => $solvKg,
        ];
    }

    /**
     * Bloque "Laminacion y solventes" del tab de Costos.
     *
     * @return array{titulo: string, columnas: list<string>, filas: list<list<string>>}
     */
    #[Computed]
    public function seccionLaminacion(): array
    {
        $c = $this->calculoLaminacion;
        ['sinSolvente' => $sinSolvente, 'conSolvente' => $conSolvente] = $this->pasadasDeLaminacion();
        $laminadora = Operativo::delSector(Operativo::LAMINACION);

        $filas = $c === null
            ? [
                [(string) ($sinSolvente + $conSolvente), 'Costo laminado', '-', '-', '', '', $this->usd($laminadora?->valor_hora), '', '-', '-', '-'],
                [(string) $sinSolvente, 'Sin solvente', '', '', '', '', 'Valor grs/mts2', '-', '-', '-', '-'],
                [(string) $conSolvente, 'Con solvente', '', '', '', '', 'Valor grs/mts2', '-', '-', '-', '-'],
            ]
            : [
                [(string) ($sinSolvente + $conSolvente), 'Costo laminado', $this->num($c['prep']), $this->num($c['prod']), '', '', $this->usd($c['valorHora']), '', $this->usd($c['laminadoMil']), $this->usd($c['laminadoKg']), $this->incidencia($c['laminadoKg'])],
                [(string) $sinSolvente, 'Sin solvente', '', '', '', '', 'Valor grs/mts2', $this->num($c['slGrsM2'], 3), $this->usd($c['slMil']), $this->usd($c['slKg']), $this->incidencia($c['slKg'])],
                [(string) $conSolvente, 'Con solvente', '', '', '', '', 'Valor grs/mts2', $this->num($c['solvGrsM2'], 3), $this->usd($c['solvMil']), $this->usd($c['solvKg']), $this->incidencia($c['solvKg'])],
            ];

        return [
            'titulo' => 'Laminación y solventes',
            'columnas' => ['', 'Detalle', 'Prep', 'Prod', '', '', 'Valor hs', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor kgrs', 'Incidencia'],
            'filas' => $filas,
        ];
    }

    /**
     * Fila 26 de la planilla (refilado / rebobinado), en numeros. La planilla
     * tiene un "Refilado SI/NO" (D8) que el formulario no: se toma como SI.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function calculoRefilado(): ?array
    {
        // D8 de la planilla: sin refilado la fila queda en cero.
        if (($this->bobinas['refilado'] ?? 'Si') !== 'Si') {
            return null;
        }

        $rebobinado = Operativo::delSector(Operativo::REBOBINADO);

        return $this->costoDeSector($rebobinado, (float) ($rebobinado?->setup_horas ?? 0));
    }

    /**
     * Suma del "Valor kgrs" de todas las filas calculadas hasta ahora
     * (SUMA(P15:P26) de la planilla, con lo que ya existe).
     */
    private function valorKgAcumulado(): float
    {
        $suma = 0.0;

        foreach ($this->calculoProveedores as $fila) {
            $suma += $fila['valorKg'] ?? 0;
        }

        $suma += $this->calculoReprint['valorKg'] ?? 0;
        $suma += $this->calculoTela['valorKg'] ?? 0;
        $suma += $this->calculoTintas['valorKg'] ?? 0;
        $suma += $this->calculoLimpieza['valorKg'] ?? 0;
        $suma += $this->calculoLaminacion['laminadoKg'] ?? 0;
        $suma += $this->calculoLaminacion['slKg'] ?? 0;
        $suma += $this->calculoLaminacion['solvKg'] ?? 0;
        $suma += $this->calculoRefilado['valorKg'] ?? 0;

        return $suma;
    }

    /**
     * Bloque "Refilado y Material Scrap" (filas 26-27 de la planilla).
     *
     * @return array{titulo: string, columnas: list<string>, filas: list<list<string>>}
     */
    #[Computed]
    public function seccionRefilado(): array
    {
        $rebobinado = Operativo::delSector(Operativo::REBOBINADO);
        $calculo = $this->calculoRefilado;
        $scrapPct = (float) ($rebobinado?->scrap_pct ?? 0);

        $refila = ($this->bobinas['refilado'] ?? 'Si') === 'Si' ? '1' : '0';

        $filaRefilado = $calculo !== null
            ? [$refila, 'Refilado', $this->num($calculo['prep']), $this->num($calculo['prod']), '', $this->usd($calculo['valorHora']), $this->usd($calculo['valorMil']), $this->usd($calculo['valorKg']), $this->incidencia($calculo['valorKg'])]
            : [$refila, 'Refilado', '-', '-', '', $this->usd($rebobinado?->valor_hora), '-', '-', '-'];

        // El scrap es un porcentaje de todo lo costeado hasta aca: crece a medida que se sumen bloques.
        $valorScrap = $this->valorKgScrap();

        return [
            'titulo' => 'Refilado y Material Scrap',
            'columnas' => ['', 'Detalle', 'Prep', 'Prod', '', 'Valor hs', 'Valor x kgrs', 'Valor kgrs', 'Incidencia'],
            'filas' => [
                $filaRefilado,
                [$this->num($scrapPct, 0).'%', 'Material Scrap', '', '', '', '', '', $this->usd($valorScrap), $this->incidencia($valorScrap)],
            ],
        ];
    }

    /**
     * Fila 28 de la planilla (flete), una por entrega: el precio de la zona y
     * el tramo en Flete Insumos, pasado a dolares.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function calculoFletes(): array
    {
        // Retira el cliente: no hay flete que costear.
        if ($this->retiroEnSucursal) {
            return [];
        }

        $dolar = Ajuste::valorDe(Fletes::GRUPO_DOLAR);
        $peso = (float) ($this->bobinas['peso'] ?: 0);
        $filas = [];

        foreach ($this->entregas as $entrega) {
            $zonaId = $entrega['flete_zona_id'] ?: null;
            $tramoId = $entrega['flete_tramo_id'] ?: null;

            if (! $zonaId && ! $tramoId) {
                continue;
            }

            $zona = $zonaId ? FleteZona::find($zonaId) : null;
            $tramo = $tramoId ? FleteTramo::find($tramoId) : null;
            $pesos = $zona && $tramo
                ? FletePrecio::where('flete_zona_id', $zona->id)->where('flete_tramo_id', $tramo->id)->value('precio')
                : null;

            $usd = $pesos !== null && $dolar > 0 ? (float) $pesos / $dolar : null;

            $filas[] = [
                'zona' => $zona?->nombre,
                'tramo' => $tramo === null ? null : (float) $tramo->kg,
                'usd' => $usd,
                'valorKg' => $usd !== null && $peso > 0 ? $usd / $peso : null,
            ];
        }

        return $filas;
    }

    /**
     * Bloque "Otros costos" (fila 28 de la planilla): el flete de cada entrega.
     *
     * @return array{titulo: string, columnas: list<string>, filas: list<list<string>>}
     */
    #[Computed]
    public function seccionOtrosCostos(): array
    {
        $filas = [];

        foreach ($this->calculoFletes as $indice => $flete) {
            $filas[] = [
                '1',
                count($this->calculoFletes) > 1 ? 'Flete entrega '.($indice + 1) : 'Flete',
                $flete['zona'] ?? '-',
                $flete['tramo'] === null ? '-' : $this->num($flete['tramo'], 0),
                $this->num($flete['usd']),
                $this->usd($flete['valorKg']),
                $this->incidencia($flete['valorKg']),
            ];
        }

        if ($filas === []) {
            $filas[] = ['-', 'Flete', '-', '-', '-', '-', '-'];
        }

        return [
            'titulo' => 'Otros costos',
            'columnas' => ['', 'Detalle', 'Zona', 'Tramo (kg)', 'Flete U$S', 'Valor kgrs', 'Incidencia'],
            'filas' => $filas,
        ];
    }

    /**
     * Fila 27 de la planilla: el % de scrap de rebobinado sobre todo lo
     * costeado hasta el refilado (SUMA(P15:P26)).
     */
    private function valorKgScrap(): ?float
    {
        $acumulado = $this->valorKgAcumulado();
        $scrapPct = (float) (Operativo::delSector(Operativo::REBOBINADO)?->scrap_pct ?? 0);

        return $acumulado > 0 ? $acumulado * $scrapPct / 100 : null;
    }

    /**
     * Costo bruto por kilo: la suma de todos los bloques (P29 = SUMA(P15:P28)).
     * Parcial mientras falten tela, tintas, laminacion y solventes.
     */
    #[Computed]
    public function costoBruto(): float
    {
        $suma = $this->valorKgAcumulado() + ($this->valorKgScrap() ?? 0);

        foreach ($this->calculoFletes as $flete) {
            $suma += $flete['valorKg'] ?? 0;
        }

        return $suma;
    }

    /**
     * Estructura del laminado segun las familias de los materiales elegidos:
     * es la clave de la tabla "Margen" de Variables Costos. Reemplaza al SI
     * anidado de 40 ramas de la planilla, que miraba los nombres uno por uno.
     */
    #[Computed]
    public function estructura(): ?string
    {
        $codigos = [];

        foreach ($this->bobinas['materiales'] ?? [] as $material) {
            $item = $this->itemsElegidos[$material['material_id'] ?? null] ?? null;

            if ($item !== null) {
                $codigos[] = $this->codigoDeFamilia((string) $item->familia?->nombre);
            }
        }

        if ($codigos === []) {
            return null;
        }

        if (count($codigos) >= 3 || (int) ($this->bobinas['laminado'] ?? 0) === 3) {
            return 'Trilaminado';
        }

        sort($codigos);
        $clave = implode('+', array_unique($codigos));

        return match ($clave) {
            'PE' => 'Pe',
            'BOPP' => 'Bopp 20+20',
            'BOPP+PE' => 'Bopp+Pe',
            'PE+PET' => 'Pet+Pe',
            'FOIL+PET' => 'Trilaminado',
            'PAPEL' => 'Papel Seda',
            default => null,
        };
    }

    private function codigoDeFamilia(string $familia): string
    {
        $nombre = mb_strtolower($familia);

        return match (true) {
            str_contains($nombre, 'poliet') => 'PE',
            str_contains($nombre, 'poliest') => 'PET',
            str_contains($nombre, 'bopp') => 'BOPP',
            str_contains($nombre, 'papel') => 'PAPEL',
            str_contains($nombre, 'foil'), str_contains($nombre, 'alumin') => 'FOIL',
            default => mb_strtoupper($familia),
        };
    }

    /**
     * Filas 29-38 de la planilla en numeros: del costo bruto al valor por kilo.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function calculoRentabilidad(): ?array
    {
        $costoBruto = $this->costoBruto;
        $peso = (float) ($this->bobinas['peso'] ?: 0);

        if ($costoBruto <= 0 || $peso <= 0) {
            return null;
        }

        $margen = $this->estructura === null ? null : VariableCosto::margen($this->estructura);
        $categoriaPct = VariableCosto::categoria($this->categoria);
        $plus = ($margen ?? 0) + VariableCosto::descuentoVolumen($peso)
            + (Parametro::valor(Parametro::CATEGORIA_EN_PRECIO) > 0 ? $categoriaPct : 0);
        $ajusteManual = (float) $this->ajuste_categoria;
        $comision = (float) (Vendedor::find($this->vendedor_id)?->comision($this->tipo_producto) ?? 0) + (float) $this->ajuste_vendedor;

        $divisor = 1 - ($plus + $comision) / 100;

        if ($divisor <= 0) {
            return null;
        }

        $precioBruto = $costoBruto / $divisor;                      // P34
        $ajuste = $precioBruto * $ajusteManual / 100;               // P35
        $contado = $precioBruto + $ajuste;                          // P36
        $plusKg = $precioBruto * $plus / 100;                       // P30
        $comisionKg = $contado * $comision / 100;                   // P32
        $ajusteKg = $contado - $costoBruto - $comisionKg;           // P31

        // Polimeros (cliches): ancho lamina x (desarrollo + extra) x colores x arranques x U$S/cm2.
        $anchoLamina = (float) ($this->bobinas['ancho_lamina'] ?: 0);
        $desarrollo = (float) ($this->bobinas['desarrollo'] ?: 0);
        $colores = $this->coloresPorArranque();
        $polimerosUsd = $anchoLamina * ($desarrollo + Parametro::valor(Parametro::POLIMEROS_DESARROLLO_EXTRA)) * $colores * Parametro::valor(Parametro::POLIMEROS_USD_CM2);
        $polimerosKg = $polimerosUsd / $peso;
        $bonificaPct = (float) ($this->bobinas['bonifica_polimeros'] ?: 0);
        $polimerosBonificadosKg = $polimerosKg * (Parametro::valor(Parametro::POLIMEROS_PROPORCION) / 100) * ($bonificaPct / 100);

        // Financiado: con los dias FF de la primera condicion de pago a plazo.
        $dias = (int) ($this->pagos[1]['dias_ff'] ?? 0);
        $financiacionPct = $dias > 0 ? VariableCosto::financiacion($dias) : 0;
        $financiacionKg = $contado * $financiacionPct / 100;        // R30
        $financiadoKg = ($plusKg + $ajusteKg - $financiacionKg) - $comisionKg; // K30

        return [
            'costoBruto' => $costoBruto,
            'estructura' => $this->estructura,
            'margen' => $margen,
            'plus' => $plus,
            'categoriaPct' => $categoriaPct,
            'ajusteManual' => $ajusteManual,
            'comision' => $comision,
            'precioBruto' => $precioBruto,
            'contado' => $contado,
            'plusKg' => $plusKg,
            'ajusteKg' => $ajusteKg,
            'comisionKg' => $comisionKg,
            'plusTotal' => $plusKg * $peso,                          // G30
            'rentabilidadTotal' => $ajusteKg * $peso + $plusKg * $peso, // G31
            'comisionTotal' => $comisionKg * $peso,                  // G32
            'polimerosUsd' => $polimerosUsd,
            'polimerosKg' => $polimerosKg,
            'bonificaPct' => $bonificaPct,
            'polimerosBonificadosKg' => $polimerosBonificadosKg,
            'dias' => $dias,
            'financiacionPct' => $financiacionPct,
            'financiacionKg' => $financiacionKg,
            'financiadoKg' => $financiadoKg,
            'financiadoTotal' => $financiadoKg * $peso,              // K32
            'financiadoRentabilidad' => ($ajusteKg + $plusKg) * $peso - $financiadoKg * $peso, // K31
        ];
    }

    /**
     * Incidencia de un renglon sobre el precio sin plus ni comision (Q15 de
     * la planilla: P x 100 / (P36 - P30 - P32)).
     */
    private function incidencia(?float $valorKg): string
    {
        $r = $this->calculoRentabilidad;

        if ($valorKg === null || $r === null) {
            return '-';
        }

        $base = $r['contado'] - $r['plusKg'] - $r['comisionKg'];

        return $base > 0 ? $this->num($valorKg * 100 / $base).'%' : '-';
    }

    /**
     * Bloque "Rentabilidad, financiado y costo bruto" del tab de Costos, con
     * la misma forma que la maqueta.
     *
     * @return array{costo_bruto: string, contado: string, estructura: ?string, sin_margen: bool, filas: list<array<string, string>>}
     */
    #[Computed]
    public function rentabilidad(): array
    {
        $r = $this->calculoRentabilidad;
        $peso = (float) ($this->bobinas['peso'] ?: 0);
        $pct = fn (?float $valor) => Numero::corto($valor);
        $xkg = fn (?float $valor) => $valor === null ? '-' : $this->num($valor).' xKg';

        if ($r === null) {
            $vacia = fn (string $porcentaje, string $detalle, string $origen, string $valor) => compact('porcentaje', 'detalle', 'origen', 'valor') + ['importe' => '-', 'por_kg' => '-', 'financiado' => '-', 'bruto' => '-'];

            return [
                'costo_bruto' => $this->costoBruto > 0 ? $this->usd($this->costoBruto) : '-',
                'contado' => '-',
                'estructura' => $this->estructura,
                'sin_margen' => $this->estructura !== null && VariableCosto::margen($this->estructura) === null,
                'filas' => [
                    $vacia('-', '% Plus', 'Cliente', $this->categoria ?: '-'),
                    $vacia('-', '%', 'Descuento', $pct((float) $this->ajuste_categoria)),
                    $vacia('-', '% Comisión', '', Vendedor::find($this->vendedor_id)?->nombre ?? '-'),
                    $vacia('0%', 'Polímeros', '', '100%'),
                    $vacia('', 'Rentabilidad bonificando polímeros', '', ''),
                ],
            ];
        }

        return [
            'costo_bruto' => $this->usd($r['costoBruto']),
            'contado' => $this->usd($r['contado']),
            'estructura' => $r['estructura'],
            'sin_margen' => $r['estructura'] !== null && $r['margen'] === null,
            'filas' => [
                ['porcentaje' => $pct($r['plus']), 'detalle' => '% Plus', 'origen' => 'Cliente', 'valor' => $this->categoria ?: '-', 'importe' => $this->usd($r['plusTotal']), 'por_kg' => $xkg($r['plusKg']), 'financiado' => $this->num($r['financiadoKg']), 'bruto' => $this->usd($r['plusKg'])],
                ['porcentaje' => $pct($r['categoriaPct']), 'detalle' => '%', 'origen' => 'Descuento', 'valor' => $pct($r['ajusteManual']), 'importe' => $this->usd($r['rentabilidadTotal']), 'por_kg' => $xkg($r['ajusteKg'] + $r['plusKg']), 'financiado' => $this->num($r['financiadoRentabilidad']), 'bruto' => $this->usd($r['ajusteKg'])],
                ['porcentaje' => $pct($r['comision']), 'detalle' => '% Comisión', 'origen' => '', 'valor' => Vendedor::find($this->vendedor_id)?->nombre ?? '-', 'importe' => $this->usd($r['comisionTotal']), 'por_kg' => $xkg($r['comisionKg']), 'financiado' => $this->num($r['financiadoTotal']), 'bruto' => $this->usd($r['comisionKg'])],
                ['porcentaje' => $pct($r['bonificaPct']).'%', 'detalle' => 'Polímeros', 'origen' => '', 'valor' => $pct(Parametro::valor(Parametro::POLIMEROS_PROPORCION)).'%', 'importe' => $this->usd($r['polimerosUsd']), 'por_kg' => $xkg($r['polimerosKg']), 'financiado' => '', 'bruto' => $this->usd($r['polimerosBonificadosKg'])],
                ['porcentaje' => '', 'detalle' => 'Rentabilidad bonificando polímeros', 'origen' => '', 'valor' => '', 'importe' => $this->usd($r['rentabilidadTotal'] - $r['polimerosBonificadosKg'] * $peso), 'por_kg' => $xkg($r['ajusteKg'] + $r['plusKg'] - $r['polimerosBonificadosKg']), 'financiado' => '', 'bruto' => $this->usd($r['precioBruto'])],
            ],
        ];
    }

    /**
     * Bloque "Costo final": el valor por kilo al contado y a los dias de cada
     * condicion de pago cargada.
     *
     * @return array{columnas: list<string>, filas: list<list<string>>}
     */
    #[Computed]
    public function costoFinal(): array
    {
        $r = $this->calculoRentabilidad;
        $filas = [['Valor por Kgrs al contado', '', '', '', $r === null ? '-' : $this->usd($r['contado'])]];

        foreach ($this->pagos as $pago) {
            $dias = (int) ($pago['dias_ff'] ?? 0);

            if ($dias <= 0) {
                continue;
            }

            $pct = VariableCosto::financiacion($dias);
            $financiacion = $r === null ? null : $r['contado'] * $pct / 100;

            $filas[] = [
                'Valor por Kgrs a',
                (string) $dias,
                $this->num($pct).'%',
                $this->usd($financiacion),
                $r === null ? '-' : $this->usd($r['contado'] + $financiacion),
            ];
        }

        return [
            'columnas' => ['Detalle', 'Días FF', 'Ajuste cambiario', 'Financiación bancaria', 'Costo total'],
            'filas' => $filas,
        ];
    }

    /**
     * Costo por kilo del material con el proveedor elegido en la fila.
     */
    private function costoDelMaterial(?InsumoItem $item, int|string|null $proveedorId): ?float
    {
        if ($item === null || ! $proveedorId) {
            return null;
        }

        $costo = InsumoPrecio::where('insumo_item_id', $item->id)
            ->where('proveedor_id', $proveedorId)
            ->value('costo');

        return $costo === null ? null : (float) $costo;
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
            $this->bobinas['peso'] = '';

            return;
        }

        $refilado = $ancho * $modulos;

        $this->bobinas['ancho_refilado'] = $this->sinCerosDeMas($refilado);
        $this->bobinas['ancho_lamina'] = $this->sinCerosDeMas($refilado + $this->anchoLaminaExtra);

        $this->recalcularPeso();
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
    public function updatedEntregas(mixed $valor, ?string $clave = null): void
    {
        // Livewire manda la clave null cuando se reemplaza el arreglo entero.
        if ($clave === null) {
            return;
        }

        if (! str_ends_with($clave, '.flete_zona_id')) {
            return;
        }

        $indice = (int) strtok($clave, '.');

        $this->entregas[$indice]['direccion_id'] = '';
    }

    /**
     * El cliente retira en sucursal: los campos de flete quedan en gris.
     */
    #[Computed]
    public function retiroEnSucursal(): bool
    {
        return ($this->bobinas['forma_entrega'] ?? '') === self::RETIRO;
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
     * El numero que va a la derecha de Colores: (diseños x colores) + (variedades x cambios).
     */
    #[Computed]
    public function coloresTotal(): string
    {
        $campos = ['disenos', 'variedades', 'cambios', 'colores'];

        // Sin nada cargado no hay resultado que mostrar.
        if (collect($campos)->every(fn (string $campo) => trim((string) ($this->bobinas[$campo] ?? '')) === '')) {
            return '';
        }

        return (string) $this->coloresPorArranque();
    }

    /**
     * Que falta cargar para el total de colores, como la ayuda del peso.
     */
    public function ayudaColores(): ?string
    {
        $etiquetas = ['disenos' => 'Diseños', 'variedades' => 'Variedades', 'cambios' => 'Cambios', 'colores' => 'Colores'];

        $faltan = collect($etiquetas)
            ->filter(fn (string $etiqueta, string $campo) => trim((string) ($this->bobinas[$campo] ?? '')) === '')
            ->values()
            ->all();

        return $faltan === [] ? null : 'Falta completar: '.implode(', ', $faltan).'.';
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

    /**
     * Uni / bi / trilaminado: se muestran tantos materiales como laminas, y
     * los que sobran se vacian.
     */
    public function elegirLaminado(int $laminado): void
    {
        if (! isset(self::LAMINADOS[$laminado])) {
            $this->bobinas['laminado'] = 2;

            return;
        }

        $this->bobinas['laminado'] = $laminado;

        foreach ($this->bobinas['materiales'] as $indice => $material) {
            if ($indice >= $laminado) {
                $this->bobinas['materiales'][$indice] = ['material_id' => '', 'mic' => '', 'proveedor_id' => ''];
            }
        }

        $this->recalcularPeso();
        $this->recalcularScrap();
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
            'laminado' => 2,
            'materiales' => array_fill(0, self::MATERIALES, ['material_id' => '', 'mic' => '', 'proveedor_id' => '']),
            'bonifica_polimeros' => '',
            'cantidad' => '',
            'peso' => '',
            'peso_neto' => '',
            'por_bobina' => '',
            'buje' => '',
            'impresion' => '',
            'reprint' => '',
            'refilado' => 'Si',
            'contiene_liquido' => '',
            'solvente' => '',
            'laminacion' => '',
            'forma_entrega' => self::ENVIO,
            'disenos' => '',
            'variedades' => '',
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
     * Textos del tab Cotizacion, armados con los datos cargados.
     *
     * @return array{cotizacion: array<string, string>, entregas: list<array<string, string>>}
     */
    #[Computed]
    public function textosPropuestos(): array
    {
        $num = fn (float $valor, int $decimales = 0) => Numero::formato($valor, $decimales);
        $r = $this->calculoRentabilidad;

        $materiales = [];
        foreach ($this->bobinas['materiales'] ?? [] as $material) {
            $item = $this->itemsElegidos[$material['material_id'] ?? null] ?? null;
            if ($item !== null) {
                $mic = (float) ($material['mic'] ?: 0);
                $materiales[] = $item->nombre.($mic > 0 ? ' de '.$num($mic).' mic' : '');
            }
        }

        $anchoRefilado = (float) ($this->bobinas['ancho_refilado'] ?: 0);
        $paso = (float) ($this->bobinas['paso'] ?: 0);
        $buje = $this->bobinas['buje'] ?? '';
        $colores = (float) ($this->bobinas['colores'] ?: 0);
        $peso = (float) ($this->bobinas['peso'] ?: 0);
        $laminado = (int) ($this->bobinas['laminado'] ?? 0);

        $impresion = match (true) {
            ($this->bobinas['impresion'] ?? '') !== 'Si' => 'Sin impresión',
            $colores > 0 => $num($colores).' colores',
            default => '',
        };

        $laminacion = match (true) {
            $laminado <= 1 => 'Sin laminar',
            ($this->bobinas['solvente'] ?? '') === 'Si' => 'Con solvente',
            ($this->bobinas['laminacion'] ?? '') !== '' => 'Libre de solventes Apto alimentos',
            default => '',
        };

        $entregas = [];
        foreach ($this->entregas as $entrega) {
            $zona = $entrega['flete_zona_id'] ? FleteZona::find($entrega['flete_zona_id'])?->nombre : null;
            $direccion = $entrega['direccion_id'] ? ContactoDireccion::find($entrega['direccion_id'])?->direccion : null;
            $cantidad = (float) ($entrega['cantidad'] ?: 0);

            $entregas[] = [
                'texto_lugar' => $this->retiroEnSucursal ? self::RETIRO : ($zona ?? ''),
                'texto_cantidad' => $cantidad > 0 ? 'Cantidad '.$num($cantidad).' mts' : '',
                'texto_direccion' => $direccion ? 'Dirección '.$direccion : '',
            ];
        }

        return [
            'cotizacion' => [
                'tipo_producto' => $this->tipo_producto !== '' ? self::TIPOS_PRODUCTO[$this->tipo_producto] ?? '' : '',
                'producto' => $this->bobinas['producto_id'] ? (string) ContactoProducto::find($this->bobinas['producto_id'])?->nombre : '',
                'materiales' => implode(' + ', $materiales),
                'anchos' => $anchoRefilado > 0 ? $num($anchoRefilado * 10).' mm'.($buje !== '' ? ' Buje '.$buje.'´´' : '') : '',
                'paso' => $paso > 0 ? $num($paso * 10).' mm' : '',
                'impresion' => $impresion,
                'laminacion' => $laminacion,
                'cantidad' => $peso > 0 ? $num($peso).' kg +/- '.$num(Parametro::valor(Parametro::TOLERANCIA_PCT)).'%' : '',
                'precio' => $r !== null ? 'U$S '.$num($r['contado'], 2).' por kg + IVA' : '',
            ],
            'entregas' => $entregas,
        ];
    }

    /**
     * Vuelca los textos calculados en la cotizacion: se rehacen en cada render
     * y se guardan con ella tal como quedaron.
     */
    private function completarTextos(): void
    {
        unset($this->textosPropuestos);
        $propuesta = $this->textosPropuestos;

        $this->cotizacion = array_replace($this->cotizacion, $propuesta['cotizacion']);

        foreach ($propuesta['entregas'] as $indice => $textos) {
            if (isset($this->entregas[$indice])) {
                $this->entregas[$indice] = array_replace($this->entregas[$indice], $textos);
            }
        }
    }

    /**
     * Partes variables de las condiciones de venta.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function condicionesDeVenta(): array
    {
        $lugar = $this->retiroEnSucursal ? self::RETIRO : null;
        foreach ($this->entregas as $entrega) {
            if ($lugar === null && $entrega['flete_zona_id']) {
                $lugar = FleteZona::find($entrega['flete_zona_id'])?->nombre;
                break;
            }
        }

        $dias = 0;
        foreach ($this->pagos as $indice => $pago) {
            if ($indice > 0 && (int) ($pago['dias_ff'] ?? 0) > 0) {
                $dias = (int) $pago['dias_ff'];
                break;
            }
        }

        $plazo = (int) Parametro::valor(Parametro::PLAZO_ENTREGA_DIAS);
        $fecha = $this->fecha ? \Carbon\Carbon::parse($this->fecha) : now();
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $pago = $fecha->copy()->addDays($plazo + $dias);

        return [
            'plazo' => (string) $plazo,
            'lugar' => $lugar ?? '-',
            'vencimiento' => (string) (int) Parametro::valor(Parametro::VENCIMIENTO_MESES),
            'reclamos' => (string) (int) Parametro::valor(Parametro::RECLAMOS_DIAS),
            'dias_ff' => $dias > 0 ? (string) $dias : '-',
            'fecha_pago' => $dias > 0 ? ucfirst($meses[$pago->month - 1]).' '.$pago->format('d').' de '.$pago->year : '-',
            'vigencia' => (string) (int) Parametro::valor(Parametro::VIGENCIA_DIAS),
        ];
    }

    /**
     * Condiciones de pago (filas 11-12 y 36-38 de la planilla): AC es el % de
     * financiacion de esos dias, la financiacion bancaria es el contado por
     * ese %, y el costo total la suma. Se rehacen en cada render.
     */
    private function completarPagos(): void
    {
        $r = $this->calculoRentabilidad;
        $contado = $r['contado'] ?? null;

        foreach ($this->pagos as $indice => $pago) {
            $dias = (int) ($pago['dias_ff'] ?? 0);
            $pct = $indice === 0 || $dias <= 0 ? 0.0 : VariableCosto::financiacion($dias);

            if ($contado === null) {
                $this->pagos[$indice]['ac'] = $indice === 0 ? '' : ($dias > 0 ? number_format($pct, 2, '.', '') : '');
                $this->pagos[$indice]['financiacion'] = '';
                $this->pagos[$indice]['costo_total'] = '';

                continue;
            }

            $financiacion = $contado * $pct / 100;

            $aPlazo = $indice > 0 && $dias > 0;

            $this->pagos[$indice]['ac'] = $aPlazo ? number_format($pct, 2, '.', '') : '';
            $this->pagos[$indice]['financiacion'] = $aPlazo ? number_format($financiacion, 2, '.', '') : '';
            $this->pagos[$indice]['costo_total'] = number_format($contado + $financiacion, 2, '.', '');
        }
    }

    public function render()
    {
        $this->completarPagos();
        $this->completarTextos();

        return view('livewire.cotizaciones.form', [
            'estados' => Cotizacion::ESTADOS,
            'clientes' => Contacto::enEstado(Contacto::CLIENTE)
                ->orderBy('razon_social')
                ->get(['id', 'razon_social']),
            'vendedores' => Vendedor::orderBy('nombre')->get(['id', 'nombre']),
            'mangas' => Ajuste::opciones('mangas'),
            'bujes' => Ajuste::opciones('bujes'),
            // Dias de financiacion de Configuración > Variables Costos.
            'diasFf' => VariableCosto::listado(VariableCosto::FINANCIACION)->pluck('clave')->all(),
            // Tramos de kg / pallets de Configuración > Flete Insumos.
            'tramos' => FleteTramo::orderBy('kg')->get()->pluck('etiqueta', 'id'),
            // Todas las zonas de flete, para agendar una direccion nueva del cliente.
            'zonasCatalogo' => FleteZona::orderBy('nombre')->pluck('nombre', 'id'),
            // Los productos son propios del cliente elegido.
            'productos' => $this->sinCliente
                ? collect()
                : ContactoProducto::where('contacto_id', $this->cliente_id)->orderBy('nombre')->pluck('nombre', 'id'),
        ])->title(($this->guardada ? 'Cotización ' : 'Nueva cotización ').$this->numero);
    }
}
