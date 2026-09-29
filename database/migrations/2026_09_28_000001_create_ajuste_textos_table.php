<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Listas de textos que alimentan selects del sistema (canales por donde
     * llega la OC, etc.). Las de numeros siguen en "ajustes".
     */
    public function up(): void
    {
        Schema::create('ajuste_textos', function (Blueprint $table) {
            $table->id();
            $table->string('grupo')->index();
            $table->string('texto');
            $table->timestamps();

            $table->unique(['grupo', 'texto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajuste_textos');
    }
};
