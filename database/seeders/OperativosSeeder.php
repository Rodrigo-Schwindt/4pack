<?php

namespace Database\Seeders;

use App\Models\Operativo;
use Illuminate\Database\Seeder;

/**
 * Costos operativos de la hoja "Tabla" de la planilla. Idempotente: no pisa
 * lo que ya se edito desde la pantalla.
 */
class OperativosSeeder extends Seeder
{
    /** sector => [valor hora U$S, mts por hora, setup hs, scrap %] */
    private const SECTORES = [
        Operativo::IMPRESION => [58.46, 20000, 1.5, 0],
        Operativo::LAMINACION => [41.35, 16000, 0.5, 0],
        Operativo::REBOBINADO => [39.54, 8000, 0, 5],
        Operativo::CONFECCION => [60.01, null, 0, 15],
        // Tabla!A9:E10. Picotera: la planilla dice 20 de produccion (golpes por minuto, no metros).
        'Troquel' => [0, null, 0, 0],
        'Picotera' => [20.00, 20, 0.5, 5],
    ];

    public function run(): void
    {
        foreach (self::SECTORES as $sector => [$valorHora, $produccion, $setup, $scrap]) {
            Operativo::firstOrCreate(['sector' => $sector], [
                'valor_hora' => $valorHora,
                'produccion_mts_hora' => $produccion,
                'setup_horas' => $setup,
                'scrap_pct' => $scrap,
            ]);
        }

        $this->command?->info(sprintf('operativos: %d sectores.', Operativo::count()));
    }
}
