<?php

namespace Database\Seeders;

use App\Livewire\Fletes\Index;
use App\Models\Ajuste;
use App\Models\FletePrecio;
use App\Models\FleteTramo;
use App\Models\FleteZona;
use Illuminate\Database\Seeder;

/**
 * Tramos, zonas y precios del catalogo de flete de insumos. Idempotente.
 */
class FleteInsumosSeeder extends Seeder
{
    private const DOLAR = 1515;

    /** kg => pallets, en el orden en que salen como columnas. */
    private const TRAMOS = [
        900 => 2,
        3500 => 6,
        7000 => 10,
        10000 => 15,
    ];

    /** zona => precio en pesos de cada tramo, en el mismo orden. */
    private const ZONAS = [
        'Quilmes' => [53146, 68400, 68400, 120000],
        'Avellaneda' => [81000, 107600, 107600, 180000],
        'Caba' => [99600, 165600, 165600, 270000],
        'La Plata' => [99600, 165600, 165600, 270000],
        'Munro' => [111900, 186400, 186400, 298000],
        'San Martín' => [111900, 186400, 186400, 298000],
        'Boulogne' => [124200, 198800, 198800, 315000],
        'Ituzaingo' => [124200, 198800, 198800, 315000],
    ];

    public function run(): void
    {
        if (Ajuste::valorDe(Index::GRUPO_DOLAR) <= 0) {
            Ajuste::definir(Index::GRUPO_DOLAR, self::DOLAR);
        }

        $tramos = [];

        foreach (self::TRAMOS as $kg => $pallets) {
            $tramos[] = FleteTramo::firstOrCreate(['kg' => $kg, 'pallets' => $pallets]);
        }

        foreach (self::ZONAS as $nombre => $precios) {
            $zona = FleteZona::firstOrCreate(['nombre' => $nombre]);

            foreach ($precios as $indice => $precio) {
                FletePrecio::updateOrCreate(
                    ['flete_zona_id' => $zona->id, 'flete_tramo_id' => $tramos[$indice]->id],
                    ['precio' => $precio],
                );
            }
        }

        $this->command?->info(sprintf('flete: %d zonas, %d tramos.', FleteZona::count(), FleteTramo::count()));
    }
}
