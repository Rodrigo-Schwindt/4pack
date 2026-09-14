<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleteTramo extends Model
{
    protected $table = 'flete_tramos';

    protected $fillable = ['kg', 'pallets'];

    protected function casts(): array
    {
        return ['kg' => 'decimal:2', 'pallets' => 'integer'];
    }

    public function precios(): HasMany
    {
        return $this->hasMany(FletePrecio::class);
    }

    /** Encabezado de la columna: "3.500 / 6 pallets". */
    public function getEtiquetaAttribute(): string
    {
        return sprintf('%s / %d pallets', \App\Support\Numero::formato($this->kg, 0), $this->pallets);
    }
}
