<?php

namespace Database\Seeders;

use App\Models\Insumo;
use App\Models\InsumoItem;
use App\Models\InsumoPrecio;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

/**
 * Catalogo de insumos y, para Materiales, las familias y precios de la maqueta.
 * Idempotente.
 */
class InsumosSeeder extends Seeder
{
    /** nombre => singular */
    private const INSUMOS = [
        'Materiales' => 'Material',
        'Tintas' => 'Tinta',
        'Adhesivos' => 'Adhesivo',
        'Diluyentes' => 'Diluyente',
        'Tela' => 'Tela',
        'Zipper' => 'Zipper',
        'Picos' => 'Pico',
    ];

    /**
     * familia => [proveedores, items => [proveedor => [costo, mas_1tn, flete, donde, costo_flete], ...]].
     * El proveedor marcado con '*' es el elegido de ese item.
     */
    private const MATERIALES = [
        'Polietileno' => [
            'proveedores' => ['Polifilm', 'Inepol', 'Rotograbados', 'Plastiandino'],
            'items' => [
                'Polietileno Cristal' => [
                    'Polifilm' => [3.35, 3.35, true, 'JLSuarez', 0.04],
                    'Inepol*' => [3.20, null, false, null, null],
                    'Plastiandino' => [4.25, null, false, null, null],
                ],
                'Polietileno Blanco' => [
                    'Polifilm' => [3.50, 3.50, true, 'JLSuarez', 0.04],
                    'Inepol*' => [3.40, null, false, null, null],
                    'Plastiandino' => [4.35, null, false, null, null],
                ],
                'Polietileno Dpk' => [
                    'Polifilm*' => [3.50, 3.50, false, null, null],
                ],
                'Polietileno EVOH ctal' => [],
                'Polietileno EVOH Bco' => [
                    'Rotograbados*' => [8.00, null, false, null, null],
                    'Plastiandino' => [5.21, null, false, null, null],
                ],
            ],
        ],
        'Poliester' => [
            'proveedores' => ['Silvapack', 'Enimar', 'Osda', 'Alufilm'],
            'items' => [
                'Poliester Cristal' => [
                    'Silvapack*' => [3.65, null, true, 'JLSuarez', 0.04],
                    'Enimar' => [3.60, null, false, null, null],
                ],
                'Poliester Saran' => [],
                'Poliester Mate' => [],
                'Poliester Metalizado' => [
                    'Silvapack*' => [4.15, null, false, null, null],
                ],
            ],
        ],
    ];

    /**
     * Insumos de impresion y laminacion que usa el tab de Costos, con los
     * precios de la hoja "Tabla". La planilla solo nombra a Flint (tintas);
     * el resto queda bajo "A definir" hasta que se cargue el proveedor real.
     *
     * insumo => [familia => [proveedor, [item => precio]]]
     */
    private const IMPRESION = [
        'Tela' => ['Telas' => ['A definir', ['Tela Doble Fax' => 399]]],
        'Tintas' => ['Tintas' => ['Flint', ['Costo tintas' => 16.1252, 'Tinta blanca' => 4.5965, 'Barniz' => 5.863]]],
        'Diluyentes' => ['Diluyentes' => ['A definir', ['DY Alargue' => 1.95, 'DY Limpieza' => 1.25, 'DY Recup' => 1.80]]],
        'Adhesivos' => [
            'Solvent less' => ['A definir', ['Compuesto solvent less' => 6.8, 'Catalizador solvent less' => 7.2]],
            'Solvente' => ['A definir', ['Compuesto solvente' => 11.13]],
        ],
    ];

    /**
     * Hoja "Tabla" A16:D33 de la planilla: peso especifico y precio USD de cada
     * material. El proveedor de Bopp es Vitopel (Bobina!B4); para el resto la
     * planilla no lo dice y quedan bajo "A definir".
     *
     * familia => [proveedor, [item => [peso esp, precio]]]
     */
    private const MATERIALES_TABLA = [
        'Polietileno' => [null, [
            'Polietileno Cristal' => [0.92, null], 'Polietileno Blanco' => [0.92, null], 'Polietileno Dpk' => [0.92, null],
            'Polietileno EVOH ctal' => [1, null], 'Polietileno EVOH Bco' => [1, null],
        ]],
        'Poliester' => [null, [
            'Poliester Cristal' => [1.41, null], 'Poliester Saran' => [1.41, null], 'Poliester Metalizado' => [1.41, null],
        ]],
        'Bopp' => ['Vitopel', [
            'Bopp Cristal' => [0.91, 4.30], 'Bopp Mate' => [0.91, 4.42], 'Bopp Metalizado' => [0.91, 5.20],
            'Bopp Blanco' => [0.95, 4.42], 'Bopp Perlado Blanco' => [1.1, 4.42],
        ]],
        'Cast' => ['A definir', ['Cast Cristal' => [0.91, null], 'Cast Metalizado' => [0.91, null]]],
        'Foil' => ['A definir', ['Foil Aluminio' => [2.7, 8.505]]],
        'Papel' => ['A definir', ['Papel Seda' => [1.1, null]]],
    ];

    /**
     * Lista de proveedores de la planilla (Tabla!F15:F29): es el desplegable
     * que usaba la hoja Bobina para elegir proveedor.
     */
    private const PROVEEDORES = [
        'PlastiAndino', 'Ingepol', 'Mateu', 'Polifilm', 'Plastipren', 'Silvapack', 'Enimar',
        'Osda', 'Dalfilm', 'Alufilm', 'Vitopel', 'Copyfilm', 'Tratitran',
    ];

    /**
     * Bloque VARIOS de la planilla (Tabla!A132:E133): precio unitario en USD.
     * Caja y Troquel no tienen insumo propio todavia.
     */
    private const VARIOS = [
        'Zipper' => ['Zipper' => ['A definir', ['Zipper' => 0.0448]]],
        'Picos' => ['Picos' => ['A definir', ['Pico' => 0.0302]]],
    ];

    public function run(): void
    {
        foreach (self::INSUMOS as $nombre => $singular) {
            Insumo::firstOrCreate(['nombre' => $nombre], ['singular' => $singular]);
        }

        foreach (self::PROVEEDORES as $nombre) {
            Proveedor::firstOrCreate(['nombre' => $nombre]);
        }

        $this->cargarImpresion();
        $this->cargarPorFamilia(self::VARIOS);

        $materiales = Insumo::where('nombre', 'Materiales')->firstOrFail();

        foreach (self::MATERIALES as $nombreFamilia => $definicion) {
            $familia = $materiales->familias()->firstOrCreate(['nombre' => $nombreFamilia]);

            $proveedores = collect($definicion['proveedores'])
                ->mapWithKeys(fn (string $nombre) => [$nombre => Proveedor::firstOrCreate(['nombre' => $nombre])]);

            $familia->proveedores()->syncWithoutDetaching($proveedores->pluck('id')->all());

            foreach ($definicion['items'] as $nombreItem => $precios) {
                $item = InsumoItem::firstOrCreate([
                    'insumo_familia_id' => $familia->id,
                    'nombre' => $nombreItem,
                ]);

                foreach ($precios as $clave => [$costo, $mas1tn, $flete, $donde, $costoFlete]) {
                    $elegido = str_ends_with($clave, '*');
                    $proveedor = $proveedores[rtrim($clave, '*')];

                    InsumoPrecio::updateOrCreate(
                        ['insumo_item_id' => $item->id, 'proveedor_id' => $proveedor->id],
                        [
                            'costo' => $costo,
                            'costo_volumen' => $mas1tn,
                            'flete' => $flete,
                            'donde' => $donde,
                            'costo_flete' => $costoFlete,
                        ],
                    );

                    if ($elegido) {
                        $item->update(['proveedor_elegido_id' => $proveedor->id]);
                    }
                }
            }
        }

        $this->cargarMaterialesDeLaTabla($materiales);

        $this->command?->info(sprintf(
            'insumos: %d, proveedores: %d, %s: %d.',
            Insumo::count(),
            Proveedor::count(),
            'materiales',
            InsumoItem::count(),
        ));
    }

    /**
     * Peso especifico de todos los materiales y las familias que faltaban.
     * Solo completa lo vacio: no pisa lo editado desde la pantalla.
     */
    private function cargarMaterialesDeLaTabla(Insumo $materiales): void
    {
        foreach (self::MATERIALES_TABLA as $nombreFamilia => [$nombreProveedor, $items]) {
            $familia = $materiales->familias()->firstOrCreate(['nombre' => $nombreFamilia]);
            $proveedor = $nombreProveedor ? Proveedor::firstOrCreate(['nombre' => $nombreProveedor]) : null;

            if ($proveedor) {
                $familia->proveedores()->syncWithoutDetaching([$proveedor->id]);
            }

            foreach ($items as $nombreItem => [$pesoEsp, $precio]) {
                $item = InsumoItem::firstOrCreate(['insumo_familia_id' => $familia->id, 'nombre' => $nombreItem]);

                if ($item->peso_especifico === null) {
                    $item->update(['peso_especifico' => $pesoEsp]);
                }

                if ($proveedor && $precio !== null) {
                    InsumoPrecio::firstOrCreate(
                        ['insumo_item_id' => $item->id, 'proveedor_id' => $proveedor->id],
                        ['costo' => $precio],
                    );

                    if ($item->proveedor_elegido_id === null) {
                        $item->update(['proveedor_elegido_id' => $proveedor->id]);
                    }
                }
            }
        }
    }

    private function cargarImpresion(): void
    {
        $this->cargarPorFamilia(self::IMPRESION);
    }

    /**
     * @param  array<string, array<string, array{0: string, 1: array<string, float>}>>  $definicion
     */
    private function cargarPorFamilia(array $definicion): void
    {
        foreach ($definicion as $nombreInsumo => $familias) {
            $insumo = Insumo::where('nombre', $nombreInsumo)->firstOrFail();

            foreach ($familias as $nombreFamilia => [$nombreProveedor, $items]) {
                $familia = $insumo->familias()->firstOrCreate(['nombre' => $nombreFamilia]);
                $proveedor = Proveedor::firstOrCreate(['nombre' => $nombreProveedor]);
                $familia->proveedores()->syncWithoutDetaching([$proveedor->id]);

                foreach ($items as $nombreItem => $precio) {
                    $item = InsumoItem::firstOrCreate(['insumo_familia_id' => $familia->id, 'nombre' => $nombreItem]);

                    InsumoPrecio::firstOrCreate(
                        ['insumo_item_id' => $item->id, 'proveedor_id' => $proveedor->id],
                        ['costo' => $precio],
                    );

                    if ($item->proveedor_elegido_id === null) {
                        $item->update(['proveedor_elegido_id' => $proveedor->id]);
                    }
                }
            }
        }
    }
}
