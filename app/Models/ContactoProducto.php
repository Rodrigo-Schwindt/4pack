<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Producto de un cliente. Se da de alta desde la cotizacion y solo aparece
 * en las cotizaciones de ese mismo cliente.
 */
class ContactoProducto extends Model
{
    protected $table = 'contacto_productos';

    protected $fillable = ['contacto_id', 'nombre'];

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }
}
