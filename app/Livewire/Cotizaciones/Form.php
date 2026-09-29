<?php

namespace App\Livewire\Cotizaciones;

use App\Livewire\Fletes\Index as Fletes;
use App\Models\Ajuste;
use App\Models\AjusteTexto;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
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

    /**
     * Trilaminado: dos pegados, uno con solvente y el otro sin.
     */
    public const SOLVENTE_MIXTO = 'Mixto';

    public const OPCIONES = [
        'si_no' => ['Si', 'No'],
        'categorias' => ['A', 'B', 'C', 'OTRO'],
        'laminaciones' => ['Simple', 'Bi.', 'Tri.'],
        'formas_entrega' => [self::ENVIO, self::RETIRO],
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

    /**
     * Una fila de material. "extra" es la demasia: los cm que se le suman al
     * ancho refilado para ese material (vacio = el valor por defecto de Ajustes).
     */
    private const MATERIAL_VACIO = ['material_id' => '', 'mic' => '', 'proveedor_id' => '', 'extra' => ''];

    /** Cuantas laminas lleva el producto: define cuantos materiales se cargan. */
    public const LAMINADOS = [1 => 'Monolaminado', 2 => 'Bilaminado', 3 => 'Trilaminado'];

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

    /** Archivos que se estan subiendo a la OC (cualquier tipo). */
    public array $subidasOc = [];

    /**
     * Archivos adjuntos a la OC: ruta en el disco privado, nombre original,
     * tamaño y fecha. Bloqueado: la ruta no se puede tocar desde el navegador.
     *
     * @var array<int, array{ruta: string, nombre: string, tamanio: int, subido: string}>
     */
    #[Locked]
    public array $archivosOc = [];

    /** Alta al vuelo de un canal de recibo de OC. */
    public bool $creandoCanal = false;

    public string $nuevoCanal = '';

    /** Tamaño maximo de cada archivo de la OC, en KB. */
    public const MAXIMO_ARCHIVO_OC = 12288;

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

    /**
     * Productos que se suman debajo del primero con "Duplicar" o "Nuevo
     * producto". Cada uno es un formulario completo desde Datos de producto
     * para abajo; los Datos generales son los de la cotizacion.
     *
     * "grupo" une las opciones de un mismo producto: Duplicar agrega una
     * opcion al grupo del ultimo producto; Nuevo producto arranca un grupo.
     * El principal es el grupo GRUPO_PRINCIPAL.
     *
     * @var array<int, array{uid: string, grupo: string, tipo_producto: string, bobinas: array, entregas: array, pagos: array, cotizacion: array}>
     */
    public array $productosExtra = [];

    public const GRUPO_PRINCIPAL = 'principal';

    /**
     * Cuando este formulario es uno de esos productos: su clave dentro de la
     * cotizacion. Vacio en el formulario principal.
     */
    public ?string $uidProducto = null;

    /** Numero con el que se muestra el producto ("Datos de producto 2"). */
    public int $numeroProducto = 1;

    /** Como se nombra en pantalla: "Producto 1 · Opción 2", "Producto 2". */
    public string $etiquetaProducto = '';

    /** @var array<string, self> productos armados en memoria para los totales y la cotizacion */
    private array $enMemoria = [];

    /** Ultimo estado que el producto le aviso a la cotizacion. */
    public string $firmaSync = '';

    /** Guardar o aprobar, en espera de que los productos manden su ultimo estado. */
    public ?string $accionPendiente = null;

    /** @var array<int, string> productos que todavia no contestaron */
    public array $esperando = [];

    public function mount(
        ?Cotizacion $guardada = null,
        ?array $producto = null,
        array $generales = [],
        string $solapaProducto = 'datos',
        int $numeroProducto = 2,
        string $etiquetaProducto = '',
    ): void {
        $this->fecha = now()->format('Y-m-d');

        $this->bobinas = $this->bobinasVacias();
        $this->cotizacion = $this->cotizacionVacia();
        $this->oc = ['canal' => '', 'fecha_recibo' => '', 'quien' => '', 'numero' => ''];
        $this->entregas = [$this->entregaVacia(), $this->entregaVacia()];
        $this->pagos = $this->pagosVacios();

        // Uno de los productos extra: toma la cabecera de la cotizacion que lo contiene.
        if ($producto !== null) {
            $this->montarProducto($producto, $generales);
            $this->solapa = $solapaProducto;
            $this->numeroProducto = $numeroProducto;
            $this->etiquetaProducto = $etiquetaProducto ?: 'Producto '.$numeroProducto;
            $this->firmaSync = $this->firmaProducto();

            return;
        }

        $this->numero = Cotizacion::siguienteNumero();

        // El parametro se llama "guardada" porque $cotizacion ya es el bloque de textos.
        if ($guardada?->exists) {
            $this->cargar($guardada);
        }
    }

    /**
     * Arma el formulario con un producto de la cotizacion y la cabecera que
     * comparten todos. Lo usan los productos extra y el resumen de totales.
     *
     * @param  array<string, mixed>  $producto
     * @param  array<string, mixed>  $generales
     */
    public function montarProducto(array $producto, array $generales): void
    {
        $this->uidProducto = (string) ($producto['uid'] ?? '') ?: (string) Str::uuid();

        foreach (['numero', 'fecha', 'cliente_id', 'vendedor_id', 'categoria', 'ajuste_categoria', 'ajuste_vendedor'] as $campo) {
            if (array_key_exists($campo, $generales)) {
                $this->{$campo} = $generales[$campo];
            }
        }

        $vacias = $this->bobinasVacias();
        $entregas = $producto['entregas'] ?? [];

        $this->tipo_producto = (string) ($producto['tipo_producto'] ?? '');
        $this->bobinas = array_replace($vacias, $producto['bobinas'] ?? []);
        $this->bobinas['materiales'] = $this->materialesCompletos($producto['bobinas']['materiales'] ?? []);
        $this->entregas = array_map(fn (array $entrega) => array_replace($this->entregaVacia(), $entrega), $entregas ?: [$this->entregaVacia()]);
        $this->pagos = array_replace($this->pagosVacios(), $producto['pagos'] ?? []);
        $this->cotizacion = array_replace($this->cotizacionVacia(), $producto['cotizacion'] ?? []);

        $this->recalcularAnchos();
        $this->recalcularPesoOMetros();
        $this->recalcularScrap();
    }

    /**
     * Lo que se guarda de un producto: todo lo de Datos de producto para abajo.
     *
     * @return array<string, mixed>
     */
    public function snapshotProducto(): array
    {
        return [
            'uid' => (string) $this->uidProducto,
            'tipo_producto' => $this->tipo_producto,
            'bobinas' => $this->bobinas,
            'entregas' => $this->entregas,
            'pagos' => $this->pagos,
            'cotizacion' => $this->cotizacion,
        ];
    }

    private function firmaProducto(): string
    {
        return md5((string) json_encode($this->snapshotProducto()));
    }

    /**
     * La cabecera que comparten todos los productos de la cotizacion.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function generales(): array
    {
        return [
            'numero' => $this->numero,
            'fecha' => $this->fecha,
            'cliente_id' => $this->cliente_id,
            'vendedor_id' => $this->vendedor_id,
            'categoria' => $this->categoria,
            'ajuste_categoria' => $this->ajuste_categoria,
            'ajuste_vendedor' => $this->ajuste_vendedor,
        ];
    }

    /**
     * Cambia cuando cambia algo de la cabecera que usan los productos: se
     * rearman con el cliente, el vendedor y la categoria nuevos.
     */
    #[Computed]
    public function claveGenerales(): string
    {
        $generales = $this->generales;
        unset($generales['fecha']);

        return md5((string) json_encode($generales));
    }

    /**
     * Solo la cotizacion escucha a sus productos, y cada producto solo el
     * pedido de mandar su ultimo estado antes de guardar.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return $this->uidProducto === null
            ? ['producto-actualizado' => 'productoActualizado', 'quitar-producto' => 'quitarProducto']
            : ['sincronizar-productos' => 'sincronizar'];
    }

    /**
     * Cada cambio en un producto extra llega aca, asi la cotizacion lo guarda y lo suma.
     *
     * @param  array<string, mixed>  $producto
     */
    public function productoActualizado(array $producto): void
    {
        $uid = (string) ($producto['uid'] ?? '');

        foreach ($this->productosExtra as $indice => $extra) {
            if ($extra['uid'] === $uid) {
                $this->productosExtra[$indice] = ['uid' => $uid, 'grupo' => $extra['grupo'] ?? $uid] + $producto;
                $this->olvidarCalculos();
            }
        }

        if ($this->accionPendiente === null) {
            return;
        }

        // Guardar o aprobar esperaban a que contesten todos los productos.
        $this->esperando = array_values(array_diff($this->esperando, [$uid]));

        if ($this->esperando === []) {
            $accion = $this->accionPendiente;
            $this->accionPendiente = null;

            $accion === 'aprobar' ? $this->aprobarAhora() : $this->guardarAhora();
        }
    }

    /**
     * El producto extra vuelve a mandar su estado aunque no haya cambiado.
     */
    public function sincronizar(): void
    {
        $this->firmaSync = '';
    }

    /**
     * Un producto extra le avisa a la cotizacion cada vez que cambia algo.
     */
    public function rendered(): void
    {
        if ($this->uidProducto === null) {
            return;
        }

        $firma = $this->firmaProducto();

        if ($firma === $this->firmaSync) {
            return;
        }

        $this->firmaSync = $firma;
        $this->dispatch('producto-actualizado', producto: $this->snapshotProducto());
    }

    /**
     * Copia el ultimo producto (el principal si no hay otros) debajo.
     */
    public function duplicarProducto(): void
    {
        $base = $this->productosExtra === [] ? $this->snapshotProducto() : end($this->productosExtra);

        if (($base['tipo_producto'] ?? '') === '') {
            return;
        }

        // La copia es otra opcion del mismo producto.
        $grupo = $this->productosExtra === [] ? self::GRUPO_PRINCIPAL : ($base['grupo'] ?? $base['uid']);

        $this->productosExtra[] = ['uid' => (string) Str::uuid(), 'grupo' => $grupo] + $base;
        $this->olvidarCalculos();
    }

    /**
     * Un formulario en blanco para otro producto, del tipo que se elija.
     */
    public function nuevoProducto(): void
    {
        $uid = (string) Str::uuid();

        $this->productosExtra[] = [
            'uid' => $uid,
            // Un producto distinto: arranca su propio grupo.
            'grupo' => $uid,
            'tipo_producto' => '',
            'bobinas' => $this->bobinasVacias(),
            'entregas' => [$this->entregaVacia()],
            'pagos' => $this->pagosVacios(),
            'cotizacion' => $this->cotizacionVacia(),
        ];
    }

    public function quitarProducto(string $uid): void
    {
        $this->productosExtra = array_values(array_filter($this->productosExtra, fn (array $producto) => $producto['uid'] !== $uid));
        $this->esperando = array_values(array_diff($this->esperando, [$uid]));
        $this->olvidarCalculos();
    }

    /**
     * Un renglon por producto con su precio al contado y el total, para sumar
     * la cotizacion completa. Los productos extra se calculan con el mismo
     * formulario armado en memoria, asi dan igual que en su propia pantalla.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function resumenProductos(): array
    {
        $filas = [];

        foreach ($this->listaProductos as $item) {
            $fila = $this->formularioDe($item)->filaResumen($item['numero']);
            $fila['etiqueta'] = $item['etiqueta'];
            $fila['esOpcion'] = $item['opcion'] !== null;
            $filas[] = $fila;
        }

        return $filas;
    }

    /**
     * Todos los productos en orden, con su grupo y como se llaman en pantalla.
     * Los grupos numeran los productos; dentro de un grupo con mas de uno, cada
     * formulario es una opcion.
     *
     * @return array<int, array{uid: string, grupo: string, numero: int, producto: int, opcion: ?int, etiqueta: string}>
     */
    #[Computed]
    public function listaProductos(): array
    {
        $items = [['uid' => '', 'grupo' => self::GRUPO_PRINCIPAL]];

        foreach ($this->productosExtra as $extra) {
            $items[] = ['uid' => $extra['uid'], 'grupo' => $extra['grupo'] ?? $extra['uid']];
        }

        $tamanios = array_count_values(array_column($items, 'grupo'));
        $grupos = [];
        $opciones = [];

        foreach ($items as $indice => $item) {
            $grupo = $item['grupo'];
            $grupos[$grupo] ??= count($grupos) + 1;
            $opciones[$grupo] = ($opciones[$grupo] ?? 0) + 1;

            $opcion = $tamanios[$grupo] > 1 ? $opciones[$grupo] : null;

            $items[$indice] += [
                'numero' => $indice + 1,
                'producto' => $grupos[$grupo],
                'opcion' => $opcion,
                'etiqueta' => 'Producto '.$grupos[$grupo].($opcion !== null ? ' · Opción '.$opcion : ''),
            ];
        }

        return $items;
    }

    /**
     * El formulario de un producto de la lista: este mismo para el principal,
     * y para los extra uno armado en memoria con su estado guardado.
     *
     * @param  array{uid: string}  $item
     */
    private function formularioDe(array $item): self
    {
        if ($item['uid'] === '') {
            return $this;
        }

        $producto = collect($this->productosExtra)->firstWhere('uid', $item['uid']);

        return $this->enMemoria[$item['uid']] ??= tap(app(self::class), fn (self $formulario) => $formulario->montarProducto($producto, $this->generales));
    }

    private function olvidarCalculos(): void
    {
        $this->enMemoria = [];
        unset($this->resumenProductos, $this->listaProductos, $this->bloquesCotizacion, $this->productosOrdenCompra, $this->filasOrdenCompra, $this->entregasOrdenCompra, $this->entregasDelPedido);
    }

    /**
     * La solapa Cotizacion: un bloque por producto. Cuando un producto tiene
     * opciones (se duplico), lo que es igual en todas va una sola vez arriba
     * y cada opcion muestra solo lo que cambia; cantidad y precio van siempre
     * en la opcion.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function bloquesCotizacion(): array
    {
        $grupos = [];

        foreach ($this->listaProductos as $item) {
            $formulario = $this->formularioDe($item);
            $textos = $formulario->textosPropuestos;

            $grupos[$item['grupo']][] = [
                'opcion' => $item['opcion'],
                'campos' => $formulario->camposCotizacion($textos['cotizacion']),
                'entregas' => array_map(fn (array $entrega) => [
                    $entrega['texto_lugar'] ?? '',
                    $entrega['texto_cantidad'] ?? '',
                    $entrega['texto_direccion'] ?? '',
                ], $textos['entregas']),
            ];
        }

        $bloques = [];

        foreach (array_values($grupos) as $indice => $opciones) {
            $primera = $opciones[0];

            // Una sola opcion: la tarjeta completa, como siempre.
            if (count($opciones) === 1) {
                $bloques[] = ['numero' => $indice + 1, 'campos' => $primera['campos'], 'entregas' => $primera['entregas'], 'opciones' => []];

                continue;
            }

            $iguales = fn (string $clave) => collect($opciones)->every(fn (array $opcion) => ($opcion['campos'][$clave][1] ?? null) === ($primera['campos'][$clave][1] ?? null));
            $siempreEnOpcion = ['cantidad', 'precio'];

            $comunes = array_filter($primera['campos'], fn ($campo, string $clave) => ! in_array($clave, $siempreEnOpcion, true) && $iguales($clave), ARRAY_FILTER_USE_BOTH);
            $entregasIguales = collect($opciones)->every(fn (array $opcion) => $opcion['entregas'] === $primera['entregas']);

            $bloques[] = [
                'numero' => $indice + 1,
                'campos' => $comunes,
                'entregas' => $entregasIguales ? $primera['entregas'] : [],
                'opciones' => array_map(fn (array $opcion) => [
                    'numero' => $opcion['opcion'],
                    'campos' => array_diff_key($opcion['campos'], $comunes),
                    'entregas' => $entregasIguales ? [] : $opcion['entregas'],
                ], $opciones),
            ];
        }

        return $bloques;
    }

    /**
     * Los campos de la tarjeta de la cotizacion, en el orden del Figma:
     * clave => [titulo, texto, ejemplo].
     *
     * @param  array<string, string>  $textos
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    private function camposCotizacion(array $textos): array
    {
        $campos = $this->esDpk
            ? [
                'producto' => ['Producto', 'Doypack galletitas x 300g con zipper'],
                'materiales' => ['Materiales', 'Poliester Cristal de 12 mic + Polietileno Dpk de 130 mic'],
                'impresion' => ['Impresión', '4 colores'],
                'laminacion' => ['Laminación', 'Libre de solventes Apto alimentos'],
                'anchos' => ['Medidas', '15 x 22 cm + fuelle 8 cm'],
                'cantidad' => ['Cantidad', '30.000 envases +/- 10%'],
                'precio' => ['Precio', 'U$S 164,06 por millar + IVA'],
            ]
            : [
                'producto' => ['Producto', 'Flowpack Bilaminado impreso Carrefour Caseras x 700g'],
                'materiales' => ['Materiales', 'Bopp Mate de 20 mic + Pe Blanco 45 mic'],
                'impresion' => ['Impresión', '8 colores con Fotocromía HD'],
                'laminacion' => ['Laminación', 'Libre de solventes Apto alimentos'],
                'anchos' => ['Anchos de bobina', '580 mm Buje 3´´'],
                'paso' => ['Paso', '580 mm'],
                'cantidad' => ['Cantidad', '67.000 kg +/- 10%'],
                'precio' => ['Precio', 'U$S 7,99 por kg + IVA'],
            ];

        return array_map(fn (string $clave) => [$campos[$clave][0], (string) ($textos[$clave] ?? ''), $campos[$clave][1]], array_combine(array_keys($campos), array_keys($campos)));
    }

    /**
     * @return array<string, mixed>
     */
    private function filaResumen(int $numero): array
    {
        $r = $this->tipo_producto === '' ? null : $this->calculoRentabilidad;
        $peso = (float) ($this->bobinas['peso'] ?: 0);
        $envases = (float) ($this->bobinas['envases'] ?: 0);

        // Bobinas se cotizan por kilo y el DPK por millar de envases.
        $unidades = $this->esDpk ? $envases / 1000 : $peso;

        return [
            'numero' => $numero,
            'tipo' => self::TIPOS_PRODUCTO[$this->tipo_producto] ?? 'Sin tipo de producto',
            'producto' => $this->bobinas['producto_id'] ? (string) ContactoProducto::find($this->bobinas['producto_id'])?->nombre : '',
            'cantidad' => $this->esDpk ? Numero::corto($envases, 0).' envases' : Numero::corto($peso).' kg',
            'unidad' => $this->esDpk ? 'millar' : 'kg',
            'unitario' => $r['contado'] ?? null,
            'total' => $r === null ? null : $r['contado'] * $unidades,
            'peso' => $peso,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function pagosVacios(): array
    {
        return array_fill(0, 3, ['valor_kgrs' => '', 'dias_ff' => '', 'ac' => '', 'financiacion' => '', 'costo_total' => '']);
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
        $this->bobinas['materiales'] = $this->materialesCompletos($datos['bobinas']['materiales'] ?? []);
        $this->cotizacion = array_replace($this->cotizacion, $datos['cotizacion'] ?? []);
        $this->oc = array_replace($this->oc, $datos['oc'] ?? []);
        $this->archivosOc = array_values($datos['oc_archivos'] ?? []);
        $this->pagos = array_replace($this->pagos, $datos['pagos'] ?? []);

        if (! empty($datos['entregas'])) {
            $this->entregas = array_map(fn (array $entrega) => array_replace($this->entregaVacia(), $entrega), $datos['entregas']);
        }

        $this->productosExtra = array_values(array_map(function (array $producto) {
            $uid = (string) ($producto['uid'] ?? '') ?: (string) Str::uuid();

            return ['uid' => $uid, 'grupo' => (string) ($producto['grupo'] ?? '') ?: $uid] + $producto;
        }, $datos['productos_extra'] ?? []));

        // Los calculados se rehacen con los datos y parametros de hoy.
        $this->recalcularAnchos();
        $this->recalcularPesoOMetros();
        $this->recalcularScrap();
    }

    /**
     * Guarda la cotizacion (alta o edicion). Se puede guardar a medio
     * completar: solo exige cliente, vendedor y fecha.
     */
    public function guardar(): void
    {
        if ($this->pedirProductos('guardar')) {
            return;
        }

        $this->guardarAhora();
    }

    /**
     * Con productos extra en pantalla, antes de guardar se les pide el ultimo
     * estado: un cambio recien hecho en uno puede no haber llegado todavia.
     * Cuando contestan todos se retoma (ver productoActualizado).
     */
    private function pedirProductos(string $accion): bool
    {
        // Solo en Datos se editan; en las otras solapas ya estan al dia.
        if ($this->productosExtra === [] || $this->solapa !== 'datos') {
            return false;
        }

        // Si falta la cabecera, el error se ve en el momento.
        $this->validarCabecera();

        $this->accionPendiente = $accion;
        $this->esperando = array_column($this->productosExtra, 'uid');
        $this->dispatch('sincronizar-productos');

        return true;
    }

    private function guardarAhora(): void
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
        if ($this->pedirProductos('aprobar')) {
            return;
        }

        $this->aprobarAhora();
    }

    private function aprobarAhora(): void
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
    private function validarCabecera(): void
    {
        $this->validate([
            'cliente_id' => ['required', 'exists:contactos,id'],
            'vendedor_id' => ['required', 'exists:vendedores,id'],
            'fecha' => ['required', 'date'],
            'tipo_producto' => ['nullable', 'string'],
        ], attributes: ['cliente_id' => 'cliente', 'vendedor_id' => 'vendedor', 'fecha' => 'fecha']);
    }

    private function persistir(): void
    {
        $this->validarCabecera();

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
                'oc_archivos' => $this->archivosOc,
                // Los productos que se sumaron con Duplicar / Nuevo producto.
                'productos_extra' => $this->productosExtra,
            ],
        ];

        if ($this->guardada) {
            $antes = array_column($this->guardada->datos['oc_archivos'] ?? [], 'ruta');

            $this->guardada->update($datos);

            // Lo que se quito de la OC ya no lo usa nadie.
            foreach (array_diff($antes, array_column($this->archivosOc, 'ruta')) as $ruta) {
                Storage::disk('local')->delete($ruta);
            }

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
     * Cada archivo que se elige se guarda en el disco privado y se suma a la
     * lista de la OC. Cualquier tipo de archivo; los guarda Guardar.
     */
    public function updatedSubidasOc(): void
    {
        $this->validate(
            ['subidasOc.*' => ['file', 'max:'.self::MAXIMO_ARCHIVO_OC]],
            ['subidasOc.*.max' => 'Cada archivo puede pesar hasta '.(self::MAXIMO_ARCHIVO_OC / 1024).' MB.'],
            ['subidasOc.*' => 'archivo'],
        );

        foreach ($this->subidasOc as $archivo) {
            $this->archivosOc[] = [
                'ruta' => $archivo->store('ordenes-compra', 'local'),
                'nombre' => $archivo->getClientOriginalName(),
                'tamanio' => (int) $archivo->getSize(),
                'subido' => now()->format('d/m/Y H:i'),
            ];
        }

        $this->subidasOc = [];
    }

    public function descargarArchivoOc(int $indice)
    {
        $archivo = $this->archivosOc[$indice] ?? null;

        if ($archivo === null || ! Storage::disk('local')->exists($archivo['ruta'])) {
            $this->addError('subidasOc', 'Ese archivo ya no está disponible.');

            return null;
        }

        return Storage::disk('local')->download($archivo['ruta'], $archivo['nombre']);
    }

    /**
     * Sale de la lista; del disco se borra al guardar (si no se guarda, sigue).
     */
    public function quitarArchivoOc(int $indice): void
    {
        unset($this->archivosOc[$indice]);
        $this->archivosOc = array_values($this->archivosOc);
    }

    public function abrirCanal(): void
    {
        $this->creandoCanal = true;
        $this->nuevoCanal = '';
        $this->resetValidation('nuevoCanal');
    }

    public function cancelarCanal(): void
    {
        $this->creandoCanal = false;
        $this->nuevoCanal = '';
        $this->resetValidation('nuevoCanal');
    }

    /**
     * El canal nuevo queda en Configuración > Ajustes > Canales de OC y elegido.
     */
    public function guardarCanal(): void
    {
        $this->validate(['nuevoCanal' => ['required', 'string', 'max:60']], attributes: ['nuevoCanal' => 'canal']);

        $canal = AjusteTexto::firstOrCreate(['grupo' => AjusteTexto::CANALES_OC, 'texto' => trim($this->nuevoCanal)]);

        $this->oc['canal'] = $canal->texto;
        $this->cancelarCanal();
    }

    /**
     * Enviar pedido: guarda la OC y pasa la cotizacion a Finalizada.
     */
    public function enviarPedido(): void
    {
        $this->validarCabecera();
        $this->validate(['oc.numero' => ['required', 'string', 'max:60']], attributes: ['oc.numero' => 'N° OC']);

        $esNueva = $this->guardada === null;

        $this->persistir();

        if ($this->guardada->estado !== Cotizacion::FINALIZADA) {
            $this->guardada->update(['estado' => Cotizacion::FINALIZADA]);

            $this->guardada->cliente?->actividades()->create([
                'fecha' => now(),
                'descripcion' => 'Se envió el pedido de la cotización '.$this->numero.' (OC '.$this->oc['numero'].')',
                'autor' => $this->guardada->vendedor?->nombre ?? auth()->user()->name,
            ]);
        }

        session()->flash('status', 'Pedido enviado: la cotización '.$this->numero.' pasó a Finalizada.');

        if ($esNueva) {
            $this->redirectRoute('cotizaciones.edit', $this->guardada, navigate: true);
        }
    }

    /**
     * Lo que entra en la OC y en Entrega: todos los formularios de la
     * cotizacion, sean opciones de un producto o productos nuevos, cada uno
     * con sus entregas.
     *
     * @return array<int, array{grupo: string, producto: int, opcion: ?int, etiqueta: string, elegida: string}>
     */
    #[Computed]
    public function productosOrdenCompra(): array
    {
        return array_map(fn (array $item) => [
            'grupo' => $item['grupo'],
            'producto' => $item['producto'],
            'opcion' => $item['opcion'],
            'etiqueta' => $item['etiqueta'],
            // El principal no tiene uid: va con la clave de su grupo.
            'elegida' => $item['uid'] ?: self::GRUPO_PRINCIPAL,
        ], $this->listaProductos);
    }

    /**
     * La tabla de la OC: tipo, producto, caracteristicas, cantidad y precio al
     * contado de cada producto y de cada opcion.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function filasOrdenCompra(): array
    {
        $filas = [];

        foreach ($this->productosOrdenCompra as $producto) {
            $formulario = $this->formularioDe(['uid' => $producto['elegida'] === self::GRUPO_PRINCIPAL ? '' : $producto['elegida']]);
            $resumen = $formulario->filaResumen($producto['producto']);
            $campos = $formulario->camposCotizacion($formulario->textosPropuestos['cotizacion']);

            $caracteristicas = [];
            foreach (['materiales', 'anchos', 'paso', 'impresion', 'laminacion'] as $clave) {
                if (($campos[$clave][1] ?? '') !== '') {
                    $caracteristicas[] = $campos[$clave][0].': '.$campos[$clave][1];
                }
            }

            $filas[] = [
                'tipo' => $resumen['tipo'],
                'producto' => $resumen['producto'] ?: '-',
                'opcion' => $producto['opcion'] !== null ? 'Opción '.$producto['opcion'] : null,
                'caracteristicas' => $caracteristicas,
                'cantidad' => $resumen['cantidad'],
                'precio_unitario' => $resumen['unitario'] === null ? '-' : Numero::usd($resumen['unitario']).' / '.$resumen['unidad'],
                'importe_total' => Numero::usd($resumen['total']),
            ];
        }

        return $filas;
    }

    /**
     * Todas las entregas de la cotizacion, con su fecha pactada: una por cada
     * entrega de cada formulario. Las del principal viven en "entregas" y las
     * de los otros en su propio producto.
     *
     * @return array<int, array{etiqueta: string, lugar: string, modelo: string}>
     */
    #[Computed]
    public function entregasOrdenCompra(): array
    {
        $varios = count($this->productosOrdenCompra) > 1;
        $lista = [];

        foreach ($this->productosOrdenCompra as $producto) {
            $uid = $producto['elegida'] === self::GRUPO_PRINCIPAL ? '' : $producto['elegida'];
            $indice = $uid === '' ? null : collect($this->productosExtra)->search(fn (array $extra) => $extra['uid'] === $uid);
            $entregas = $indice === null ? $this->entregas : ($this->productosExtra[$indice]['entregas'] ?? []);
            $prefijo = $indice === null ? 'entregas' : 'productosExtra.'.$indice.'.entregas';

            foreach ($entregas as $numero => $entrega) {
                $lista[] = [
                    'etiqueta' => ($varios ? 'P'.$producto['producto'].($producto['opcion'] !== null ? ' Op'.$producto['opcion'] : '').' · ' : '').'Entrega '.($numero + 1),
                    'titulo' => ($varios ? $producto['etiqueta'].' · ' : '').'Entrega '.($numero + 1),
                    'lugar' => (string) ($entrega['texto_lugar'] ?? ''),
                    'modelo' => $prefijo.'.'.$numero.'.fecha_entrega',
                ];
            }
        }

        return $lista;
    }

    /**
     * Solapa Entrega: las entregas de todos los formularios de la cotizacion,
     * lo pactado contra lo entregado y la comision que le toca a cada una.
     *
     *   Cumplimiento       = entregada / a entregar
     *   Comision proyectada = comision del producto x a entregar / total del producto
     *   Comision real       = proyectada x cumplimiento
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function entregasDelPedido(): array
    {
        $varios = count($this->productosOrdenCompra) > 1;
        $lista = [];

        foreach ($this->productosOrdenCompra as $producto) {
            [$uid, $prefijo, $entregas] = $this->entregasDeLoComprado($producto);
            $formulario = $this->formularioDe(['uid' => $uid]);

            $total = $formulario->totalARepartir;
            $comision = $formulario->tipo_producto === '' ? null : ($formulario->calculoRentabilidad['comisionTotal'] ?? null);
            $nombre = $formulario->bobinas['producto_id'] ? (string) ContactoProducto::find($formulario->bobinas['producto_id'])?->nombre : '';
            $antes = $varios ? $producto['etiqueta'].' · ' : '';

            foreach ($entregas as $numero => $entrega) {
                $aEntregar = (float) ($entrega['cantidad'] ?: 0);
                $cargada = trim((string) ($entrega['cantidad_entregada'] ?? ''));
                $entregada = $cargada === '' ? null : (float) $cargada;
                $cumplimiento = $entregada !== null && $aEntregar > 0 ? $entregada / $aEntregar * 100 : null;
                $proyectada = $comision !== null && $total > 0 ? $comision * $aEntregar / $total : null;

                $lista[] = [
                    'titulo' => $antes.($numero + 1).'° entrega',
                    'nombre' => $antes.'Entrega '.($numero + 1),
                    'modelo' => $prefijo.'.'.$numero,
                    'producto' => $nombre,
                    'a_entregar' => $aEntregar,
                    'unidad' => $formulario->unidadEntregas,
                    'entregada' => $entregada,
                    'cumplimiento' => $cumplimiento,
                    'proyectada' => $proyectada,
                    'real' => $proyectada !== null && $cumplimiento !== null ? $proyectada * $cumplimiento / 100 : null,
                ];
            }
        }

        return $lista;
    }

    /**
     * Donde viven las entregas de un formulario: las del principal en
     * "entregas" y las de los otros dentro de su producto.
     *
     * @param  array{elegida: string}  $producto
     * @return array{0: string, 1: string, 2: array<int, array<string, mixed>>}
     */
    private function entregasDeLoComprado(array $producto): array
    {
        $uid = $producto['elegida'] === self::GRUPO_PRINCIPAL ? '' : $producto['elegida'];
        $indice = $uid === '' ? false : collect($this->productosExtra)->search(fn (array $extra) => $extra['uid'] === $uid);

        return $indice === false
            ? [$uid, 'entregas', $this->entregas]
            : [$uid, 'productosExtra.'.$indice.'.entregas', $this->productosExtra[$indice]['entregas'] ?? []];
    }

    /**
     * Zona, direccion y codigo postal de la entrega arrancan con lo pactado en
     * Datos; se pueden cambiar si se entrego en otro lado.
     */
    private function completarEntregasDelPedido(): void
    {
        foreach ($this->productosOrdenCompra as $producto) {
            [, $prefijo, $entregas] = $this->entregasDeLoComprado($producto);

            foreach ($entregas as $numero => $entrega) {
                $direccion = ($entrega['direccion_id'] ?? '') ? ContactoDireccion::find($entrega['direccion_id']) : null;
                $propuestos = [
                    'zona' => (string) ($entrega['texto_lugar'] ?? ''),
                    'direccion_entrega' => (string) ($direccion?->direccion ?? ''),
                    'codigo_postal' => (string) ($direccion?->codigo_postal ?? ''),
                ];

                foreach ($propuestos as $campo => $valor) {
                    if (trim((string) ($entrega[$campo] ?? '')) === '' && $valor !== '') {
                        data_set($this, $prefijo.'.'.$numero.'.'.$campo, $valor);
                    }
                }
            }
        }
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

        foreach ($this->productosExtra as $indice => $producto) {
            $this->productosExtra[$indice]['bobinas']['producto_id'] = null;

            foreach ($producto['entregas'] ?? [] as $numero => $entrega) {
                foreach (['flete_zona_id', 'flete_subzona_id', 'direccion_id'] as $campo) {
                    $this->productosExtra[$indice]['entregas'][$numero][$campo] = '';
                }
            }
        }
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
     * Subzonas de la zona elegida (Quilmes -> Bernal, Don Bosco). Si no tiene,
     * el select no se muestra y se cotiza con el precio de la zona.
     */
    public function subzonasDeZona(int|string|null $zonaId): Collection
    {
        return $zonaId
            ? FleteZona::where('zona_padre_id', (int) $zonaId)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
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

        $camposDeAncho = $this->esDpk
            ? ['ancho', 'alto', 'fuelle', 'modulos_ancho', 'calle', 'modulos_desarrollo', 'envases']
            : ['ancho', 'modulos_ancho', 'paso', 'modulos_desarrollo'];

        if (in_array($clave, $camposDeAncho, true)) {
            $this->recalcularAnchos();
        }

        // Los accesorios y los extras entran en el costo del envase, que se arma en cada render.

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
        if (preg_match('/^materiales\.\d+\.extra$/', $clave)) {
            $this->recalcularAnchos();
        }

        if (preg_match('/^materiales\.(\d+)\.material_id$/', $clave, $partes)) {
            $indice = (int) $partes[1];
            $elegido = InsumoItem::whereKey($this->bobinas['materiales'][$indice]['material_id'] ?: 0)
                ->value('proveedor_elegido_id');

            $this->bobinas['materiales'][$indice]['proveedor_id'] = (string) ($elegido ?? '');
        }

        // Cargar el peso o los metros define cual de los dos manda.
        if (in_array($clave, ['peso', 'cantidad'], true) && ! $this->esDpk) {
            $this->bobinas['peso_manda'] = $clave === 'peso' ? '1' : '';
            unset($this->pesoManda);
        }

        // Peso y scrap comparten datos: se recalculan juntos.
        $disparaCalculo = in_array($clave, ['cantidad', 'peso', 'disenos', 'cambios', 'impresion_scrap', 'laminacion_scrap', 'bilaminacion_scrap'], true)
            || preg_match('/^materiales\.\d+\.(material_id|mic|proveedor_id)$/', $clave);

        if ($disparaCalculo) {
            $this->recalcularPesoOMetros();
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

        if ($this->esDpk) {
            $conAncho = (float) ($this->bobinas['alto'] ?: 0) > 0;

            if ((float) ($this->bobinas['ancho'] ?: 0) <= 0 || ! $conAncho) {
                $faltan[] = 'Ancho y Alto';
            }

            if ((float) ($this->bobinas['envases'] ?: 0) <= 0) {
                $faltan[] = 'Envases';
            }
        } else {
            $conAncho = (float) ($this->bobinas['ancho'] ?: 0) > 0 && (float) ($this->bobinas['modulos_ancho'] ?: 0) > 0;

            if (! $conAncho) {
                $faltan[] = 'Ancho y Módulos Ancho';
            }

            if (! $this->pesoManda && (float) ($this->bobinas['cantidad'] ?: 0) <= 0) {
                $faltan[] = 'Cantidad (mts)';
            }
        }

        return $this->faltaCompletar(array_merge($faltan, $this->faltaDeMateriales($conAncho)));
    }

    /**
     * En bobinas los metros son el calculado: hace falta el peso y los kilos
     * por 1000 metros para poder sacarlos.
     */
    public function ayudaCantidad(): ?string
    {
        if ($this->esDpk || ($this->bobinas['cantidad'] ?? '') !== '') {
            return null;
        }

        $conAncho = (float) ($this->bobinas['ancho'] ?: 0) > 0 && (float) ($this->bobinas['modulos_ancho'] ?: 0) > 0;
        $faltan = [];

        if ((float) ($this->bobinas['peso'] ?: 0) <= 0) {
            $faltan[] = 'Peso (kg)';
        }

        if (! $conAncho) {
            $faltan[] = 'Ancho y Módulos Ancho';
        }

        return $this->faltaCompletar(array_merge($faltan, $this->faltaDeMateriales($conAncho)));
    }

    /**
     * Lo que falta del lado de los materiales para tener los kilos por 1000 metros.
     *
     * @return array<int, string>
     */
    private function faltaDeMateriales(bool $conAncho): array
    {
        $elegidos = collect($this->bobinas['materiales'] ?? [])->filter(fn (array $material) => ! empty($material['material_id']));

        if ($elegidos->isEmpty()) {
            return ['el material'];
        }

        // Sin ancho no hay kilos: ahi lo que falta es el ancho, no esto.
        return $conAncho && $this->kgrsPorMilMetros === [] ? ['el mic, o el peso esp. del material en Insumos'] : [];
    }

    /**
     * @param  array<int, string>  $faltan
     */
    private function faltaCompletar(array $faltan): ?string
    {
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
     * La vuelta de la formula anterior: con el peso cargado, los metros que
     * hacen falta para llegar a esos kilos.
     *
     *   Cantidad (mts) = peso x 1000 / Σ Kgrs x 1000 Mts
     */
    private function recalcularCantidad(): void
    {
        unset($this->itemsElegidos, $this->kgrsPorMilMetros);

        $peso = (float) ($this->bobinas['peso'] ?: 0);
        $kgrs = array_sum($this->kgrsPorMilMetros);

        $this->bobinas['cantidad'] = $peso > 0 && $kgrs > 0
            ? $this->sinCerosDeMas(round($peso * 1000 / $kgrs, 2))
            : '';
    }

    /**
     * Rehace el que sale calculado: si el peso lo escribio el usuario se
     * recalculan los metros, y si no, al reves (como la planilla).
     */
    private function recalcularPesoOMetros(): void
    {
        if ($this->pesoManda) {
            $this->recalcularCantidad();

            return;
        }

        $this->recalcularPeso();
    }

    /**
     * En el DPK los metros salen de los envases: el peso nunca manda.
     */
    #[Computed]
    public function pesoManda(): bool
    {
        return ! $this->esDpk && ($this->bobinas['peso_manda'] ?? '') === '1';
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

    private function usd(?float $valor, int $decimales = 2): string
    {
        return Numero::usd($valor, $decimales, prefijo: 'U$S ');
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
            $metros = $this->metrosATrabajar($indice);

            // K: kilos por 1000 m con el ancho del material (refilado + demasia + scrap de su paso).
            // L: con ancho refilado. J: K por los metros.
            $anchoMaterial = $this->anchoMaterial($indice);
            $kgrsLamina = $pesoEsp !== null && $mic > 0 && $anchoMaterial !== null ? $pesoEsp * $mic * $anchoMaterial / 100 : null;
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
        $prepReprint = ($setup / max(Parametro::valor(Parametro::REPRINT_DIVISOR_SETUP), 1)) * $reprint;
        $prodReprint = ($cantidad / $produccion) * $reprint;

        // N20 de la hoja DPK: las horas del reprint tambien se cobran; en bobinas (N19) no.
        $valorMil = $costo['valorMil'] * $imprime;
        if ($this->esDpk) {
            $valorMil = (($prep + $costo['prod'] + $prepReprint + $prodReprint) * $costo['valorHora']) / ($cantidad / 1000) * $imprime;
        }
        $kgrsTotales = array_sum($this->kgrsPorMilMetros);

        return [
            'flag' => $reprint,
            'prep' => $prep,
            'prod' => $costo['prod'] * $imprime,
            'prepReprint' => $prepReprint,
            'prodReprint' => $prodReprint,
            'valorHora' => $costo['valorHora'],
            'valorMil' => $valorMil,
            'valorKg' => $kgrsTotales > 0 ? $valorMil / $kgrsTotales : null,
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
            'valorKg' => (($this->esDpk ? ($colores > 0 ? ($porColor / $colores) * $reprint : 0) : 0) + $porColor * $colores) / $peso * $imprime,
        ];
    }

    /**
     * Total de colores a imprimir (S8 de la planilla):
     * (diseños x colores) + (variedades x cambios).
     */
    private function coloresPorArranque(): float
    {
        $manual = trim((string) ($this->bobinas['colores_total'] ?? ''));

        return $manual === '' ? $this->coloresCalculado() : (float) $manual;
    }

    /**
     * La cuenta, sin el numero escrito a mano.
     */
    private function coloresCalculado(): float
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

        // L22 del DPK: el reprint entra en los gramos por m2.
        if ($this->esDpk) {
            $grsM2 += $reprintGrsM2;
        }

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
     * Pegados de laminacion sin solvente y con solvente.
     *
     * Bobinas: salen del Laminado. Cada pegado une dos laminas (bi = 1,
     * tri = 2) y el Solvente dice como es cada uno: Si = todos con solvente,
     * No = todos sin, Mixto (solo tri) = uno con y otro sin. Monolaminado no
     * tiene pegados.
     *
     * DPK: no elige laminado; sigue como la planilla, Laminación (Simple /
     * Bi. / Tri.) da las pasadas sin solvente y Solvente = Si suma una con.
     *
     * @return array{sinSolvente: int, conSolvente: int}
     */
    private function pasadasDeLaminacion(): array
    {
        if (! $this->esDpk) {
            $pegados = max((int) ($this->bobinas['laminado'] ?? 0) - 1, 0);

            return match ($this->bobinas['solvente'] ?? '') {
                'Si' => ['sinSolvente' => 0, 'conSolvente' => $pegados],
                self::SOLVENTE_MIXTO => $pegados >= 2 ? ['sinSolvente' => 1, 'conSolvente' => 1] : ['sinSolvente' => $pegados, 'conSolvente' => 0],
                default => ['sinSolvente' => $pegados, 'conSolvente' => 0],
            };
        }

        $pasadas = match ($this->bobinas['laminacion'] ?? '') {
            'Simple' => 1,
            'Bi.' => 2,
            'Tri.' => 3,
            default => 0,
        };

        // En la planilla son dos preguntas independientes: cuantas pasadas sin solvente
        // (Laminacion) y si ademas lleva una con solvente (Solvente = Si).
        return [
            'sinSolvente' => $pasadas,
            'conSolvente' => in_array($this->bobinas['solvente'] ?? '', ['Si', self::SOLVENTE_MIXTO], true) ? 1 : 0,
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
        if ($this->esDpk) {
            // L25 del DPK: la mezcla completa por caras y pasadas.
            $slGrsM2 = $slCompuesto === null || $slCatalizador === null ? null
                : (($slCompuesto / 1000 * Parametro::valor(Parametro::DPK_SL_COMPUESTO))
                    + ($slCatalizador / 1000 * Parametro::valor(Parametro::DPK_SL_CATALIZADOR))) * $caras * $sinSolvente;
        } else {
            $slGrsM2 = $slCompuesto === null || $slCatalizador === null ? null
                : ($slCompuesto / 1000 * Parametro::valor(Parametro::SOLVENTLESS_COMPUESTO))
                    + ($slCatalizador / 1000 * Parametro::valor(Parametro::SOLVENTLESS_CATALIZADOR)) * $caras * $sinSolvente;
        }
        $scrapLaminacion = (float) ($this->bobinas['laminacion_scrap'] ?: 0) + (float) ($this->bobinas['bilaminacion_scrap'] ?: 0);
        $slMil = $slGrsM2 === null ? null : $slGrsM2 * (($anchoLamina + $scrapLaminacion) * 10);
        $slKg = $slMil === null ? null : $slMil / $kgrsTotales * $sinSolvente;

        // Fila 25: con solvente.
        $solvCompuesto = $this->precioInsumo('solvente_compuesto');
        $factorSolvente = $this->esDpk ? Parametro::valor(Parametro::DPK_SOLVENTE_FACTOR) : $caras;
        $solvGrsM2 = $solvCompuesto === null ? null : ($solvCompuesto / 1000) * $factorSolvente * $conSolvente;
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
            $subzonaId = $entrega['flete_subzona_id'] ?: null;
            $tramoId = $entrega['flete_tramo_id'] ?: null;

            if (! $zonaId && ! $tramoId) {
                continue;
            }

            $zona = $zonaId ? FleteZona::find($zonaId) : null;
            $subzona = $subzonaId ? FleteZona::where('zona_padre_id', $zonaId)->find($subzonaId) : null;
            $tramo = $tramoId ? FleteTramo::find($tramoId) : null;

            // Con subzona manda su precio; si esa fila esta vacia, el de la zona.
            $precioDe = fn (?FleteZona $de) => $de && $tramo
                ? FletePrecio::where('flete_zona_id', $de->id)->where('flete_tramo_id', $tramo->id)->value('precio')
                : null;
            $pesos = $precioDe($subzona) ?? $precioDe($zona);

            $usd = $pesos !== null && $dolar > 0 ? (float) $pesos / $dolar : null;

            $filas[] = [
                'zona' => $subzona ? $zona?->nombre.' - '.$subzona->nombre : $zona?->nombre,
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
        if ($this->esDpk) {
            return $this->calculoRentabilidadDpk();
        }

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

        // En el DPK la incidencia se mide sobre el precio por millar (S16 de la hoja).
        if ($this->esDpk) {
            $base = $r['contado'] - $r['precioBrutoMillar'] + $r['costoBrutoMillar'];

            return $base > 0 ? $this->num($valorKg * 100 / $base).'%' : '-';
        }

        $base = $r['contado'] - $r['plusKg'] - $r['comisionKg'];

        return $base > 0 ? $this->num($valorKg * 100 / $base).'%' : '-';
    }

    /**
     * Pasa un "valor x 1000 mts" a "valor x millar de envases" (columna R de
     * la hoja DPK): en 1000 m entran 100000 / ancho envases.
     */
    private function porMillar(?float $valorMil): ?float
    {
        $ancho = (float) ($this->bobinas['ancho'] ?: 0);
        $modulos = max((float) ($this->bobinas['modulos_ancho'] ?: 1), 1);

        return $valorMil === null || $ancho <= 0 ? null : $valorMil * $ancho / 100 / $modulos;
    }

    /**
     * Precio por envase del accesorio elegido (zipper, troquel, pico) o de la
     * caja, con el proveedor elegido en Insumos.
     */
    private function precioAccesorio(int|string|null $itemId): ?float
    {
        if (! $itemId) {
            return null;
        }

        $item = InsumoItem::with('precios')->find($itemId);
        $precio = $item?->precios->firstWhere('proveedor_id', $item->proveedor_elegido_id) ?? $item?->precios->first();

        return $precio?->costo === null ? null : (float) $precio->costo;
    }

    private function precioCaja(): ?float
    {
        $item = InsumoItem::with('precios')
            ->where('nombre', 'Caja')
            ->whereHas('familia.insumo', fn ($consulta) => $consulta->where('nombre', 'Varios'))
            ->first();

        return $item === null ? null : $this->precioAccesorio($item->id);
    }

    /**
     * Bloque "Costo Envase" de la hoja DPK (filas 30 a 39): lo que cuesta
     * confeccionar, los accesorios, las cajas, el flete y los extras. Cada
     * renglon en valor x 1000 mts (N), por kilo (P) y por millar (R).
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function calculoEnvase(): ?array
    {
        $ancho = (float) ($this->bobinas['ancho'] ?: 0);
        $envases = (float) ($this->bobinas['envases'] ?: 0);
        $cantidad = (float) ($this->bobinas['cantidad'] ?: 0);
        $peso = (float) ($this->bobinas['peso'] ?: 0);
        $kgrs = array_sum($this->kgrsPorMilMetros);

        if (! $this->esDpk || $ancho <= 0 || $envases <= 0 || $cantidad <= 0 || $kgrs <= 0 || $peso <= 0) {
            return null;
        }

        $zipper = ($this->bobinas['zipper'] ?? '') === 'Si' ? 1 : 0;
        $troquel = ($this->bobinas['troquel'] ?? '') === 'Si' ? 1 : 0;
        $pico = ($this->bobinas['pico'] ?? '') === 'Si' ? 1 : 0;
        $porKg = fn (?float $n) => $n === null ? null : $n / $kgrs;

        // Costo del material ya costeado (P29 de la hoja), base de los scrap de confeccion.
        $costoBobinaKg = $this->costoBobinaKg();
        $renglones = [];

        // Fila 30: confeccionadora. Golpes por minuto segun el ancho, menos los que resta el zipper.
        $confeccion = Operativo::delSector(Operativo::CONFECCION);
        $golpes = (VariableCosto::porTramoDesde(VariableCosto::GOLPES_DOYPACK, $ancho) ?? 0)
            - $zipper * (VariableCosto::porTramoDesde(VariableCosto::GOLPES_ZIPPER, $ancho) ?? 0);
        $horasProd = $golpes > 0 ? $envases / ($golpes * 60) : 0;
        $prep = (float) ($confeccion?->setup_horas ?? 0);
        $confMil = $confeccion === null || $golpes <= 0 ? null : (($prep + $horasProd) * (float) $confeccion->valor_hora) / ($cantidad / 1000);
        $renglones['confeccionadora'] = ['flag' => $golpes, 'prep' => $prep, 'prod' => $horasProd, 'valorHora' => $confeccion?->valor_hora, 'valorMil' => $confMil, 'valorKg' => $porKg($confMil), 'millar' => $this->porMillar($confMil)];

        // Filas 31, 32 y 35: accesorios por millar, con sus unidades de scrap. Se cargan tal cual al millar.
        foreach ([
            'zipper' => [$zipper, $this->bobinas['tipo_zipper_id'] ?? null, Parametro::DPK_SCRAP_ZIPPER],
            'troquel' => [$troquel, $this->bobinas['tipo_troquel_id'] ?? null, Parametro::DPK_SCRAP_TROQUEL],
            'picos' => [$pico, $this->bobinas['tipo_pico_id'] ?? null, Parametro::DPK_SCRAP_PICO],
        ] as $clave => [$flag, $itemId, $parametroScrap]) {
            $precio = $this->precioAccesorio($itemId);
            $scrapUnidades = Parametro::valor($parametroScrap);
            $millar = $flag && $precio !== null ? $precio * (1000 + $scrapUnidades) : ($flag ? null : 0.0);
            $renglones[$clave] = ['flag' => $flag, 'precio' => $precio, 'scrap' => $scrapUnidades, 'valorMil' => $millar === null ? null : $millar / ($ancho / 100), 'valorKg' => $millar === null ? null : $porKg($millar / ($ancho / 100)), 'millar' => $millar];
        }

        // Fila 33: scrap de confeccion, % del costo del material por millar.
        $scrapConfPct = (float) ($confeccion?->scrap_pct ?? 0);
        $scrapConfMil = $costoBobinaKg * $scrapConfPct / 100 / (100000 / $ancho) * 1000;
        $baseScrapConf = $this->millarHastaRefilado() + ($renglones['confeccionadora']['millar'] ?? 0) + ($renglones['zipper']['millar'] ?? 0);
        $renglones['scrap_confeccion'] = ['pct' => $scrapConfPct, 'valorMil' => $scrapConfMil, 'valorKg' => $porKg($scrapConfMil), 'millar' => $baseScrapConf * $scrapConfPct / 100];

        // Fila 34: picotera. "Produccion" son golpes por minuto. Solo si el envase lleva pico.
        $picotera = Operativo::delSector('Picotera');
        $golpesPic = (float) ($picotera?->produccion_mts_hora ?: 0);
        $horasPic = $golpesPic > 0 ? $envases / ($golpesPic * 60) : 0;
        $picMil = $picotera === null || $golpesPic <= 0 ? null : (((float) $picotera->setup_horas + $horasPic) * (float) $picotera->valor_hora) / ($cantidad / 1000);
        $renglones['picotera'] = ['flag' => $pico, 'prep' => $picotera?->setup_horas, 'prod' => $horasPic, 'valorHora' => $picotera?->valor_hora, 'valorMil' => $picMil === null ? null : $picMil * $pico, 'valorKg' => $picMil === null ? null : $porKg($picMil) * $pico, 'millar' => $picMil === null ? null : $this->porMillar($picMil) * $pico];

        // Fila 36: scrap de picotera.
        $scrapPicPct = (float) ($picotera?->scrap_pct ?? 0);
        $scrapPicMil = $costoBobinaKg * $scrapPicPct / 100 / (100000 / $ancho) * 1000 * $pico;
        $baseScrapPic = $baseScrapConf + ($renglones['troquel']['millar'] ?? 0) + $renglones['scrap_confeccion']['millar'] + ($renglones['picotera']['millar'] ?? 0) + ($renglones['picos']['millar'] ?? 0);
        $renglones['scrap_picotera'] = ['pct' => $scrapPicPct, 'valorMil' => $scrapPicMil, 'valorKg' => $porKg($scrapPicMil), 'millar' => $baseScrapPic * $scrapPicPct / 100 * $pico];

        // Fila 37: cajas. Envases por caja segun el ancho.
        $porCaja = VariableCosto::porTramoDesde(VariableCosto::ENVASES_CAJA, $ancho) ?? 0;
        $precioCaja = $this->precioCaja();
        $cajaMillar = $precioCaja === null || $porCaja <= 0 ? null : $precioCaja * 1000 / $porCaja;
        $renglones['caja'] = ['porCaja' => $porCaja, 'precio' => $precioCaja, 'valorMil' => $cajaMillar === null ? null : $cajaMillar / ($ancho / 100), 'valorKg' => $cajaMillar === null ? null : $porKg($cajaMillar / ($ancho / 100)), 'millar' => $cajaMillar];

        // Fila 38: flete de todas las entregas, por kilo y por millar.
        $fleteUsd = array_sum(array_map(fn (array $f) => $f['usd'] ?? 0, $this->calculoFletes));
        $renglones['flete'] = ['usd' => $fleteUsd, 'valorMil' => null, 'valorKg' => $fleteUsd / $peso, 'millar' => $fleteUsd / $envases * 1000];

        // Fila 39: extras, un importe suelto en U$S.
        $extras = ($this->bobinas['extras'] ?? '') === 'Si' ? (float) ($this->bobinas['extras_usd'] ?: 0) : 0.0;
        $renglones['extras'] = ['usd' => $extras, 'valorMil' => null, 'valorKg' => $extras / $peso, 'millar' => $extras / $envases * 1000];

        $envaseKg = array_sum(array_map(fn (array $r) => $r['valorKg'] ?? 0, $renglones));
        $envaseMillar = array_sum(array_map(fn (array $r) => $r['millar'] ?? 0, $renglones));

        return [
            'renglones' => $renglones,
            'costoBobinaKg' => $costoBobinaKg,
            'costoBobinaMillar' => $this->millarHastaRefilado(),
            'costoEnvaseKg' => $envaseKg,
            'costoEnvaseMillar' => $envaseMillar,
        ];
    }

    /**
     * Costo del material por kilo (P29 de la hoja DPK): todos los bloques
     * hasta el scrap, sin el flete (que en el DPK va con el envase).
     */
    private function costoBobinaKg(): float
    {
        return $this->valorKgAcumulado() + ($this->valorKgScrap() ?? 0);
    }

    /**
     * Valor x 1000 mts del material scrap (N28): el % sobre el costo por kilo,
     * llevado a metros.
     */
    private function valorKgScrapDpkMil(): float
    {
        return ($this->valorKgScrap() ?? 0) * array_sum($this->kgrsPorMilMetros);
    }

    /**
     * Suma por millar de los renglones de material, impresion, laminacion,
     * refilado y scrap (R16:R28 de la hoja DPK).
     */
    private function millarHastaRefilado(): float
    {
        $mils = [];

        foreach ($this->calculoProveedores as $fila) {
            $mils[] = $fila['valorMil'] ?? 0;
        }

        $mils[] = $this->calculoReprint['valorMil'] ?? 0;
        $mils[] = $this->calculoTintas['valorMil'] ?? 0;
        $mils[] = $this->calculoLimpieza['valorMil'] ?? 0;
        $mils[] = $this->calculoLaminacion['laminadoMil'] ?? 0;
        $mils[] = $this->calculoLaminacion['slMil'] ?? 0;
        $mils[] = $this->calculoLaminacion['solvMil'] ?? 0;
        $mils[] = $this->calculoRefilado['valorMil'] ?? 0;
        $mils[] = $this->valorKgScrapDpkMil();

        $suma = 0.0;
        foreach ($mils as $mil) {
            $suma += $this->porMillar($mil) ?? 0;
        }

        // La tela va por envases, no por metros (R21 de la hoja).
        $tela = $this->calculoTela;
        $envases = (float) ($this->bobinas['envases'] ?: 0);
        $modulos = max((float) ($this->bobinas['modulos_ancho'] ?: 1), 1);

        if ($tela !== null && $envases > 0) {
            $suma += $this->telaPorMillar();
        }

        return $suma;
    }

    /** R21 de la hoja: (tela del reprint + tela x color x colores) por millar de envases. */
    private function telaPorMillar(): float
    {
        $tela = $this->calculoTela;
        $envases = (float) ($this->bobinas['envases'] ?: 0);
        $modulos = max((float) ($this->bobinas['modulos_ancho'] ?: 1), 1);
        $imprime = ($this->bobinas['impresion'] ?? '') === 'Si' ? 1 : 0;
        $colores = (float) ($this->bobinas['colores'] ?: 0);

        if ($tela === null || $envases <= 0) {
            return 0.0;
        }

        return (($tela['porColorReprint'] + $tela['porColor'] * $colores) / ($envases / 1000) * $imprime) / $modulos;
    }

    /**
     * Rentabilidad del DPK (filas 41 a 50 de la hoja): el precio sale por
     * millar de envases; el valor por kilo es el equivalente.
     *
     * @return array<string, mixed>|null
     */
    private function calculoRentabilidadDpk(): ?array
    {
        $envase = $this->calculoEnvase;
        $peso = (float) ($this->bobinas['peso'] ?: 0);
        $envases = (float) ($this->bobinas['envases'] ?: 0);
        $ancho = (float) ($this->bobinas['ancho'] ?: 0);

        if ($envase === null || $peso <= 0 || $envases <= 0) {
            return null;
        }

        $costoBrutoKg = $envase['costoBobinaKg'] + $envase['costoEnvaseKg'];
        $costoBrutoMillar = $envase['costoBobinaMillar'] + $envase['costoEnvaseMillar'];

        // A42: % plus por ancho (< 18 cm o no) y peso. A43: categoria. A44: comision.
        $margen = VariableCosto::porTramoHasta($ancho < 18 ? VariableCosto::MARGEN_DPK_CHICO : VariableCosto::MARGEN_DPK, $peso);
        $categoriaPct = VariableCosto::categoria($this->categoria);
        $plus = $margen + (Parametro::valor(Parametro::CATEGORIA_EN_PRECIO) > 0 ? $categoriaPct : 0);
        $ajusteManual = (float) $this->ajuste_categoria;
        $comision = (float) (Vendedor::find($this->vendedor_id)?->comision($this->tipo_producto) ?? 0) + (float) $this->ajuste_vendedor;

        $divisor = 1 - ($plus + $comision) / 100;

        $dias = (int) ($this->pagos[1]['dias_ff'] ?? 0);
        $financiacionPct = $dias > 0 ? VariableCosto::financiacion($dias) : 0;
        $divisorContado = 1 - ($plus + $comision * (1 + $financiacionPct / 100)) / 100;

        if ($divisor <= 0 || $divisorContado <= 0) {
            return null;
        }

        // R46 / R48: precio bruto por millar y con ajuste. R49: el contado, con la comision financiada.
        $precioBrutoMillar = $costoBrutoMillar / $divisor;
        $ajusteMillar = $precioBrutoMillar * $ajusteManual / 100;
        $contado = $costoBrutoMillar / $divisorContado + $ajusteMillar;
        $financiacionMillar = $contado * $financiacionPct / 100;

        $plusMillar = $precioBrutoMillar * $plus / 100;
        $comisionMillar = ($precioBrutoMillar + $ajusteMillar) * $comision / 100;

        // Polimeros (G45): ancho lamina x (desarrollo + extra) x colores x arranques x U$S/cm2.
        $anchoLamina = (float) ($this->bobinas['ancho_lamina'] ?: 0);
        $desarrollo = (float) ($this->bobinas['desarrollo'] ?: 0);
        $polimerosUsd = $anchoLamina * ($desarrollo + Parametro::valor(Parametro::POLIMEROS_DESARROLLO_EXTRA)) * $this->coloresPorArranque() * Parametro::valor(Parametro::POLIMEROS_USD_CM2);

        $aKg = fn (float $millar) => $millar * $envases / 1000 / $peso;

        return [
            'esDpk' => true,
            'costoBruto' => $costoBrutoKg,
            'costoBrutoMillar' => $costoBrutoMillar,
            'costoBobinaMillar' => $envase['costoBobinaMillar'],
            'costoEnvaseMillar' => $envase['costoEnvaseMillar'],
            'margen' => $margen,
            'plus' => $plus,
            'categoriaPct' => $categoriaPct,
            'ajusteManual' => $ajusteManual,
            'comision' => $comision,
            'precioBruto' => $aKg($precioBrutoMillar),
            'precioBrutoMillar' => $precioBrutoMillar,
            'contado' => $contado,
            'contadoKg' => $aKg($contado),
            'plusKg' => $aKg($plusMillar),
            'comisionKg' => $aKg($comisionMillar),
            'plusMillar' => $plusMillar,
            'comisionMillar' => $comisionMillar,
            'ajusteMillar' => $ajusteMillar,
            'plusTotal' => $plusMillar * $envases / 1000,
            'comisionTotal' => $comisionMillar * $envases / 1000,
            'rentabilidadTotal' => ($contado - $costoBrutoMillar - $comisionMillar) * $envases / 1000,
            'polimerosUsd' => $polimerosUsd,
            'polimerosKg' => $polimerosUsd / $peso,
            'dias' => $dias,
            'financiacionPct' => $financiacionPct,
            'financiacionKg' => $financiacionMillar,
            'aDiasMillar' => $contado + $financiacionMillar,
            'aDiasKg' => $aKg($contado + $financiacionMillar),
        ];
    }

    /**
     * Bloques del tab de Costos del DPK: los mismos que bobinas mas el costo
     * del envase, con la columna "Valor x millar".
     *
     * @return list<array{titulo: string, columnas: list<string>, filas: list<list<string>>}>
     */
    #[Computed]
    public function seccionesDpk(): array
    {
        $secciones = [];
        $usdM = fn (?float $valor) => $this->usd($valor);

        // Materiales: la fila 15-17 mas el millar.
        $filas = [];
        foreach ($this->calculoProveedores as $fila) {
            $filas[] = $fila === null
                ? array_fill(0, 12, '-')
                : [
                    $this->num(Parametro::valor(Parametro::MATERIAL_FACTOR), 0), $fila['item']->nombre, $this->num($fila['mic'], 0), $this->usd($fila['costo']),
                    $this->num($fila['metros'], 0), $this->num($fila['kgrsTrabajar']), $this->num($fila['kgrsLamina']), $this->num($fila['kgrsRefilado']),
                    $this->usd($fila['valorMil']), $this->usd($fila['valorKg']), $usdM($this->porMillar($fila['valorMil'])), $this->incidencia($this->porMillar($fila['valorMil'])),
                ];
        }
        $secciones[] = ['titulo' => 'Proveedores', 'columnas' => ['', 'Detalle', 'Mic', 'Valor x kgrs', 'Mts a trabajar', 'Kgrs a trabajar', 'Kgrs x 1000 mts', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor x kgrs', 'Valor x millar', 'Incidencia'], 'filas' => $filas];

        // Impresion.
        $imprime = ($this->bobinas['impresion'] ?? '') === 'Si' ? 1 : 0;
        $rep = $this->calculoReprint; $tela = $this->calculoTela; $tin = $this->calculoTintas; $lim = $this->calculoLimpieza;
        $telaMillar = $tela === null ? null : $this->telaPorMillar();
        $secciones[] = ['titulo' => 'Impresión y Reprint', 'columnas' => ['', 'Detalle', 'Prep', 'Prod', 'Valor hs', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor kgrs', 'Valor x millar', 'Incidencia'], 'filas' => [
            [(string) $imprime, 'Reprint', $this->num($rep['prep'] ?? null), $this->num($rep['prod'] ?? null), $this->usd($rep['valorHora'] ?? null), '', $this->usd($rep['valorMil'] ?? null), $this->usd($rep['valorKg'] ?? null), $usdM($this->porMillar($rep['valorMil'] ?? null)), $this->incidencia($this->porMillar($rep['valorMil'] ?? null))],
            [(string) $imprime, 'Tela Doble Fax', '', '', 'x Color U$S', $this->num($tela['porColor'] ?? null, 3), '', $this->usd($tela['valorKg'] ?? null), $usdM($telaMillar), $this->incidencia($telaMillar)],
            [(string) $imprime, 'Tintas + Diluyentes', '', '', 'Valor grs/mts2', $this->num($tin['grsM2'] ?? null, 3), $this->usd($tin['valorMil'] ?? null), $this->usd($tin['valorKg'] ?? null), $usdM($this->porMillar($tin['valorMil'] ?? null)), $this->incidencia($this->porMillar($tin['valorMil'] ?? null))],
            [(string) $imprime, 'Limpieza Diluyentes', (float) ($this->bobinas['cantidad'] ?: 0) > 0 ? $this->num(Parametro::litrosLimpieza((float) $this->bobinas['cantidad']), 0) : '-', '', 'Valor Lts', $this->num($lim['precioLitro'] ?? null, 3), $this->usd($lim['valorMil'] ?? null), $this->usd($lim['valorKg'] ?? null), $usdM($this->porMillar($lim['valorMil'] ?? null)), $this->incidencia($this->porMillar($lim['valorMil'] ?? null))],
        ]];

        // Laminacion.
        $c = $this->calculoLaminacion;
        ['sinSolvente' => $sin, 'conSolvente' => $con] = $this->pasadasDeLaminacion();
        $secciones[] = ['titulo' => 'Laminación y solventes', 'columnas' => ['', 'Detalle', 'Prep', 'Prod', 'Valor hs', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor kgrs', 'Valor x millar', 'Incidencia'], 'filas' => [
            [(string) ($sin + $con), 'Costo laminado', $this->num($c['prep'] ?? null), $this->num($c['prod'] ?? null), $this->usd($c['valorHora'] ?? null), '', $this->usd($c['laminadoMil'] ?? null), $this->usd($c['laminadoKg'] ?? null), $usdM($this->porMillar($c['laminadoMil'] ?? null)), $this->incidencia($this->porMillar($c['laminadoMil'] ?? null))],
            [(string) $sin, 'Sin solvente', '', '', 'Valor grs/mts2', $this->num($c['slGrsM2'] ?? null, 4), $this->usd($c['slMil'] ?? null), $this->usd($c['slKg'] ?? null), $usdM($this->porMillar($c['slMil'] ?? null)), $this->incidencia($this->porMillar($c['slMil'] ?? null))],
            [(string) $con, 'Con solvente', '', '', 'Valor grs/mts2', $this->num($c['solvGrsM2'] ?? null, 4), $this->usd($c['solvMil'] ?? null), $this->usd($c['solvKg'] ?? null), $usdM($this->porMillar($c['solvMil'] ?? null)), $this->incidencia($this->porMillar($c['solvMil'] ?? null))],
        ]];

        // Refilado y scrap.
        $ref = $this->calculoRefilado;
        $scrapMil = $this->valorKgScrapDpkMil();
        $secciones[] = ['titulo' => 'Refilado y Material Scrap', 'columnas' => ['', 'Detalle', 'Prep', 'Prod', 'Valor hs', 'Valor x 1000 mts', 'Valor kgrs', 'Valor x millar', 'Incidencia'], 'filas' => [
            [($this->bobinas['refilado'] ?? 'Si') === 'Si' ? '1' : '0', 'Refilado', $this->num($ref['prep'] ?? null), $this->num($ref['prod'] ?? null), $this->usd($ref['valorHora'] ?? null), $this->usd($ref['valorMil'] ?? null), $this->usd($ref['valorKg'] ?? null), $usdM($this->porMillar($ref['valorMil'] ?? null)), $this->incidencia($this->porMillar($ref['valorMil'] ?? null))],
            [$this->num((float) (Operativo::delSector(Operativo::REBOBINADO)?->scrap_pct ?? 0), 0).'%', 'Material Scrap', '', '', '', $this->usd($scrapMil > 0 ? $scrapMil : null), $this->usd($this->valorKgScrap()), $usdM($scrapMil > 0 ? $this->porMillar($scrapMil) : null), $this->incidencia($scrapMil > 0 ? $this->porMillar($scrapMil) : null)],
        ]];

        // Costo envase.
        $e = $this->calculoEnvase;
        $r = $e['renglones'] ?? [];
        $fila = fn (string $flag, string $detalle, string $c1, string $c2, string $c3, ?array $x) => [$flag, $detalle, $c1, $c2, $c3, $this->usd($x['valorMil'] ?? null), $this->usd($x['valorKg'] ?? null), $usdM($x['millar'] ?? null), $this->incidencia($x['millar'] ?? null)];
        $secciones[] = ['titulo' => 'Costo envase', 'columnas' => ['', 'Detalle', 'Prep / dato', 'Prod / scrap', 'Valor hs / unitario', 'Valor x 1000 mts', 'Valor kgrs', 'Valor x millar', 'Incidencia'], 'filas' => [
            $fila($this->num($r['confeccionadora']['flag'] ?? null, 0), 'Costo confeccionadora', $this->num($r['confeccionadora']['prep'] ?? null), $this->num($r['confeccionadora']['prod'] ?? null), $this->usd($r['confeccionadora']['valorHora'] ?? null), $r['confeccionadora'] ?? null),
            $fila((string) ($r['zipper']['flag'] ?? 0), 'Zipper', 'scrap '.$this->num($r['zipper']['scrap'] ?? null, 0), '', $this->usd($r['zipper']['precio'] ?? null, 4), $r['zipper'] ?? null),
            $fila((string) ($r['troquel']['flag'] ?? 0), 'Troquel', 'scrap '.$this->num($r['troquel']['scrap'] ?? null, 0), '', $this->usd($r['troquel']['precio'] ?? null, 4), $r['troquel'] ?? null),
            $fila($this->num($r['scrap_confeccion']['pct'] ?? null, 0).'%', 'Scrap confeccionadora', '', '', '', $r['scrap_confeccion'] ?? null),
            $fila((string) ($r['picotera']['flag'] ?? 0), 'Costo picotera', $this->num($r['picotera']['prep'] ?? null), $this->num($r['picotera']['prod'] ?? null), $this->usd($r['picotera']['valorHora'] ?? null), $r['picotera'] ?? null),
            $fila((string) ($r['picos']['flag'] ?? 0), 'Picos', 'scrap '.$this->num($r['picos']['scrap'] ?? null, 0), '', $this->usd($r['picos']['precio'] ?? null, 4), $r['picos'] ?? null),
            $fila($this->num($r['scrap_picotera']['pct'] ?? null, 0).'%', 'Scrap picotera', '', '', '', $r['scrap_picotera'] ?? null),
            $fila($this->num($r['caja']['porCaja'] ?? null, 0), 'x Caja', 'envases por caja', '', $this->usd($r['caja']['precio'] ?? null, 4), $r['caja'] ?? null),
            $fila('1', 'Flete', '', '', $this->usd($r['flete']['usd'] ?? null), $r['flete'] ?? null),
            $fila(($this->bobinas['extras'] ?? '') === 'Si' ? '1' : '0', 'Extras', '', '', $this->usd($r['extras']['usd'] ?? null), $r['extras'] ?? null),
        ]];

        return $secciones;
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
        $unidad = $this->esDpk ? 'Millar' : 'Kgrs';
        $filas = [['Valor por '.$unidad.' al contado', '', '', '', $r === null ? '-' : $this->usd($r['contado'])]];

        foreach ($this->pagos as $pago) {
            $dias = (int) ($pago['dias_ff'] ?? 0);

            if ($dias <= 0) {
                continue;
            }

            $pct = VariableCosto::financiacion($dias);
            $financiacion = $r === null ? null : $r['contado'] * $pct / 100;

            $filas[] = [
                'Valor por '.$unidad.' a',
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
        if ($this->esDpk) {
            $this->recalcularMedidasDpk();

            return;
        }

        $ancho = (float) ($this->bobinas['ancho'] ?: 0);
        $modulos = (float) ($this->bobinas['modulos_ancho'] ?: 0);

        // El desarrollo es el paso por los modulos de desarrollo: tiene que dar una manga cargada.
        $paso = (float) ($this->bobinas['paso'] ?: 0);
        $modulosDesarrollo = (float) ($this->bobinas['modulos_desarrollo'] ?: 0);
        $this->bobinas['desarrollo'] = $paso > 0 && $modulosDesarrollo > 0 ? $this->sinCerosDeMas($paso * $modulosDesarrollo) : '';

        if ($ancho <= 0 || $modulos <= 0) {
            $this->bobinas['ancho_refilado'] = '';
            $this->bobinas['ancho_lamina'] = '';
            $this->bobinas['peso'] = '';

            return;
        }

        $refilado = $ancho * $modulos;

        $this->bobinas['ancho_refilado'] = $this->sinCerosDeMas($refilado);
        $this->bobinas['ancho_lamina'] = $this->sinCerosDeMas($refilado + $this->extraMaterial(0));

        $this->recalcularPesoOMetros();
    }

    /**
     * Confeccion DPK. Los calculos de material, impresion y laminacion son
     * los mismos que bobinas; cambian las medidas y se suma el costo del envase.
     */
    #[Computed]
    public function esDpk(): bool
    {
        return $this->tipo_producto === 'confeccion-dpk';
    }

    /**
     * Medidas del DPK (D8:D10, N8, N9, J5 de la hoja): la lamina se arma con
     * el alto x 2 mas el fuelle, y la cantidad de metros sale de los envases
     * por el ancho.
     */
    private function recalcularMedidasDpk(): void
    {
        $ancho = (float) ($this->bobinas['ancho'] ?: 0);
        $alto = (float) ($this->bobinas['alto'] ?: 0);
        $fuelle = (float) ($this->bobinas['fuelle'] ?: 0);
        $modulos = max((float) ($this->bobinas['modulos_ancho'] ?: 1), 1);
        $calle = (float) ($this->bobinas['calle'] ?: 0);
        $modulosDesarrollo = (float) ($this->bobinas['modulos_desarrollo'] ?: 0);
        $envases = (float) ($this->bobinas['envases'] ?: 0);

        // N8 y J5: el paso es el ancho del envase; la cantidad son los envases por el ancho en metros.
        $this->bobinas['paso'] = $ancho > 0 ? $this->sinCerosDeMas($ancho) : '';
        $this->bobinas['cantidad'] = $ancho > 0 && $envases > 0 ? $this->sinCerosDeMas($envases * $ancho / 100) : '';

        // O7 / N9: desarrollo = paso x modulos de desarrollo.
        $this->bobinas['desarrollo'] = $ancho > 0 && $modulosDesarrollo > 0 ? $this->sinCerosDeMas($ancho * $modulosDesarrollo) : '';

        if ($alto <= 0) {
            $this->bobinas['ancho_desplegado'] = '';
            $this->bobinas['ancho_refilado'] = '';
            $this->bobinas['ancho_lamina'] = '';
            $this->bobinas['peso'] = '';
            $this->recalcularScrap();

            return;
        }

        // D8: el envase abierto. D9: por los modulos en el ancho, mas la calle. D10: mas el extra de lamina.
        $desplegado = $alto * 2 + $fuelle;
        $refilado = $desplegado * $modulos + $calle;

        $this->bobinas['ancho_desplegado'] = $this->sinCerosDeMas($desplegado);
        $this->bobinas['ancho_refilado'] = $this->sinCerosDeMas($refilado);
        $this->bobinas['ancho_lamina'] = $this->sinCerosDeMas($refilado + $this->extraMaterial(0));

        $this->recalcularPeso();
        $this->recalcularScrap();
    }

    /**
     * Cm que se le suman al ancho refilado para un material: su "Demasia" si la
     * cargaron, y si no el valor por defecto de Ajustes (2 en bobinas, 3 en DPK).
     */
    public function extraMaterial(int $indice): float
    {
        $cargado = trim((string) ($this->bobinas['materiales'][$indice]['extra'] ?? ''));

        if ($cargado !== '' && is_numeric($cargado)) {
            return (float) $cargado;
        }

        return $this->esDpk ? $this->anchoLaminaExtraDpk : $this->anchoLaminaExtra;
    }

    /**
     * Ancho con el que se compra cada material: ancho refilado + su demasia +
     * el scrap de su paso (material 1 impresion, 2 laminacion, 3 bilaminacion).
     */
    public function anchoMaterial(int $indice): ?float
    {
        $refilado = (float) ($this->bobinas['ancho_refilado'] ?: 0);

        if ($refilado <= 0) {
            return null;
        }

        $scrapFila = ['impresion_scrap', 'laminacion_scrap', 'bilaminacion_scrap'][$indice] ?? null;

        return $refilado + $this->extraMaterial($indice) + (float) ($scrapFila ? ($this->bobinas[$scrapFila] ?: 0) : 0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $guardados
     * @return array<int, array<string, mixed>>
     */
    private function materialesCompletos(array $guardados): array
    {
        $materiales = array_fill(0, self::MATERIALES, self::MATERIAL_VACIO);

        foreach ($guardados as $indice => $material) {
            if (isset($materiales[$indice])) {
                $materiales[$indice] = array_replace(self::MATERIAL_VACIO, $material);
            }
        }

        return $materiales;
    }

    #[Computed]
    public function anchoLaminaExtraDpk(): float
    {
        return Parametro::valor(Parametro::DPK_ANCHO_LAMINA_EXTRA);
    }

    /**
     * El desarrollo del DPK tiene que ser una manga cargada (N9 de la hoja).
     */
    public function ayudaDesarrollo(): ?string
    {
        if (($this->bobinas['desarrollo'] ?? '') === '' || Parametro::valor(Parametro::DPK_MANGA_EXACTA) <= 0) {
            return null;
        }

        $desarrollo = (float) $this->bobinas['desarrollo'];
        $existe = Ajuste::opciones('mangas')->contains(fn (float $manga) => abs($manga - $desarrollo) < 0.001);

        return $existe ? null : 'Manga no disponible ('.$this->sinCerosDeMas($desarrollo).' cm): cargala en Ajustes o revisá el paso y los módulos.';
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
        $this->entregas[$indice]['flete_subzona_id'] = '';
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
     * Lo que se reparte entre las entregas: el Peso (kg) en bobinas y los
     * Envases en el DPK, como se cotiza cada uno.
     */
    #[Computed]
    public function totalARepartir(): float
    {
        return (float) ($this->esDpk ? ($this->bobinas['envases'] ?: 0) : ($this->bobinas['peso'] ?: 0));
    }

    /** "kg" o "envases": la unidad de las entregas. */
    #[Computed]
    public function unidadEntregas(): string
    {
        return $this->esDpk ? 'envases' : 'kg';
    }

    /** El campo de Datos de producto que hay que completar para repartir. */
    #[Computed]
    public function campoARepartir(): string
    {
        return $this->esDpk ? 'Envases (unidades)' : 'Peso (kg)';
    }

    /**
     * Sin peso (o envases) no hay nada que repartir entre las entregas.
     */
    #[Computed]
    public function sinCantidad(): bool
    {
        return $this->totalARepartir <= 0;
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
        return $this->totalARepartir - $this->cantidadRepartida;
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

        return (string) $this->coloresCalculado();
    }

    /**
     * Que falta cargar para el total de colores, como la ayuda del peso.
     */
    public function ayudaColores(): ?string
    {
        if (trim((string) ($this->bobinas['colores_total'] ?? '')) !== '') {
            return 'Total puesto a mano; la cuenta daba '.$this->coloresTotal.'. Borralo para volver al automático.';
        }

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
                $this->bobinas['materiales'][$indice] = self::MATERIAL_VACIO;
            }
        }

        // Monolaminado no tiene pegados: sin liquido ni solvente. Mixto es solo del trilaminado.
        if ($laminado === 1) {
            $this->bobinas['contiene_liquido'] = '';
            $this->bobinas['solvente'] = '';
        } elseif ($laminado === 2 && ($this->bobinas['solvente'] ?? '') === self::SOLVENTE_MIXTO) {
            $this->bobinas['solvente'] = '';
        }

        $this->recalcularPesoOMetros();
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
            'materiales' => array_fill(0, self::MATERIALES, self::MATERIAL_VACIO),
            'bonifica_polimeros' => '',
            'cantidad' => '',
            'peso' => '',
            // '1' cuando el peso lo escribio el usuario y los metros salen de el.
            'peso_manda' => '',
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
            // Vacio: el total de colores sale de la cuenta. Con algo escrito, manda ese numero.
            'colores_total' => '',
            'variedades' => '',
            'cambios' => '',
            'colores' => '',
            'porcentaje_impreso' => '',
            'blanco' => '',
            'ancho_refilado' => '',
            'ancho_lamina' => '',
            // Confeccion DPK: medidas del envase y accesorios.
            'alto' => '',
            'fuelle' => '',
            'envases' => '',
            'ancho_desplegado' => '',
            'calle' => '',
            'zipper' => 'No',
            'tipo_zipper_id' => '',
            'troquel' => 'No',
            'tipo_troquel_id' => '',
            'pico' => 'No',
            'tipo_pico_id' => '',
            'extras' => 'No',
            'extras_usd' => '',
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
            // Vacia: se cotiza con el precio de la zona.
            'flete_subzona_id' => '',
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
            ($this->bobinas['solvente'] ?? '') === self::SOLVENTE_MIXTO => 'Laminación mixta (con y sin solvente)',
            $this->esDpk ? ($this->bobinas['laminacion'] ?? '') !== '' : true => 'Libre de solventes Apto alimentos',
            default => '',
        };

        $entregas = [];
        foreach ($this->entregas as $entrega) {
            $lugar = $entrega['flete_subzona_id'] ?: $entrega['flete_zona_id'];
            $zona = $lugar ? FleteZona::find($lugar)?->nombreCompleto() : null;
            $direccion = $entrega['direccion_id'] ? ContactoDireccion::find($entrega['direccion_id'])?->direccion : null;
            $cantidad = (float) ($entrega['cantidad'] ?: 0);

            $entregas[] = [
                'texto_lugar' => $this->retiroEnSucursal ? self::RETIRO : ($zona ?? ''),
                'texto_cantidad' => $cantidad > 0 ? 'Cantidad '.$num($cantidad).' '.$this->unidadEntregas : '',
                'texto_direccion' => $direccion ? 'Dirección '.$direccion : '',
            ];
        }

        return [
            'cotizacion' => [
                'tipo_producto' => $this->tipo_producto !== '' ? self::TIPOS_PRODUCTO[$this->tipo_producto] ?? '' : '',
                'producto' => $this->bobinas['producto_id'] ? (string) ContactoProducto::find($this->bobinas['producto_id'])?->nombre : '',
                'materiales' => implode(' + ', $materiales),
                'anchos' => $this->esDpk
                    ? ((float) ($this->bobinas['ancho'] ?: 0) > 0 && (float) ($this->bobinas['alto'] ?: 0) > 0
                        ? $num((float) $this->bobinas['ancho'], 1).' x '.$num((float) $this->bobinas['alto'], 1).' cm'.((float) ($this->bobinas['fuelle'] ?: 0) > 0 ? ' + fuelle '.$num((float) $this->bobinas['fuelle'], 1).' cm' : '')
                        : '')
                    : ($anchoRefilado > 0 ? $num($anchoRefilado * 10).' mm'.($buje !== '' ? ' Buje '.$buje.'´´' : '') : ''),
                'paso' => $paso > 0 ? $num($paso * 10).' mm' : '',
                'impresion' => $impresion,
                'laminacion' => $laminacion,
                'cantidad' => $this->esDpk
                    ? ((float) ($this->bobinas['envases'] ?: 0) > 0 ? $num((float) $this->bobinas['envases']).' envases +/- '.$num(Parametro::valor(Parametro::TOLERANCIA_PCT)).'%' : '')
                    : ($peso > 0 ? $num($peso).' kg +/- '.$num(Parametro::valor(Parametro::TOLERANCIA_PCT)).'%' : ''),
                'precio' => $r !== null ? 'U$S '.$num($r['contado'], 2).($this->esDpk ? ' por millar + IVA' : ' por kg + IVA') : '',
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

    /**
     * @return Collection<int, string>
     */
    private function itemsDeInsumo(string $insumo): Collection
    {
        return InsumoItem::whereHas('familia.insumo', fn ($consulta) => $consulta->where('nombre', $insumo))
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    public function render()
    {
        $this->completarPagos();
        $this->completarTextos();

        $vista = $this->uidProducto === null ? 'livewire.cotizaciones.form' : 'livewire.cotizaciones.producto';

        // Los productos extra que se muestran se rearman con el estado que tienen ahora.
        $this->olvidarCalculos();

        // La OC guarda que opcion compro el cliente de cada producto (la primera si no se eligio).
        if ($this->uidProducto === null) {
            // Lo pactado se copia al abrir la solapa: desde ahi es lo que paso de verdad.
            if ($this->solapa === 'entrega') {
                $this->completarEntregasDelPedido();
            }
        }

        $pantalla = view($vista, [
            'estados' => Cotizacion::ESTADOS,
            'clientes' => Contacto::enEstado(Contacto::CLIENTE)
                ->orderBy('razon_social')
                ->get(['id', 'razon_social']),
            'vendedores' => Vendedor::orderBy('nombre')->get(['id', 'nombre']),
            'mangas' => Ajuste::opciones('mangas'),
            'bujes' => Ajuste::opciones('bujes'),
            // Accesorios del DPK: los items de cada insumo.
            'tiposZipper' => $this->itemsDeInsumo('Zipper'),
            'tiposTroquel' => $this->itemsDeInsumo('Troquel'),
            'tiposPico' => $this->itemsDeInsumo('Picos'),
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
        ]);

        return $this->uidProducto === null
            ? $pantalla->title(($this->guardada ? 'Cotización ' : 'Nueva cotización ').$this->numero)
            : $pantalla;
    }
}
