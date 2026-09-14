<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoDireccion extends Model
{
    protected $table = 'contacto_direcciones';

    protected $fillable = ['contacto_id', 'flete_zona_id', 'direccion', 'codigo_postal', 'observaciones'];

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(FleteZona::class, 'flete_zona_id');
    }
}
