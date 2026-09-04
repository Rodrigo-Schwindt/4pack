<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prospecto y cliente son el mismo contacto en distinto estado: al convertirse
 * conserva codigo, datos e historial de actividad.
 */
class Contacto extends Model
{
    public const PROSPECTO = 'prospecto';

    public const CLIENTE = 'cliente';

    /**
     * Provincias argentinas: lista fija, no amerita tabla propia.
     */
    public const PROVINCIAS = [
        'Buenos Aires', 'CABA', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba', 'Corrientes',
        'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa', 'La Rioja', 'Mendoza', 'Misiones',
        'Neuquén', 'Río Negro', 'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe',
        'Santiago del Estero', 'Tierra del Fuego', 'Tucumán',
    ];

    protected $fillable = [
        'codigo',
        'estado',
        'nombre_comercial',
        'razon_social',
        'cuit',
        'email',
        'direccion',
        'provincia',
        'localidad',
        'telefono',
        'celular',
        'pagina_web',
        'rubro_id',
        'tipo_id',
        'vendedor_id',
        'observaciones',
    ];

    public function scopeEnEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(Tipo::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(ContactoActividad::class)->orderByDesc('fecha')->orderByDesc('id');
    }

    public function direcciones(): HasMany
    {
        return $this->hasMany(ContactoDireccion::class)->orderBy('id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(ContactoProducto::class)->orderBy('nombre');
    }

    /**
     * Codigo correlativo de 6 digitos, compartido por prospectos y clientes.
     */
    public static function siguienteCodigo(): string
    {
        $ultimo = (int) static::max('codigo');

        return str_pad((string) max($ultimo + 1, 445), 6, '0', STR_PAD_LEFT);
    }
}
