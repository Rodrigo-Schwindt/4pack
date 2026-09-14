<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La cotizacion guardada: la cabecera en columnas y el detalle del tipo
     * de producto (bobinas, entregas, pagos, textos, OC) en JSON, porque cada
     * tipo tiene sus propios campos y los calculos se rehacen al abrirla.
     */
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->date('fecha');
            $table->foreignId('contacto_id')->constrained('contactos')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('vendedores')->nullOnDelete();
            $table->string('categoria')->nullable();
            $table->decimal('ajuste_categoria', 8, 2)->default(0);
            $table->decimal('ajuste_vendedor', 8, 2)->default(0);
            $table->string('referencia')->nullable();
            $table->string('tipo_producto')->nullable();
            $table->string('estado')->default('pendiente');
            $table->json('datos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
