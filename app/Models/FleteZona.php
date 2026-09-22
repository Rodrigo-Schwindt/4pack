<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleteZona extends Model
{
    protected $table = 'flete_zonas';

    protected $fillable = ['nombre', 'zona_padre_id'];

    public function precios(): HasMany
    {
        return $this->hasMany(FletePrecio::class);
    }

    /**
     * La zona de la que cuelga, cuando es una subzona (Bernal → Quilmes).
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'zona_padre_id');
    }

    /**
     * Las subzonas tienen su propia tabla de precios; si no cargan ninguno,
     * la cotizacion usa el de la zona principal.
     */
    public function subzonas(): HasMany
    {
        return $this->hasMany(self::class, 'zona_padre_id')->orderBy('nombre');
    }

    /** @param  Builder<self>  $query */
    public function scopePrincipales(Builder $query): void
    {
        $query->whereNull('zona_padre_id');
    }

    public function esSubzona(): bool
    {
        return $this->zona_padre_id !== null;
    }

    /**
     * Como se nombra en la cotizacion: "Quilmes - Bernal" o solo "Quilmes".
     */
    public function nombreCompleto(): string
    {
        return $this->esSubzona() ? $this->padre?->nombre.' - '.$this->nombre : $this->nombre;
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
