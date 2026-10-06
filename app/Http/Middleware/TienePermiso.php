<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * La pantalla pide alguno de los permisos: permiso:clientes,cotizaciones.
 * Si al usuario lo desactivaron mientras tenia la sesion abierta, se lo saca.
 */
class TienePermiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();

        if ($usuario !== null && ! $usuario->activo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'Tu usuario está inactivo. Pedile a un administrador que lo active.']);
        }

        abort_unless($usuario?->puede(...$permisos), 403, 'No tenés permiso para entrar a esta pantalla.');

        return $next($request);
    }
}
