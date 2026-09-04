<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tipo extends Model
{
    protected $table = 'tipos';

    protected $fillable = ['nombre'];

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}
