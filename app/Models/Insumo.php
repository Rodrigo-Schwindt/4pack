<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insumo extends Model
{
    /** El insumo que alimenta el bloque de materiales de la cotizacion. */
    public const MATERIALES = 'Materiales';

    protected $table = 'insumos';

    protected $fillable = ['nombre', 'singular'];

    public function familias(): HasMany
    {
        return $this->hasMany(InsumoFamilia::class)->orderBy('id');
    }

    /** Para los titulos: "Nuevo Material" en vez de "Nuevo Materiales". */
    public function getSingularAttribute(?string $valor): string
    {
        return $valor ?: $this->attributes['nombre'];
    }
}
