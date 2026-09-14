<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsumoPrecio extends Model
{
    protected $table = 'insumo_precios';

    protected $fillable = ['insumo_item_id', 'proveedor_id', 'costo', 'costo_volumen', 'flete', 'donde', 'costo_flete'];

    protected function casts(): array
    {
        return [
            'costo' => 'decimal:4',
            'costo_volumen' => 'decimal:4',
            'costo_flete' => 'decimal:4',
            'flete' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InsumoItem::class, 'insumo_item_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /** Costo mas el flete, cuando lo hay. Null si todavia no tiene costo. */
    public function getTotalAttribute(): ?float
    {
        if ($this->costo === null) {
            return null;
        }

        return (float) $this->costo + ($this->flete ? (float) $this->costo_flete : 0);
    }

    /**
     * Costo que corresponde a la cantidad: desde las toneladas que define la
     * familia manda el precio por volumen, si esta cargado.
     */
    public function costoPara(float $kg): ?float
    {
        $porVolumen = $this->costo_volumen !== null && $kg >= $this->item->familia->kilosVolumen();
        $costo = $porVolumen ? (float) $this->costo_volumen : $this->costo;

        return $costo === null ? null : (float) $costo;
    }
}
