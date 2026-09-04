<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Direcciones de entrega del cliente. La zona es la del catalogo de flete
     * de insumos: de ahi sale despues el precio del flete al cotizar.
     */
    public function up(): void
    {
        Schema::create('contacto_direcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contacto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flete_zona_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direccion');
            $table->string('codigo_postal', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacto_direcciones');
    }
};
