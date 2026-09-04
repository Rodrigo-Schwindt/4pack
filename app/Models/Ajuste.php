<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Ajuste extends Model
{
    protected $table = 'ajustes';

    protected $fillable = ['grupo', 'valor'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2'];
    }

    public function scopeDelGrupo(Builder $query, string $grupo): Builder
    {
        return $query->where('grupo', $grupo);
    }

    /**
     * Grupos de un solo valor (el dolar, por ejemplo), en vez de una lista.
     */
    public static function valorDe(string $grupo, float $porDefecto = 0): float
    {
        return (float) (static::delGrupo($grupo)->value('valor') ?? $porDefecto);
    }

    public static function definir(string $grupo, float|string $valor): void
    {
        static::delGrupo($grupo)->delete();

        static::create(['grupo' => $grupo, 'valor' => $valor]);
    }

    /**
     * Valores listos para usar como opciones de un select.
     *
     * @return Collection<int, float>
     */
    public static function opciones(string $grupo): Collection
    {
        return static::delGrupo($grupo)
            ->orderBy('valor')
            ->pluck('valor')
            ->map(fn ($valor) => (float) $valor)
            ->values();
    }
}
