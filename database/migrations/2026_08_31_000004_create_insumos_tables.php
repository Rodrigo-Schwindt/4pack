<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogo de insumos: cada insumo (Materiales, Tintas, ...) se abre en
     * familias, cada familia tiene sus items y cada item un costo por proveedor.
     */
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            // Para los titulos en singular: "Nuevo Material" en vez de "Nuevo Materiales".
            $table->string('singular')->nullable();
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('insumo_familias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['insumo_id', 'nombre']);
        });

        // Proveedores que son columna de la familia.
        Schema::create('insumo_familia_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_familia_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['insumo_familia_id', 'proveedor_id']);
        });

        Schema::create('insumo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_familia_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            // Proveedor con el que se trabaja: es el total que resalta la tabla.
            $table->foreignId('proveedor_elegido_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->timestamps();

            $table->unique(['insumo_familia_id', 'nombre']);
        });

        Schema::create('insumo_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->decimal('costo', 12, 4)->nullable();
            // Precio por mas de una tonelada: lo usa la cotizacion segun la cantidad.
            $table->decimal('costo_mas_1tn', 12, 4)->nullable();
            $table->boolean('flete')->default(false);
            $table->string('donde')->nullable();
            $table->decimal('costo_flete', 12, 4)->nullable();
            $table->timestamps();

            $table->unique(['insumo_item_id', 'proveedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumo_precios');
        Schema::dropIfExists('insumo_items');
        Schema::dropIfExists('insumo_familia_proveedor');
        Schema::dropIfExists('insumo_familias');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('insumos');
    }
};
