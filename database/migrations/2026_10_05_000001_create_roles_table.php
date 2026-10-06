<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles con sus permisos, y el rol y el estado de cada usuario. Los
     * usuarios que ya existian quedan como Administrador, para que nadie
     * pierda el acceso al aplicar la migracion.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->json('permisos');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->boolean('activo')->default(true);
        });

        $ahora = now();
        $roles = [
            'Administrador' => ['Acceso completo al sistema', ['dashboard', 'estadisticas', 'prospectos', 'clientes', 'cotizaciones', 'aprobar_cotizaciones', 'costos', 'vendedores', 'usuarios']],
            'Vendedor' => ['Prospectos, clientes y sus cotizaciones', ['dashboard', 'prospectos', 'clientes', 'cotizaciones']],
            'Supervisor' => ['Seguimiento y aprobación de cotizaciones', ['dashboard', 'estadisticas', 'prospectos', 'clientes', 'cotizaciones', 'aprobar_cotizaciones']],
        ];

        foreach ($roles as $nombre => [$descripcion, $permisos]) {
            DB::table('roles')->insert([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'permisos' => json_encode($permisos),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        DB::table('users')->update(['rol_id' => DB::table('roles')->where('nombre', 'Administrador')->value('id')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rol_id');
            $table->dropColumn('activo');
        });

        Schema::dropIfExists('roles');
    }
};
