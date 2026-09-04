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
        'mangas' => [35, 36, 38, 40, 42, 44, 45, 47, 48, 50, 52, 54, 56, 58, 60, 64, 66, 70],
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
