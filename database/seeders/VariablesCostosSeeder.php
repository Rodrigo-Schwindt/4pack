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
        // Tabla!A139:I155 de la planilla: golpes por minuto segun el ancho del envase (en cm).
        VariableCosto::GOLPES_DOYPACK => [
            '10' => 70, '12' => 70, '12.5' => 70, '13' => 70, '14' => 70, '15' => 65, '16' => 65, '17' => 65,
            '18' => 50, '20' => 50, '22' => 50, '25' => 40, '27' => 40, '29' => 40, '30' => 40, '34' => 40, '38' => 30,
        ],
        VariableCosto::GOLPES_POUCH => [
            '10' => 75, '12' => 75, '12.5' => 75, '13' => 75, '14' => 75, '15' => 70, '16' => 70, '17' => 70,
            '18' => 65, '20' => 65, '22' => 65, '25' => 60, '27' => 60, '29' => 60, '30' => 60, '34' => 60, '38' => 50,
        ],
        VariableCosto::GOLPES_ZIPPER => ['10' => 5],
        // Tabla!B216:I220: % plus de confeccion segun el peso. La hoja DPK usa las filas "Pouche"
        // (218 y 220) para los dos anchos; la fila "Dpk" (64,8 -> 48) no la referencia ninguna formula.
        VariableCosto::MARGEN_DPK_CHICO => [
            '500' => 51.3, '1000' => 49.4, '1500' => 47.5, '2000' => 45.6, '2500' => 43.7, '3000' => 41.8, '4000' => 39.9, '12000' => 38,
            VariableCosto::RESTO => 38,
        ],
        VariableCosto::MARGEN_DPK => [
            '500' => 51.3, '1000' => 49.4, '1500' => 47.5, '2000' => 45.6, '2500' => 43.7, '3000' => 41.8, '4000' => 39.9, '12000' => 38,
            VariableCosto::RESTO => 38,
        ],
        // A37 de la hoja DPK: envases por caja segun el ancho.
        VariableCosto::ENVASES_CAJA => ['0' => 2000, '12' => 1500, '15' => 1000, '22.1' => 400],
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
