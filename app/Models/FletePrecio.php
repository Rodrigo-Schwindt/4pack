<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FletePrecio extends Model
{
    protected $table = 'flete_precios';

    protected $fillable = ['flete_zona_id', 'flete_tramo_id', 'precio'];

    protected function casts(): array
    {
        return ['precio' => 'decimal:2'];
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(FleteZona::class, 'flete_zona_id');
    }

    public function tramo(): BelongsTo
    {
        return $this->belongsTo(FleteTramo::class, 'flete_tramo_id');
    }
}
