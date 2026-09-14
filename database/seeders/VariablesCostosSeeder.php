<?php

namespace Database\Seeders;

use App\Models\VariableCosto;
use Illuminate\Database\Seeder;

/**
 * Porcentajes de la hoja "Tabla" de la planilla. Idempotente.
 */
class VariablesCostosSeeder extends Seeder
{
    private const VALORES = [
        VariableCosto::MARGEN => [
            'Pe' => 7.5,
            'Bopp 20+20' => 17.5,
            'Bopp+Pe' => 25,
            'Pet+Pe' => 25,
            'Papel Seda' => 20,
            'Trilaminado' => 30,
        ],
        VariableCosto::VOLUMEN => [
            '1000' => 2,
            '2500' => 0,
            '5000' => -2.5,
            VariableCosto::RESTO => -5,
        ],
        VariableCosto::CATEGORIA => [
            'A' => 0,
            'B' => 1.5,
            'C' => 0,
            'OTRO' => 0,
        ],
        VariableCosto::FINANCIACION => [
            '30' => 4.1667,
            '45' => 6.383,
            '60' => 8.6957,
        ],
    ];

    public function run(): void
    {
        foreach (self::VALORES as $tipo => $filas) {
            foreach ($filas as $clave => $valor) {
                VariableCosto::firstOrCreate(['tipo' => $tipo, 'clave' => (string) $clave], ['valor' => $valor]);
            }
        }

        $this->command?->info(sprintf('variables costos: %d filas.', VariableCosto::count()));
    }
}
