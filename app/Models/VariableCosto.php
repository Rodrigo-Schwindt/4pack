<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Porcentajes de Configuracion > Variables Costos. Son las tablas "Margen",
 * "Dcto x volumen", "Cliente" y "Ajuste Cambiario" de la planilla.
 */
class VariableCosto extends Model
{
    public const MARGEN = 'margen';

    public const VOLUMEN = 'volumen';

    public const CATEGORIA = 'categoria';

    public const FINANCIACION = 'financiacion';

    /** Clave del descuento por volumen que aplica por encima del ultimo tramo. */
    public const RESTO = 'resto';

    /**
     * tipo => [titulo, etiqueta de la clave, ayuda]
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public const TIPOS = [
        self::MARGEN => ['Margen por estructura', 'Estructura', 'Plus base según la combinación de materiales de la cotización.'],
        self::VOLUMEN => ['Descuento por volumen', 'Hasta kg', 'Se suma al margen según el peso. La fila "resto" aplica por encima del último tramo; los negativos descuentan.'],
        self::CATEGORIA => ['Categoría de cliente', 'Categoría', 'Porcentaje según la categoría (A, B, C...) del cliente.'],
        self::FINANCIACION => ['Financiación por días', 'Días FF', 'Recargo sobre el valor al contado según los días de pago.'],
    ];

    protected $table = 'variables_costos';

    protected $fillable = ['tipo', 'clave', 'valor'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:4'];
    }

    public function scopeDelTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Filas de un tipo, ordenadas como corresponde (numericas por valor,
     * "resto" al final, el resto alfabetico).
     *
     * @return Collection<int, self>
     */
    public static function listado(string $tipo): Collection
    {
        return static::delTipo($tipo)->get()->sortBy(function (self $fila) {
            if ($fila->clave === self::RESTO) {
                return PHP_INT_MAX;
            }

            return is_numeric($fila->clave) ? (float) $fila->clave : $fila->clave;
        })->values();
    }

    public static function porcentaje(string $tipo, string $clave): ?float
    {
        $valor = static::delTipo($tipo)->where('clave', $clave)->value('valor');

        return $valor === null ? null : (float) $valor;
    }

    public static function margen(string $estructura): ?float
    {
        return static::porcentaje(self::MARGEN, $estructura);
    }

    public static function categoria(string $categoria): float
    {
        return static::porcentaje(self::CATEGORIA, $categoria) ?? 0;
    }

    public static function financiacion(int|string $dias): float
    {
        return static::porcentaje(self::FINANCIACION, (string) (int) $dias) ?? 0;
    }

    /**
     * Descuento por volumen: el primer tramo cuyo "hasta" supera al peso, o
     * el resto si lo pasa a todos.
     */
    public static function descuentoVolumen(float $kg): float
    {
        $tramos = static::listado(self::VOLUMEN);

        foreach ($tramos as $tramo) {
            if ($tramo->clave !== self::RESTO && $kg < (float) $tramo->clave) {
                return (float) $tramo->valor;
            }
        }

        return (float) ($tramos->firstWhere('clave', self::RESTO)?->valor ?? 0);
    }
}
