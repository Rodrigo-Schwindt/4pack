<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Observaciones libres de la direccion de entrega (horarios, referencias, etc.).
     */
    public function up(): void
    {
        Schema::table('contacto_direcciones', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('codigo_postal');
        });
    }

    public function down(): void
    {
        Schema::table('contacto_direcciones', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};
