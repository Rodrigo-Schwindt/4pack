<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendedor extends Model
{
    protected $table = 'vendedores';

    protected $fillable = ['nombre', 'comision', 'activo'];

    protected function casts(): array
    {
        return [
            'comision' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }
}
