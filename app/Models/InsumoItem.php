<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsumoItem extends Model
{
    protected $table = 'insumo_items';

    protected $fillable = ['insumo_familia_id', 'nombre', 'proveedor_elegido_id'];

    public function familia(): BelongsTo
    {
        return $this->belongsTo(InsumoFamilia::class, 'insumo_familia_id');
    }

    public function precios(): HasMany
    {
        return $this->hasMany(InsumoPrecio::class);
    }

    public function elegido(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_elegido_id');
    }

    /**
     * Precios del item indexados por proveedor.
     *
     * @return array<int, InsumoPrecio>
     */
    public function preciosPorProveedor(): array
    {
        return $this->precios->keyBy('proveedor_id')->all();
    }
}
