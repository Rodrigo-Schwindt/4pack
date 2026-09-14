<?php

namespace App\Support;

/**
 * Formato de numeros para mostrar en pantalla, a la argentina: punto de
 * miles y coma decimal (66.120,50). Los inputs numericos no pasan por aca,
 * porque el navegador exige el punto decimal para type="number".
 */
class Numero
{
    /** 66.120,50. Null (o vacio) se muestra como guion. */
    public static function formato(float|int|string|null $valor, int $decimales = 2, string $vacio = '-'): string
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return $vacio;
        }

        return number_format((float) $valor, $decimales, ',', '.');
    }

    /** Igual que formato() pero sin ceros de relleno: 2,5 en vez de 2,50 y 3 en vez de 3,00. */
    public static function corto(float|int|string|null $valor, int $decimales = 2, string $vacio = '-'): string
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return $vacio;
        }

        $texto = number_format((float) $valor, $decimales, ',', '.');

        return str_contains($texto, ',') ? rtrim(rtrim($texto, '0'), ',') : $texto;
    }

    /** USD 66.120,50 (en la cotizacion se escribe U$S: pasar el prefijo). */
    public static function usd(float|int|string|null $valor, int $decimales = 2, string $vacio = '-', string $prefijo = 'USD '): string
    {
        return $valor === null || $valor === '' || ! is_numeric($valor) ? $vacio : $prefijo.self::formato($valor, $decimales);
    }

    /** $66.120,50 */
    public static function pesos(float|int|string|null $valor, int $decimales = 2, string $vacio = '-'): string
    {
        return $valor === null || $valor === '' || ! is_numeric($valor) ? $vacio : '$'.self::formato($valor, $decimales);
    }

    /** 12,5 % */
    public static function porcentaje(float|int|string|null $valor, int $decimales = 2, string $vacio = '-'): string
    {
        return $valor === null || $valor === '' || ! is_numeric($valor) ? $vacio : self::corto($valor, $decimales).' %';
    }
}
