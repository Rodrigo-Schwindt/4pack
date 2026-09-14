<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El precio por volumen dejaba de ser "mas de 1 TN" fijo: cada familia
     * define desde cuantas toneladas aplica.
     */
    public function up(): void
    {
        Schema::table('insumo_familias', function (Blueprint $table) {
            $table->decimal('volumen_desde_tn', 8, 3)->default(1)->after('nombre');
        });

        Schema::table('insumo_precios', function (Blueprint $table) {
            $table->renameColumn('costo_mas_1tn', 'costo_volumen');
        });
    }

    public function down(): void
    {
        Schema::table('insumo_precios', function (Blueprint $table) {
            $table->renameColumn('costo_volumen', 'costo_mas_1tn');
        });

        Schema::table('insumo_familias', function (Blueprint $table) {
            $table->dropColumn('volumen_desde_tn');
        });
    }
};
