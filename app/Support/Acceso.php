<?php

namespace App\Support;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Reglas para no dejar el sistema sin nadie que pueda administrarlo.
 */
final class Acceso
{
    /**
     * Se llama despues del cambio y dentro de la misma transaccion: si ya no
     * queda ningun usuario activo que gestione usuarios y roles, el error
     * deshace el cambio.
     */
    public static function asegurarAdministrador(string $campo): void
    {
        $quedaAlguno = User::where('activo', true)
            ->whereHas('rol', fn ($consulta) => $consulta->whereJsonContains('permisos', Rol::USUARIOS))
            ->exists();

        if (! $quedaAlguno) {
            throw ValidationException::withMessages([
                $campo => 'Tiene que quedar al menos un usuario activo que pueda gestionar usuarios y roles.',
            ]);
        }
    }
}
