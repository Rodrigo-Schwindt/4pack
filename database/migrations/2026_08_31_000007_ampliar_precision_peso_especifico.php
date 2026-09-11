<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El peso especifico es un numero chico (0,01638): con cuatro decimales se
     * redondeaba y el peso de la cotizacion salia mal.
     */
    public function up(): void
    {
        Schema::table('insumo_items', function (Blueprint $table) {
            $table->decimal('peso_especifico', 12, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('insumo_items', function (Blueprint $table) {
            $table->decimal('peso_especifico', 8, 4)->nullable()->change();
        });
    }
};
