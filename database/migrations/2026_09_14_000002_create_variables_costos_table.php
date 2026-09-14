<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Porcentajes que arman el precio a partir del costo bruto: margen por
     * estructura de material, descuento por volumen, ajuste por categoria de
     * cliente y financiacion por dias. Cada tipo es una tablita clave => %.
     */
    public function up(): void
    {
        Schema::create('variables_costos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->string('clave');
            $table->decimal('valor', 8, 4);
            $table->timestamps();

            $table->unique(['tipo', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variables_costos');
    }
};
