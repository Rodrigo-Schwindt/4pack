<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsumoFamilia extends Model
{
    protected $table = 'insumo_familias';

    protected $fillable = ['insumo_id', 'nombre'];

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InsumoItem::class)->orderBy('id');
    }

    /** Proveedores que son columna de esta familia. */
    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'insumo_familia_proveedor')->orderBy('proveedores.id');
    }
}
