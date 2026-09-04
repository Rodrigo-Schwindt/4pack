<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los productos se cargan al vuelo desde la cotizacion y valen solo para
     * el cliente que los dio de alta: no hay catalogo general de productos.
     */
    public function up(): void
    {
        Schema::create('contacto_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contacto_id')->constrained('contactos')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['contacto_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacto_productos');
    }
};
