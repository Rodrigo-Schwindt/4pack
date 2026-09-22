<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una zona puede tener subzonas con su propia tabla de precios: Quilmes
     * cuelga de Bernal y Don Bosco, cada una con su costo por tramo. La
     * subzona es una zona mas, colgada de la principal, asi reutiliza los
     * precios que ya existen.
     */
    public function up(): void
    {
        Schema::table('flete_zonas', function (Blueprint $table) {
            $table->foreignId('zona_padre_id')->nullable()->after('nombre')->constrained('flete_zonas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flete_zonas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zona_padre_id');
        });
    }
};
