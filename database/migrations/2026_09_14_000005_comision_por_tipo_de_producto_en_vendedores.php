<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El vendedor cobra una comision distinta segun el tipo de producto
     * (bobinas, DPK, pouch, 4 costuras). La unica que habia pasa a las cuatro.
     */
    public function up(): void
    {
        Schema::table('vendedores', function (Blueprint $table) {
            $table->decimal('comision_bobinas', 5, 2)->default(0)->after('nombre');
            $table->decimal('comision_dpk', 5, 2)->default(0)->after('comision_bobinas');
            $table->decimal('comision_pouch', 5, 2)->default(0)->after('comision_dpk');
            $table->decimal('comision_4_costuras', 5, 2)->default(0)->after('comision_pouch');
        });

        DB::table('vendedores')->update([
            'comision_bobinas' => DB::raw('comision'),
            'comision_dpk' => DB::raw('comision'),
            'comision_pouch' => DB::raw('comision'),
            'comision_4_costuras' => DB::raw('comision'),
        ]);

        Schema::table('vendedores', function (Blueprint $table) {
            $table->dropColumn('comision');
        });
    }

    public function down(): void
    {
        Schema::table('vendedores', function (Blueprint $table) {
            $table->decimal('comision', 5, 2)->default(0)->after('nombre');
        });

        DB::table('vendedores')->update(['comision' => DB::raw('comision_bobinas')]);

        Schema::table('vendedores', function (Blueprint $table) {
            $table->dropColumn(['comision_bobinas', 'comision_dpk', 'comision_pouch', 'comision_4_costuras']);
        });
    }
};
