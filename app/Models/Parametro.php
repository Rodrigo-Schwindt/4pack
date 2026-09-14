<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un numero por clave, para las constantes que usan las formulas. Se editan
 * desde Configuracion > Ajustes > Variables de calculo.
 */
class Parametro extends Model
{
    /** Lo que se le suma al ancho refilado para llegar al ancho de lamina. */
    public const ANCHO_LAMINA_EXTRA = 'ancho_lamina_extra';

    public const SCRAP_METROS_POR_ARRANQUE = 'scrap_metros_por_arranque';

    public const SCRAP_METROS_EXTRA = 'scrap_metros_extra';

    public const IMPRESION_HORAS_POR_CAMBIO = 'impresion_horas_por_cambio';

    public const REPRINT_DIVISOR_SETUP = 'reprint_divisor_setup';

    public const IMPRESION_COLORES_BASE = 'impresion_colores_base';

    public const TELA_LARGO_ROLLO = 'tela_largo_rollo';

    public const TELA_ANCHO_ROLLO = 'tela_ancho_rollo';

    public const TELA_FACTOR = 'tela_factor';

    public const TINTAS_CARAS = 'tintas_caras';

    public const BARNIZ_PROPORCION = 'barniz_proporcion';

    public const ALARGUE_PROPORCION = 'alargue_proporcion';

    public const POLIMEROS_USD_CM2 = 'polimeros_usd_cm2';

    public const POLIMEROS_DESARROLLO_EXTRA = 'polimeros_desarrollo_extra';

    public const POLIMEROS_PROPORCION = 'polimeros_proporcion';

    public const CATEGORIA_EN_PRECIO = 'categoria_en_precio';

    public const MATERIAL_FACTOR = 'material_factor';

    public const SOLVENTLESS_COMPUESTO = 'solventless_compuesto';

    public const SOLVENTLESS_CATALIZADOR = 'solventless_catalizador';

    public const ADHESIVO_CARAS = 'adhesivo_caras';

    public const PLAZO_ENTREGA_DIAS = 'plazo_entrega_dias';

    public const VENCIMIENTO_MESES = 'vencimiento_meses';

    public const RECLAMOS_DIAS = 'reclamos_dias';

    public const VIGENCIA_DIAS = 'vigencia_dias';

    public const TOLERANCIA_PCT = 'tolerancia_pct';

    public const OBJETIVO_TONELADAS_DIA = 'objetivo_toneladas_dia';

    /** Litros de limpieza segun los metros de la cotizacion: la escala de la planilla. */
    public const LIMPIEZA_ESCALA = [
        'limpieza_lts_base' => [0, 12],
        'limpieza_lts_30000' => [30000, 16],
        'limpieza_lts_60000' => [60000, 24],
        'limpieza_lts_150000' => [150000, 48],
        'limpieza_lts_180000' => [180000, 72],
        'limpieza_lts_210000' => [210000, 96],
        'limpieza_lts_240000' => [240000, 120],
    ];

    /**
     * Todas las variables editables: seccion => [clave => [etiqueta, defecto]].
     * Los defectos son los numeros que tiene la planilla original.
     *
     * @var array<string, array<string, array{0: string, 1: float}>>
     */
    public const VARIABLES = [
        'Medidas' => [
            self::ANCHO_LAMINA_EXTRA => ['Ancho lámina: cm que se suman al ancho refilado', 2],
        ],
        'Materiales' => [
            self::MATERIAL_FACTOR => ['Factor que multiplica el valor de cada material (columna A de la planilla)', 1],
        ],
        'Precio' => [
            self::CATEGORIA_EN_PRECIO => ['El % de la categoría del cliente se suma al plus (1 = sí, 0 = no)', 1],
        ],
        'Scrap' => [
            self::SCRAP_METROS_POR_ARRANQUE => ['Metros que suma cada diseño y cada cambio (impresión)', 1500],
            self::SCRAP_METROS_EXTRA => ['Metros que se suman en laminación y bilaminación', 100],
        ],
        'Impresión' => [
            self::IMPRESION_HORAS_POR_CAMBIO => ['Horas de preparación por cada cambio', 1],
            self::REPRINT_DIVISOR_SETUP => ['Reprint: el setup de impresión se divide por', 3],
            self::IMPRESION_COLORES_BASE => ['Colores sobre los que está calculado el costo de tintas', 8],
            self::TINTAS_CARAS => ['Tintas: factor por caras', 2],
            self::BARNIZ_PROPORCION => ['Reprint: proporción de barniz', 0.8],
            self::ALARGUE_PROPORCION => ['Reprint: proporción de diluyente de alargue', 0.2],
        ],
        'Polímeros (clichés)' => [
            self::POLIMEROS_USD_CM2 => ['U$S por cm² de polímero', 0.055],
            self::POLIMEROS_DESARROLLO_EXTRA => ['cm que se suman al desarrollo', 3],
            self::POLIMEROS_PROPORCION => ['Proporción del polímero que se bonifica (%)', 100],
        ],
        'Laminación' => [
            self::SOLVENTLESS_COMPUESTO => ['Solvent less: proporción de compuesto', 0.55],
            self::SOLVENTLESS_CATALIZADOR => ['Solvent less: proporción de catalizador', 0.45],
            self::ADHESIVO_CARAS => ['Adhesivo: factor por caras', 2],
        ],
        'Tela' => [
            self::TELA_LARGO_ROLLO => ['Largo del rollo de tela (cm)', 2300],
            self::TELA_ANCHO_ROLLO => ['Ancho del rollo de tela (cm)', 46],
            self::TELA_FACTOR => ['Factor de aprovechamiento de la tela', 0.75],
        ],
        'Cotización (textos y dashboard)' => [
            self::PLAZO_ENTREGA_DIAS => ['Plazo de entrega (días desde la OC)', 30],
            self::VENCIMIENTO_MESES => ['Vencimiento de los materiales (meses)', 12],
            self::RECLAMOS_DIAS => ['Tiempo máximo de reclamos (días)', 60],
            self::VIGENCIA_DIAS => ['Vigencia de la cotización (días)', 2],
            self::TOLERANCIA_PCT => ['Tolerancia de la cantidad entregada (± %)', 10],
            self::OBJETIVO_TONELADAS_DIA => ['Objetivo diario de toneladas aprobadas (dashboard)', 2],
        ],
        'Limpieza de diluyentes (litros según metros)' => [
            'limpieza_lts_base' => ['Hasta 30.000 m', 12],
            'limpieza_lts_30000' => ['Más de 30.000 m', 16],
            'limpieza_lts_60000' => ['Más de 60.000 m', 24],
            'limpieza_lts_150000' => ['Más de 150.000 m', 48],
            'limpieza_lts_180000' => ['Más de 180.000 m', 72],
            'limpieza_lts_210000' => ['Más de 210.000 m', 96],
            'limpieza_lts_240000' => ['Más de 240.000 m', 120],
        ],
    ];

    protected $table = 'parametros';

    protected $fillable = ['clave', 'valor'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:4'];
    }

    public static function valor(string $clave): float
    {
        $guardado = static::where('clave', $clave)->value('valor');

        return (float) ($guardado ?? self::defecto($clave) ?? 0);
    }

    public static function guardar(string $clave, float $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }

    public static function defecto(string $clave): ?float
    {
        foreach (self::VARIABLES as $variables) {
            if (isset($variables[$clave])) {
                return (float) $variables[$clave][1];
            }
        }

        return null;
    }

    /**
     * Litros de limpieza que corresponden a esa cantidad de metros.
     */
    public static function litrosLimpieza(float $metros): float
    {
        $litros = 0.0;

        foreach (self::LIMPIEZA_ESCALA as $clave => [$desde]) {
            if ($metros > $desde || $desde === 0) {
                $litros = static::valor($clave);
            }
        }

        return $litros;
    }

    /**
     * Todas las variables con su valor actual, para la pantalla de Ajustes.
     *
     * @return array<string, array<string, array{etiqueta: string, valor: float, defecto: float}>>
     */
    public static function todas(): array
    {
        $guardados = static::pluck('valor', 'clave');
        $salida = [];

        foreach (self::VARIABLES as $seccion => $variables) {
            foreach ($variables as $clave => [$etiqueta, $defecto]) {
                $salida[$seccion][$clave] = [
                    'etiqueta' => $etiqueta,
                    'valor' => (float) ($guardados[$clave] ?? $defecto),
                    'defecto' => (float) $defecto,
                ];
            }
        }

        return $salida;
    }
}
