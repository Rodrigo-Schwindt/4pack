<?php

namespace Database\Seeders;

use App\Models\Ajuste;
use Illuminate\Database\Seeder;

/**
 * Valores por defecto de los selects. Idempotente: se puede volver a correr.
 */
class AjustesSeeder extends Seeder
{
    private const VALORES = [
        // Las de la captura mas las de la planilla: PE de 30 a 220 de a 5 (Tabla!A36:E74) y PET 12 y 14.
        'mangas' => [
            12, 14, 30, 35, 36, 38, 40, 42, 44, 45, 47, 48, 50, 52, 54, 55, 56, 58, 60, 64, 65, 66, 70, 75, 80, 85, 90, 95, 100,
            105, 110, 115, 120, 125, 130, 135, 140, 145, 150, 155, 160, 165, 170, 175, 180, 185, 190, 195, 200, 205, 210, 215, 220,
        ],
        'bujes' => [3, 6],
    ];

    public function run(): void
    {
        foreach (self::VALORES as $grupo => $valores) {
            foreach ($valores as $valor) {
                Ajuste::firstOrCreate(['grupo' => $grupo, 'valor' => $valor]);
            }

            $this->command?->info(sprintf('ajustes/%s: %d valores.', $grupo, Ajuste::delGrupo($grupo)->count()));
        }
    }
}
