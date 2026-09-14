<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga los datos del proyecto original a partir del dump de MySQL.
 *
 * Del backup solo se toman las tablas de negocio: cache, sessions, jobs y
 * migrations las maneja Laravel.
 */
class DatabaseSeeder extends Seeder
{
    private const DUMP = __DIR__.'/../dumps/4pack-backup.sql';

    /**
     * En orden de dependencia: primero los catalogos, despues los contactos.
     */
    private const TABLAS = [
        'users',
        'vendedores',
        'rubros',
        'tipos',
        'contactos',
        'contacto_actividades',
    ];

    public function run(): void
    {
        $this->call([AjustesSeeder::class, FleteInsumosSeeder::class, InsumosSeeder::class, OperativosSeeder::class, VariablesCostosSeeder::class]);

        if (! is_file(self::DUMP)) {
            $this->command->warn('No se encontró '.realpath(dirname(self::DUMP)).'/4pack-backup.sql, no hay datos para importar.');

            return;
        }

        $inserts = $this->insertsDelDump(file_get_contents(self::DUMP));

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach (array_reverse(self::TABLAS) as $tabla) {
                DB::table($tabla)->truncate();
            }

            foreach (self::TABLAS as $tabla) {
                foreach ($inserts[$tabla] ?? [] as $insert) {
                    DB::unprepared($insert);
                }

                $this->command->info(sprintf('%s: %d filas.', $tabla, DB::table($tabla)->count()));
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * Sentencias INSERT del dump agrupadas por tabla.
     *
     * @return array<string, list<string>>
     */
    private function insertsDelDump(string $sql): array
    {
        $tablas = implode('|', self::TABLAS);

        preg_match_all('/^INSERT INTO `('.$tablas.')` VALUES .*?;$/ms', $sql, $coincidencias, PREG_SET_ORDER);

        $inserts = [];

        foreach ($coincidencias as [$sentencia, $tabla]) {
            $inserts[$tabla][] = $sentencia;
        }

        return $inserts;
    }
}
