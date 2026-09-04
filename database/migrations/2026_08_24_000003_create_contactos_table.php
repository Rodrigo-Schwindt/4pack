<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prospecto y cliente son el mismo registro en distinto estado: comparten
     * tabla, secuencia de codigos e historial de actividad.
     */
    public function up(): void
    {
        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('estado', 20)->default('prospecto')->index();
            $table->string('nombre_comercial')->nullable();
            $table->string('razon_social');
            $table->string('cuit')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('provincia')->nullable();
            $table->string('localidad')->nullable();
            $table->string('telefono')->nullable();
            $table->string('celular')->nullable();
            $table->string('pagina_web')->nullable();
            $table->foreignId('rubro_id')->nullable()->constrained('rubros')->nullOnDelete();
            $table->foreignId('tipo_id')->nullable()->constrained('tipos')->nullOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('vendedores')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('contacto_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contacto_id')->constrained('contactos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('descripcion');
            $table->string('autor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacto_actividades');
        Schema::dropIfExists('contactos');
    }
};
