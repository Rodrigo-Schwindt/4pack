<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peso especifico del material: con el se calcula cuantos kilos pesan
     * 1000 metros de lamina, y de ahi el peso de la cotizacion.
     */
    public function up(): void
    {
        Schema::table('insumo_items', function (Blueprint $table) {
            $table->decimal('peso_especifico', 8, 4)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('insumo_items', function (Blueprint $table) {
            $table->dropColumn('peso_especifico');
        });
    }
};
