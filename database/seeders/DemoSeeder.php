<?php

namespace Database\Seeders;

use App\Livewire\Cotizaciones\Form;
use App\Models\Contacto;
use App\Models\ContactoDireccion;
use App\Models\ContactoProducto;
use App\Models\Cotizacion;
use App\Models\FleteTramo;
use App\Models\FleteZona;
use App\Models\Insumo;
use App\Models\InsumoItem;
use App\Models\InsumoPrecio;
use App\Models\VariableCosto;
use App\Models\Vendedor;
use App\Support\DatosDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostracion para ver el Dashboard y las Estadisticas con
 * movimiento: clientes con sus direcciones y productos, prospectos con su
 * seguimiento y unos cuatro meses de cotizaciones hasta hoy.
 *
 * Las cotizaciones se cargan como lo haria un vendedor (medidas, materiales,
 * cantidades) y el peso, los scrap y los costos los calcula el mismo motor
 * de la cotizacion. Materiales y zonas se buscan por nombre, asi que sirve
 * en cualquier base que tenga cargados los catalogos (InsumosSeeder,
 * FleteInsumosSeeder...).
 *
 * Se puede correr las veces que haga falta: primero borra la demo anterior.
 *
 *   php artisan db:seed --class=DemoSeeder
 *   php artisan demo:borrar
 */
class DemoSeeder extends Seeder
{
    /** Los vendedores del sistema; si falta alguno se crea. */
    private const VENDEDORES = ['Ariel' => 2, 'Fernando' => 3, 'Carlos' => 1, 'Julian' => 2];

    /** Cuantas cotizaciones por mes, del mes en curso hacia atras. */
    private const COTIZACIONES_POR_MES = [12, 14, 11, 8];

    /**
     * Razon social => [localidad, vendedor, celular, direcciones (zona => calle y CP), recetas que compra].
     */
    private const CLIENTES = [
        'Lácteos del Oeste S.A.' => ['Moreno', 'Ariel', '11 5421-3380', ['Moreno' => ['Ruta 7 km 38, Parque Industrial', '1744']], ['bopp-pe', 'pe-simple']],
        'Snacks Patagonia S.R.L.' => ['Pilar', 'Fernando', '11 6034-1172', ['Pilar' => ['Panamericana km 49,5', '1629']], ['mate-metal', 'doypack']],
        'Café Molienda Norte S.A.' => ['San Martín', 'Carlos', '11 4752-9031', ['San Martín' => ['Av. Ricardo Balbín 2450', '1650']], ['trilaminado', 'doypack-pico']],
        'Galletitas Doña Rosa S.A.' => ['Quilmes', 'Julian', '11 4253-8890', ['Quilmes' => ['Av. Calchaquí 3950', '1879'], 'Caba' => ['Av. Juan B. Justo 9200', '1408']], ['bopp-pe', 'pet-pe']],
        'Pastas Frescas del Sur' => ['Avellaneda', 'Ariel', '11 4201-6654', ['Avellaneda' => ['Av. Mitre 1840', '1870']], ['pe-simple', 'pet-pe']],
        'Alimentos Balanceados Pampa' => ['La Plata', 'Fernando', '221 482-1190', ['La Plata' => ['Calle 520 n° 2300', '1900']], ['pe-simple', 'trilaminado']],
        'Golosinas Arcoíris S.A.' => ['Tigre', 'Carlos', '11 4749-2215', ['Tigre' => ['Av. Cazón 1150', '1648']], ['mate-metal', 'doypack']],
        'Infusiones Yerbal S.A.' => ['Caba', 'Julian', '11 4863-7712', ['Caba' => ['Av. Corrientes 4321', '1195']], ['doypack-pico', 'bopp-pe']],
    ];

    /**
     * Razon social => [vendedor, celular, dias desde el alta, dias hasta el seguimiento (null: sin seguimiento)].
     * Los primeros entran en "Nuevos prospectos" del Dashboard (ultimos 7 dias).
     */
    private const PROSPECTOS = [
        'Conservas La Huerta' => ['Ariel', '11 3345-2210', 1, null],
        'Panificados San Telmo' => ['Fernando', '11 5567-8812', 2, 1],
        'Frutos Secos Cuyo S.A.' => ['Carlos', '261 455-7731', 4, null],
        'Bebidas Andinas S.A.' => ['Julian', '11 6012-4498', 6, 2],
        'Condimentos El Gaucho' => ['Ariel', '11 4421-0093', 9, 3],
        'Helados Polo Sur' => ['Fernando', '11 5890-3321', 13, null],
        'Mascotas Felices S.R.L.' => ['Carlos', '11 3876-5540', 17, 4],
        'Arroz del Litoral S.A.' => ['Julian', '343 422-9187', 21, 6],
        'Chocolates Bariloche' => ['Ariel', '294 452-6610', 26, null],
        'Detergentes Brillo S.A.' => ['Fernando', '11 4709-3365', 30, 5],
    ];

    /**
     * Productos que se cotizan: tipo, nombre, laminado, materiales (nombre en
     * Insumos y micraje), los campos de la solapa Datos y el rango de la
     * cantidad (metros en bobinas, envases en el DPK).
     */
    private const RECETAS = [
        'bopp-pe' => [
            'tipo' => 'bobinas', 'producto' => 'Bobina Bopp cristal + PE impresa', 'laminado' => 2,
            'materiales' => [['Bopp Cristal', 20], ['Polietileno Cristal', 40]],
            'campos' => ['ancho' => '52', 'paso' => '60', 'modulos_ancho' => '2', 'modulos_desarrollo' => '1', 'desarrollo' => '60', 'buje' => '3', 'impresion' => 'Si', 'reprint' => 'No', 'refilado' => 'Si', 'contiene_liquido' => 'No', 'solvente' => 'No', 'laminacion' => 'Simple', 'disenos' => '2', 'variedades' => '1', 'cambios' => '1', 'colores' => '8', 'porcentaje_impreso' => '100', 'blanco' => '100', 'impresion_scrap' => '3', 'laminacion_scrap' => '2', 'bilaminacion_scrap' => '0'],
            'cantidad' => [40000, 140000],
        ],
        'pe-simple' => [
            'tipo' => 'bobinas', 'producto' => 'Bobina PE cristal para envasado automático', 'laminado' => 1,
            'materiales' => [['Polietileno Cristal', 60]],
            'campos' => ['ancho' => '35', 'paso' => '45', 'modulos_ancho' => '2', 'modulos_desarrollo' => '1', 'desarrollo' => '45', 'buje' => '3', 'impresion' => 'No', 'reprint' => 'No', 'refilado' => 'Si', 'contiene_liquido' => 'No', 'solvente' => 'No', 'disenos' => '0', 'variedades' => '0', 'cambios' => '0', 'colores' => '0', 'porcentaje_impreso' => '0', 'blanco' => '0', 'impresion_scrap' => '0', 'laminacion_scrap' => '0', 'bilaminacion_scrap' => '0'],
            'cantidad' => [20000, 90000],
        ],
        'mate-metal' => [
            'tipo' => 'bobinas', 'producto' => 'Bobina Bopp mate + Bopp metalizado', 'laminado' => 2,
            'materiales' => [['Bopp Mate', 20], ['Bopp Metalizado', 18]],
            'campos' => ['ancho' => '40', 'paso' => '50', 'modulos_ancho' => '2', 'modulos_desarrollo' => '1', 'desarrollo' => '50', 'buje' => '3', 'impresion' => 'Si', 'reprint' => 'No', 'refilado' => 'Si', 'contiene_liquido' => 'No', 'solvente' => 'No', 'laminacion' => 'Simple', 'disenos' => '1', 'variedades' => '2', 'cambios' => '0', 'colores' => '4', 'porcentaje_impreso' => '60', 'blanco' => '0', 'impresion_scrap' => '3', 'laminacion_scrap' => '2', 'bilaminacion_scrap' => '0'],
            'cantidad' => [30000, 110000],
        ],
        'pet-pe' => [
            'tipo' => 'bobinas', 'producto' => 'Bobina poliéster + PE blanco', 'laminado' => 2,
            'materiales' => [['Poliester Cristal', 12], ['Polietileno Blanco', 70]],
            'campos' => ['ancho' => '45', 'paso' => '55', 'modulos_ancho' => '2', 'modulos_desarrollo' => '1', 'desarrollo' => '55', 'buje' => '3', 'impresion' => 'Si', 'reprint' => 'No', 'refilado' => 'Si', 'contiene_liquido' => 'Si', 'solvente' => 'No', 'laminacion' => 'Simple', 'disenos' => '1', 'variedades' => '1', 'cambios' => '0', 'colores' => '6', 'porcentaje_impreso' => '90', 'blanco' => '80', 'impresion_scrap' => '3', 'laminacion_scrap' => '2', 'bilaminacion_scrap' => '0'],
            'cantidad' => [25000, 80000],
        ],
        'trilaminado' => [
            'tipo' => 'bobinas', 'producto' => 'Bobina trilaminada Bopp / PE / PE', 'laminado' => 3,
            'materiales' => [['Bopp Cristal', 20], ['Polietileno Cristal', 40], ['Polietileno Cristal', 20]],
            'campos' => ['ancho' => '30', 'paso' => '20', 'modulos_ancho' => '2', 'modulos_desarrollo' => '2', 'desarrollo' => '40', 'buje' => '3', 'impresion' => 'Si', 'reprint' => 'Si', 'refilado' => 'Si', 'contiene_liquido' => 'Si', 'solvente' => 'Si', 'laminacion' => 'Simple', 'disenos' => '2', 'variedades' => '2', 'cambios' => '2', 'colores' => '6', 'porcentaje_impreso' => '80', 'blanco' => '30', 'impresion_scrap' => '2', 'laminacion_scrap' => '2', 'bilaminacion_scrap' => '2'],
            'cantidad' => [30000, 70000],
        ],
        'doypack' => [
            'tipo' => 'confeccion-dpk', 'producto' => 'Doypack con zipper 500 g', 'laminado' => 3,
            'materiales' => [['Poliester Cristal', 12], ['Bopp Metalizado', 18], ['Polietileno Cristal', 90]],
            'campos' => ['ancho' => '15', 'paso' => '15', 'modulos_ancho' => '1', 'modulos_desarrollo' => '3', 'desarrollo' => '45', 'alto' => '22', 'fuelle' => '8', 'calle' => '0', 'impresion' => 'Si', 'reprint' => 'No', 'refilado' => 'Si', 'contiene_liquido' => 'No', 'solvente' => 'Si', 'laminacion' => 'Bi.', 'disenos' => '1', 'variedades' => '1', 'cambios' => '0', 'colores' => '8', 'porcentaje_impreso' => '100', 'blanco' => '100', 'zipper' => 'Si', 'troquel' => 'Si', 'pico' => 'No', 'extras' => 'No', 'impresion_scrap' => '3', 'laminacion_scrap' => '2', 'bilaminacion_scrap' => '2'],
            'cantidad' => [20000, 90000],
        ],
        'doypack-pico' => [
            'tipo' => 'confeccion-dpk', 'producto' => 'Doypack con pico 1 kg', 'laminado' => 3,
            'materiales' => [['Poliester Metalizado', 12], ['Polietileno Blanco', 70], ['Polietileno Cristal', 50]],
            'campos' => ['ancho' => '12', 'paso' => '12', 'modulos_ancho' => '2', 'modulos_desarrollo' => '4', 'desarrollo' => '48', 'alto' => '20', 'fuelle' => '6', 'calle' => '0.5', 'impresion' => 'Si', 'reprint' => 'No', 'refilado' => 'Si', 'contiene_liquido' => 'Si', 'solvente' => 'Si', 'laminacion' => 'Bi.', 'disenos' => '2', 'variedades' => '2', 'cambios' => '1', 'colores' => '5', 'porcentaje_impreso' => '70', 'blanco' => '60', 'zipper' => 'Si', 'troquel' => 'Si', 'pico' => 'Si', 'extras' => 'No', 'impresion_scrap' => '3', 'laminacion_scrap' => '2', 'bilaminacion_scrap' => '2'],
            'cantidad' => [15000, 80000],
        ],
    ];

    /** @var array<string, Vendedor> */
    private array $vendedores = [];

    /** @var array<string, array<string, mixed>> recetas con los ids de esta base */
    private array $recetas = [];

    public function run(): void
    {
        // Siempre los mismos datos (relativos a hoy) en cada corrida.
        mt_srand(4);

        $this->recetas = $this->resolverRecetas();

        if ($this->recetas === []) {
            $this->command?->error('No se encontraron los materiales en Insumos: corré primero los seeders de catálogos (php artisan db:seed).');

            return;
        }

        DB::transaction(function () {
            $anterior = DatosDemo::borrar();

            if ($anterior['contactos'] > 0) {
                $this->command?->info(sprintf('Demo anterior borrada: %d contactos y %d cotizaciones.', $anterior['contactos'], $anterior['cotizaciones']));
            }

            $this->cargarVendedores();
            $clientes = $this->cargarClientes();
            $this->cargarProspectos();
            $cotizaciones = $this->cargarCotizaciones($clientes);

            $this->command?->info(sprintf('Demo cargada: %d clientes, %d prospectos y %d cotizaciones.', count($clientes), count(self::PROSPECTOS), $cotizaciones));
        });
    }

    /**
     * Las recetas con los ids de materiales, proveedores y accesorios de esta
     * base. Una receta cuyo material no existe se saltea.
     *
     * @return array<string, array<string, mixed>>
     */
    private function resolverRecetas(): array
    {
        $materiales = InsumoItem::whereHas('familia.insumo', fn ($consulta) => $consulta->where('nombre', Insumo::MATERIALES))
            ->get()
            ->keyBy('nombre');

        $accesorio = fn (string $insumo) => (string) (InsumoItem::whereHas('familia.insumo', fn ($consulta) => $consulta->where('nombre', $insumo))->orderBy('id')->value('id') ?? '');
        $accesorios = ['tipo_zipper_id' => $accesorio('Zipper'), 'tipo_troquel_id' => $accesorio('Troquel'), 'tipo_pico_id' => $accesorio('Picos')];

        $recetas = [];

        foreach (self::RECETAS as $clave => $receta) {
            $lista = [];

            foreach ($receta['materiales'] as [$nombre, $mic]) {
                $item = $materiales[$nombre] ?? null;
                $proveedor = $item?->proveedor_elegido_id ?? ($item ? InsumoPrecio::where('insumo_item_id', $item->id)->orderBy('id')->value('proveedor_id') : null);

                if ($item === null || $proveedor === null) {
                    $this->command?->warn("Se saltea el producto «{$receta['producto']}»: falta el material «{$nombre}» o su proveedor en Insumos.");

                    continue 2;
                }

                $lista[] = ['material_id' => (string) $item->id, 'mic' => (string) $mic, 'proveedor_id' => (string) $proveedor];
            }

            $receta['materiales'] = $lista;
            $receta['campos'] += $receta['tipo'] === 'confeccion-dpk' ? $accesorios : [];
            $recetas[$clave] = $receta;
        }

        return $recetas;
    }

    private function cargarVendedores(): void
    {
        foreach (self::VENDEDORES as $nombre => $comision) {
            $this->vendedores[$nombre] = Vendedor::firstOrCreate(['nombre' => $nombre], [
                'comision_bobinas' => $comision,
                'comision_dpk' => $comision,
                'comision_pouch' => $comision,
                'comision_4_costuras' => $comision,
                'activo' => true,
            ]);
        }
    }

    /**
     * @return list<array{contacto: Contacto, direcciones: list<ContactoDireccion>, productos: array<string, ContactoProducto>}>
     */
    private function cargarClientes(): array
    {
        $alta = now()->subMonths(5);
        $clientes = [];

        foreach (self::CLIENTES as $razonSocial => [$localidad, $vendedor, $celular, $direcciones, $recetas]) {
            $cliente = $this->contacto(Contacto::CLIENTE, $razonSocial, $vendedor, $celular, $localidad, $alta);
            $cliente->actividades()->create(['fecha' => $alta, 'descripcion' => 'Se dio de alta el cliente', 'autor' => $vendedor]);

            $guardadas = [];
            foreach ($direcciones as $zona => [$calle, $codigoPostal]) {
                $guardadas[] = $cliente->direcciones()->create([
                    'flete_zona_id' => FleteZona::firstOrCreate(['nombre' => $zona])->id,
                    'direccion' => $calle,
                    'codigo_postal' => $codigoPostal,
                    'observaciones' => 'Recepción de 8 a 15 hs.',
                ]);
            }

            $productos = [];
            foreach ($recetas as $receta) {
                if (isset($this->recetas[$receta])) {
                    $productos[$receta] = $cliente->productos()->create(['nombre' => $this->recetas[$receta]['producto']]);
                }
            }

            if ($productos !== []) {
                $clientes[] = ['contacto' => $cliente, 'direcciones' => $guardadas, 'productos' => $productos];
            }
        }

        return $clientes;
    }

    private function cargarProspectos(): void
    {
        foreach (self::PROSPECTOS as $razonSocial => [$vendedor, $celular, $dias, $seguimiento]) {
            $alta = now()->subDays($dias)->setTime(10, 0);
            $prospecto = $this->contacto(Contacto::PROSPECTO, $razonSocial, $vendedor, $celular, null, $alta);

            $prospecto->actividades()->create(['fecha' => $alta, 'descripcion' => 'Se creó el prospecto', 'autor' => $vendedor]);

            if ($seguimiento !== null && $seguimiento <= $dias) {
                $prospecto->actividades()->create(['fecha' => $alta->copy()->addDays($seguimiento), 'descripcion' => 'Llamado de seguimiento: se envió presentación y muestras', 'autor' => $vendedor]);
            }
        }
    }

    private function contacto(string $estado, string $razonSocial, string $vendedor, string $celular, ?string $localidad, Carbon $alta): Contacto
    {
        $contacto = Contacto::create([
            'codigo' => Contacto::siguienteCodigo(),
            'estado' => $estado,
            'razon_social' => $razonSocial,
            'nombre_comercial' => $razonSocial,
            'provincia' => 'Buenos Aires',
            'localidad' => $localidad,
            'celular' => $celular,
            'vendedor_id' => $this->vendedores[$vendedor]->id,
            'observaciones' => DatosDemo::MARCA,
        ]);

        $contacto->forceFill(['created_at' => $alta, 'updated_at' => $alta])->save();

        return $contacto;
    }

    /**
     * Cotizaciones de los ultimos meses, en orden de fecha para que la
     * numeracion acompañe.
     *
     * @param  list<array{contacto: Contacto, direcciones: list<ContactoDireccion>, productos: array<string, ContactoProducto>}>  $clientes
     */
    private function cargarCotizaciones(array $clientes): int
    {
        $hoy = today();
        $fechas = [];

        foreach (self::COTIZACIONES_POR_MES as $mesesAtras => $cantidad) {
            $inicio = $hoy->copy()->startOfMonth()->subMonthsNoOverflow($mesesAtras);
            $fin = $mesesAtras === 0 ? $hoy->copy() : $inicio->copy()->endOfMonth()->startOfDay();

            for ($i = 0; $i < $cantidad; $i++) {
                $fechas[] = $inicio->copy()->addDays(mt_rand(0, (int) $inicio->diffInDays($fin)));
            }
        }

        usort($fechas, fn (Carbon $a, Carbon $b) => $a <=> $b);

        $tramos = FleteTramo::orderBy('kg')->get();
        $diasFf = VariableCosto::listado(VariableCosto::FINANCIACION)->pluck('clave')->map(fn ($dias) => (string) $dias)->values()->all();

        foreach ($fechas as $fecha) {
            $cliente = $clientes[mt_rand(0, count($clientes) - 1)];
            $clave = array_rand($cliente['productos']);
            $this->cotizacion($cliente, $clave, $fecha, $tramos, $diasFf);
        }

        return count($fechas);
    }

    /**
     * @param  array{contacto: Contacto, direcciones: list<ContactoDireccion>, productos: array<string, ContactoProducto>}  $cliente
     * @param  \Illuminate\Support\Collection<int, FleteTramo>  $tramos
     * @param  list<string>  $diasFf
     */
    private function cotizacion(array $cliente, string $clave, Carbon $fecha, $tramos, array $diasFf): void
    {
        $receta = $this->recetas[$clave];
        $esDpk = $receta['tipo'] === 'confeccion-dpk';
        $estado = $this->estadoSegunAntiguedad((int) $fecha->diffInDays(today()));
        $cantidad = (string) (mt_rand($receta['cantidad'][0] / 1000, $receta['cantidad'][1] / 1000) * 1000);

        $bobinas = [
            'producto_id' => $cliente['productos'][$clave]->id,
            'laminado' => $receta['laminado'],
            'materiales' => array_pad($receta['materiales'], 3, ['material_id' => '', 'mic' => '', 'proveedor_id' => '']),
            'forma_entrega' => Form::ENVIO,
        ] + $receta['campos'] + ($esDpk ? ['envases' => $cantidad] : ['cantidad' => $cantidad]);

        // Se aprueba a los pocos dias de cotizada, nunca despues de hoy.
        $aprobadaEn = null;

        if (in_array($estado, [Cotizacion::APROBADA, Cotizacion::FINALIZADA], true)) {
            $aprobadaEn = $fecha->copy()->addDays(mt_rand(1, 4))->setTime(mt_rand(9, 17), 0);
            $aprobadaEn = $aprobadaEn->gt(now()) ? now() : $aprobadaEn;
        }

        $cotizacion = Cotizacion::create([
            'numero' => Cotizacion::siguienteNumero($fecha->year),
            'fecha' => $fecha,
            'contacto_id' => $cliente['contacto']->id,
            'vendedor_id' => $cliente['contacto']->vendedor_id,
            'categoria' => ['A', 'B', 'C'][mt_rand(0, 2)],
            'referencia' => $receta['producto'],
            'tipo_producto' => $receta['tipo'],
            'estado' => $estado,
            'aprobada_en' => $aprobadaEn,
            'datos' => ['bobinas' => $bobinas, 'entregas' => [], 'pagos' => [], 'oc' => []],
        ]);

        // El motor de la cotizacion completa peso, metros, anchos y scrap.
        $formulario = new Form;
        $formulario->mount($cotizacion);
        $bobinas = $formulario->bobinas;

        $total = (float) ($esDpk ? ($bobinas['envases'] ?: 0) : ($bobinas['peso'] ?: 0));
        $peso = (float) ($bobinas['peso'] ?: 0);

        $cotizacion->forceFill([
            'created_at' => $fecha->copy()->setTime(mt_rand(9, 17), mt_rand(0, 59)),
            'datos' => [
                'bobinas' => $bobinas,
                'entregas' => $this->entregas($cliente, $estado, $aprobadaEn, $total, $peso, $tramos, $esDpk),
                'pagos' => $this->pagos($diasFf),
                'oc' => $this->ordenCompra($cliente['contacto'], $aprobadaEn),
            ],
        ])->save();

        if ($aprobadaEn !== null) {
            $cliente['contacto']->actividades()->create([
                'fecha' => $aprobadaEn,
                'descripcion' => 'Se aprobó la cotización '.$cotizacion->numero,
                'autor' => $cotizacion->vendedor?->nombre,
            ]);
        }
    }

    /**
     * Las recientes todavia se estan negociando; las viejas ya se cerraron.
     * Algunas pendientes de mas de 7 dias quedan como alertas del Dashboard.
     */
    private function estadoSegunAntiguedad(int $dias): string
    {
        $azar = mt_rand(1, 100);

        return match (true) {
            $dias <= 7 => $azar <= 55 ? Cotizacion::PENDIENTE : Cotizacion::APROBADA,
            $dias <= 25 => match (true) {
                $azar <= 20 => Cotizacion::PENDIENTE,
                $azar <= 80 => Cotizacion::APROBADA,
                default => Cotizacion::RECHAZADA,
            },
            default => match (true) {
                $azar <= 4 => Cotizacion::PENDIENTE,
                $azar <= 50 => Cotizacion::FINALIZADA,
                $azar <= 82 => Cotizacion::APROBADA,
                default => Cotizacion::RECHAZADA,
            },
        };
    }

    /**
     * Una o dos entregas que reparten el pedido. Lo aprobado se entrega a los
     * 15 y 30 dias; lo finalizado ya se entrego todo.
     *
     * @param  array{contacto: Contacto, direcciones: list<ContactoDireccion>, productos: array<string, ContactoProducto>}  $cliente
     * @param  \Illuminate\Support\Collection<int, FleteTramo>  $tramos
     * @return list<array<string, string>>
     */
    private function entregas(array $cliente, string $estado, ?Carbon $aprobadaEn, float $total, float $peso, $tramos, bool $esDpk): array
    {
        if ($total <= 0) {
            return [];
        }

        $partes = mt_rand(0, 1) === 1 ? [0.6, 0.4] : [1.0];
        $hoy = today();
        $entregas = [];

        foreach ($partes as $numero => $proporcion) {
            $direccion = $cliente['direcciones'][$numero % count($cliente['direcciones'])];
            $cantidad = round($total * $proporcion, $esDpk ? 0 : 2);
            $kilos = $peso * $proporcion;
            $tramo = $tramos->first(fn (FleteTramo $tramo) => (float) $tramo->kg >= $kilos) ?? $tramos->last();

            $pactada = $aprobadaEn?->copy()->startOfDay()->addDays($estado === Cotizacion::FINALIZADA ? 7 * ($numero + 1) : 15 * ($numero + 1));
            $entregada = $pactada !== null && ($estado === Cotizacion::FINALIZADA || $pactada->lte($hoy));
            $real = $entregada ? $pactada->copy()->addDays(mt_rand(-2, 3)) : null;

            if ($real !== null && $real->gt($hoy)) {
                $real = $hoy->copy();
            }

            $entregas[] = [
                'flete_zona_id' => (string) $direccion->flete_zona_id,
                'direccion_id' => (string) $direccion->id,
                'cantidad' => (string) $cantidad,
                'flete_tramo_id' => (string) ($tramo?->id ?? ''),
                'fecha_entrega' => $pactada?->format('Y-m-d') ?? '',
                'cantidad_entregada' => $entregada ? (string) round($cantidad * mt_rand(92, 100) / 100, $esDpk ? 0 : 2) : '',
                'fecha_real' => $real?->format('Y-m-d') ?? '',
                'zona' => $entregada ? (string) $direccion->zona?->nombre : '',
                'direccion_entrega' => $entregada ? $direccion->direccion : '',
                'codigo_postal' => $entregada ? (string) $direccion->codigo_postal : '',
            ];
        }

        return $entregas;
    }

    /**
     * Contado y una o dos opciones a plazo; los importes los completa la cotizacion al abrirla.
     *
     * @param  list<string>  $diasFf
     * @return list<array<string, string>>
     */
    private function pagos(array $diasFf): array
    {
        $vacio = ['valor_kgrs' => '', 'dias_ff' => '', 'ac' => '', 'financiacion' => '', 'costo_total' => ''];

        return [
            ['valor_kgrs' => 'Si'] + $vacio,
            ['valor_kgrs' => 'Si', 'dias_ff' => $diasFf[0] ?? ''] + $vacio,
            ['valor_kgrs' => mt_rand(0, 1) === 1 ? 'Si' : '', 'dias_ff' => $diasFf[count($diasFf) - 1] ?? ''] + $vacio,
        ];
    }

    /**
     * Siete de cada diez aprobadas llegan con la orden de compra cargada.
     *
     * @return array<string, string>
     */
    private function ordenCompra(Contacto $cliente, ?Carbon $aprobadaEn): array
    {
        if ($aprobadaEn === null || mt_rand(1, 10) > 7) {
            return ['canal' => '', 'fecha_recibo' => '', 'quien' => '', 'numero' => ''];
        }

        return [
            'canal' => 'Whats app',
            'fecha_recibo' => $aprobadaEn->format('Y-m-d'),
            'quien' => 'Compras '.strtok($cliente->razon_social, ' '),
            'numero' => sprintf('OC-%04d/%s', mt_rand(1000, 9999), $aprobadaEn->format('y')),
        ];
    }
}
