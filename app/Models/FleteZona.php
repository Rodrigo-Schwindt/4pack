<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleteZona extends Model
{
    protected $table = 'flete_zonas';

    protected $fillable = ['nombre'];

    public function precios(): HasMany
    {
        return $this->hasMany(FletePrecio::class);
    }

    /**
     * Precios de la zona indexados por tramo, para armar la fila de la tabla.
     *
     * @return array<int, float>
     */
    public function preciosPorTramo(): array
    {
        return $this->precios
            ->mapWithKeys(fn (FletePrecio $precio) => [$precio->flete_tramo_id => (float) $precio->precio])
            ->all();
    }
}
