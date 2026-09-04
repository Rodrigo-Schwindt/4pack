<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogos que se pueden crear al vuelo desde el detalle del contacto.
     */
    public function up(): void
    {
        foreach (['rubros', 'tipos'] as $tabla) {
            Schema::create($tabla, function (Blueprint $table) {
                $table->id();
                $table->string('nombre')->unique();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['rubros', 'tipos'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
