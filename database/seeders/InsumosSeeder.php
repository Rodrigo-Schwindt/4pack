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

    public function run(): void
    {
        foreach (self::INSUMOS as $nombre => $singular) {
            Insumo::firstOrCreate(['nombre' => $nombre], ['singular' => $singular]);
        }

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
                            'costo_mas_1tn' => $mas1tn,
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

        $this->command?->info(sprintf(
            'insumos: %d, proveedores: %d, %s: %d.',
            Insumo::count(),
            Proveedor::count(),
            'materiales',
            InsumoItem::count(),
        ));
    }
}
