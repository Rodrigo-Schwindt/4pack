<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogo de flete de insumos: una fila por zona, una columna por tramo
     * (kg / pallets) y el precio en pesos del cruce de ambos.
     */
    public function up(): void
    {
        Schema::create('flete_tramos', function (Blueprint $table) {
            $table->id();
            $table->decimal('kg', 12, 2);
            $table->unsignedSmallInteger('pallets');
            $table->timestamps();

            $table->unique(['kg', 'pallets']);
        });

        Schema::create('flete_zonas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('flete_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flete_zona_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flete_tramo_id')->constrained()->cascadeOnDelete();
            $table->decimal('precio', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['flete_zona_id', 'flete_tramo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flete_precios');
        Schema::dropIfExists('flete_zonas');
        Schema::dropIfExists('flete_tramos');
    }
};
