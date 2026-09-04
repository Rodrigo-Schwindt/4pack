<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un numero por clave, para las constantes que usan las formulas.
 */
class Parametro extends Model
{
    /** Lo que se le suma al ancho refilado para llegar al ancho de lamina. */
    public const ANCHO_LAMINA_EXTRA = 'ancho_lamina_extra';

    /**
     * Valor por defecto de cada clave, para cuando todavia no se tocó.
     */
    private const DEFECTOS = [
        self::ANCHO_LAMINA_EXTRA => 2,
    ];

    protected $table = 'parametros';

    protected $fillable = ['clave', 'valor'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2'];
    }

    public static function valor(string $clave): float
    {
        $guardado = static::where('clave', $clave)->value('valor');

        return (float) ($guardado ?? self::DEFECTOS[$clave] ?? 0);
    }

    public static function guardar(string $clave, float $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
}
