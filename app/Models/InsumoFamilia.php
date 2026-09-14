<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsumoFamilia extends Model
{
    protected $table = 'insumo_familias';

    protected $fillable = ['insumo_id', 'nombre', 'volumen_desde_tn'];

    protected function casts(): array
    {
        return ['volumen_desde_tn' => 'decimal:3'];
    }

    /** Kilos a partir de los cuales rige el precio por volumen. */
    public function kilosVolumen(): float
    {
        return (float) $this->volumen_desde_tn * 1000;
    }

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
