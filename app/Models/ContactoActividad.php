<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoActividad extends Model
{
    protected $table = 'contacto_actividades';

    protected $fillable = ['contacto_id', 'fecha', 'descripcion', 'autor'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }
}
