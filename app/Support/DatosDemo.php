<?php

namespace App\Support;

use App\Models\Contacto;
use App\Models\Cotizacion;
use Illuminate\Database\Eloquent\Builder;

/**
 * Datos de demostracion que carga el DemoSeeder. Todo cuelga de contactos
 * marcados en las observaciones, asi que borrarlos se lleva en cascada sus
 * cotizaciones, direcciones, productos y actividad.
 */
final class DatosDemo
{
    public const MARCA = 'Dato de demostración: se borra con php artisan demo:borrar';

    /** @return Builder<Contacto> */
    public static function contactos(): Builder
    {
        return Contacto::where('observaciones', self::MARCA);
    }

    /**
     * @return array{contactos: int, cotizaciones: int}
     */
    public static function borrar(): array
    {
        $ids = self::contactos()->pluck('id');
        $cotizaciones = Cotizacion::whereIn('contacto_id', $ids)->count();

        Contacto::whereIn('id', $ids)->delete();

        return ['contactos' => $ids->count(), 'cotizaciones' => $cotizaciones];
    }
}
