<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendedor extends Model
{
    protected $table = 'vendedores';

    /**
     * Columna de comision de cada tipo de producto de la cotizacion.
     */
    public const COMISIONES = [
        'bobinas' => 'comision_bobinas',
        'confeccion-dpk' => 'comision_dpk',
        'confeccion-pouch' => 'comision_pouch',
        'confeccion-4-costuras' => 'comision_4_costuras',
    ];

    protected $fillable = ['nombre', 'comision_bobinas', 'comision_dpk', 'comision_pouch', 'comision_4_costuras', 'activo'];

    protected function casts(): array
    {
        return [
            'comision_bobinas' => 'decimal:2',
            'comision_dpk' => 'decimal:2',
            'comision_pouch' => 'decimal:2',
            'comision_4_costuras' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * Comision (%) que le corresponde por un tipo de producto; 0 si no es uno conocido.
     */
    public function comision(string $tipoProducto): float
    {
        $columna = self::COMISIONES[$tipoProducto] ?? null;

        return $columna === null ? 0.0 : (float) $this->{$columna};
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}
