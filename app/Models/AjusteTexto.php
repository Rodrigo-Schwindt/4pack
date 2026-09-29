<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Opciones de texto de un select, administradas en Configuración > Ajustes
 * (a diferencia de Ajuste, que guarda numeros como las mangas).
 */
class AjusteTexto extends Model
{
    /** Por donde llega la orden de compra del cliente. */
    public const CANALES_OC = 'canales_oc';

    protected $table = 'ajuste_textos';

    protected $fillable = ['grupo', 'texto'];

    public function scopeDelGrupo(Builder $query, string $grupo): Builder
    {
        return $query->where('grupo', $grupo);
    }

    /**
     * Textos listos para usar como opciones de un select (valor = texto).
     *
     * @return Collection<string, string>
     */
    public static function opciones(string $grupo): Collection
    {
        return static::delGrupo($grupo)->orderBy('texto')->pluck('texto', 'texto');
    }
}
