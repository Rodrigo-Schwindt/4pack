<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubro extends Model
{
    protected $table = 'rubros';

    protected $fillable = ['nombre'];

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}
