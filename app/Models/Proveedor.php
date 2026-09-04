<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = ['nombre'];

    public function familias(): BelongsToMany
    {
        return $this->belongsToMany(InsumoFamilia::class, 'insumo_familia_proveedor');
    }
}
