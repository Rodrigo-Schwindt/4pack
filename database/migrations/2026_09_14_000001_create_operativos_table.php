<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Costos operativos por sector (impresion, laminacion, rebobinado...):
     * valor de la hora, produccion, setup y scrap. Son la hoja "Tabla" A4:E8.
     */
    public function up(): void
    {
        Schema::create('operativos', function (Blueprint $table) {
            $table->id();
            $table->string('sector')->unique();
            $table->decimal('valor_hora', 12, 2)->default(0);
            $table->decimal('produccion_mts_hora', 12, 2)->nullable();
            $table->decimal('setup_horas', 8, 2)->default(0);
            $table->decimal('scrap_pct', 6, 2)->default(0);
            $table->timestamps();
        });

        // Las variables de calculo tienen decimales chicos (0,75 - 0,2).
        Schema::table('parametros', function (Blueprint $table) {
            $table->decimal('valor', 12, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('parametros', function (Blueprint $table) {
            $table->decimal('valor', 12, 2)->change();
        });

        Schema::dropIfExists('operativos');
    }
};
