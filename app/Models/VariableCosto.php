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

    public const GOLPES_DOYPACK = 'golpes_doypack';

    public const GOLPES_POUCH = 'golpes_pouch';

    public const GOLPES_ZIPPER = 'golpes_zipper';

    public const MARGEN_DPK_CHICO = 'margen_dpk_chico';

    public const MARGEN_DPK = 'margen_dpk';

    public const ENVASES_CAJA = 'envases_caja';

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
        self::GOLPES_DOYPACK => ['Confección: golpes por minuto (Doypack)', 'Desde ancho (cm)', 'Velocidad de la confeccionadora según el ancho del envase. Aplica el tramo de mayor "desde" que no supere el ancho.'],
        self::GOLPES_POUCH => ['Confección: golpes por minuto (Pouch)', 'Desde ancho (cm)', 'Igual que Doypack, para los pouch.'],
        self::GOLPES_ZIPPER => ['Confección: golpes que resta el zipper', 'Desde ancho (cm)', 'Se restan a los golpes por minuto cuando el envase lleva zipper.'],
        self::MARGEN_DPK_CHICO => ['Margen confección DPK < 18 cm', 'Hasta kg', '% Plus según el peso, para envases de menos de 18 cm de ancho. La fila "resto" aplica por encima del último tramo.'],
        self::MARGEN_DPK => ['Margen confección DPK ≥ 18 cm', 'Hasta kg', '% Plus según el peso, para envases de 18 cm o más.'],
        self::ENVASES_CAJA => ['Envases por caja', 'Desde ancho (cm)', 'Cuántos envases entran en una caja según el ancho. Aplica el tramo de mayor "desde" que no supere el ancho.'],
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
        return static::porTramoHasta(self::VOLUMEN, $kg, estricto: true);
    }

    /**
     * Tablas "hasta": el primer tramo cuyo tope alcanza al valor (o lo supera,
     * si es estricto), y si ninguno lo alcanza, "resto".
     */
    public static function porTramoHasta(string $tipo, float $valor, bool $estricto = false): float
    {
        $tramos = static::listado($tipo);

        foreach ($tramos as $tramo) {
            if ($tramo->clave === self::RESTO) {
                continue;
            }

            $tope = (float) $tramo->clave;

            if ($estricto ? $valor < $tope : $valor <= $tope) {
                return (float) $tramo->valor;
            }
        }

        return (float) ($tramos->firstWhere('clave', self::RESTO)?->valor ?? 0);
    }

    /**
     * Tablas "desde": el tramo de mayor arranque que no supera al valor.
     */
    public static function porTramoDesde(string $tipo, float $valor): ?float
    {
        $elegido = null;

        foreach (static::listado($tipo) as $tramo) {
            if ($tramo->clave !== self::RESTO && (float) $tramo->clave <= $valor) {
                $elegido = (float) $tramo->valor;
            }
        }

        return $elegido;
    }
}
