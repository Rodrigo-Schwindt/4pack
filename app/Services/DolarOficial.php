<?php

namespace App\Services;

use App\Livewire\Fletes\Index as Fletes;
use App\Models\Ajuste;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cotizacion del dolar oficial, valor de venta. Se consulta primero DolarApi
 * (publica el BNA al momento) y, si no responde, la API del BCRA (estatal,
 * promedio vendedor de los bancos, con un dia de rezago). Si ninguna responde
 * se conserva el ultimo valor guardado: el sistema nunca queda sin dolar.
 */
class DolarOficial
{
    public const URL = 'https://dolarapi.com/v1/dolares/oficial';

    /** Variable 4 del BCRA: tipo de cambio minorista (promedio vendedor). */
    public const URL_BCRA = 'https://api.bcra.gob.ar/estadisticas/v4.0/monetarias/4';

    /** Grupo de Ajustes que indica si el dolar se actualiza solo (1) o a mano (0). */
    public const GRUPO_AUTOMATICO = 'dolar_automatico';

    /** Clave de cache con el detalle de la ultima consulta que anduvo. */
    private const CACHE_ULTIMA = 'dolar_oficial.ultima';

    /**
     * Consulta las fuentes en orden y devuelve la primera que responde bien.
     *
     * @return array{fuente: string, compra: ?float, venta: float, fecha: string}|null
     */
    public function consultar(): ?array
    {
        return $this->desdeDolarApi() ?? $this->desdeBcra();
    }

    /**
     * Consulta y guarda la cotizacion de venta en Ajustes. Devuelve el valor guardado o null si fallo.
     */
    public function actualizar(): ?float
    {
        $cotizacion = $this->consultar();

        if ($cotizacion === null) {
            return null;
        }

        Ajuste::definir(Fletes::GRUPO_DOLAR, $cotizacion['venta']);

        Cache::forever(self::CACHE_ULTIMA, $cotizacion + ['consultado_en' => now()->toIso8601String()]);

        return $cotizacion['venta'];
    }

    public static function automatico(): bool
    {
        return Ajuste::valorDe(self::GRUPO_AUTOMATICO) > 0;
    }

    public static function definirAutomatico(bool $activo): void
    {
        Ajuste::definir(self::GRUPO_AUTOMATICO, $activo ? 1 : 0);
    }

    /**
     * Detalle de la ultima actualizacion que anduvo, para mostrar en pantalla.
     *
     * @return array{fuente: string, compra: ?float, venta: float, fecha: string, consultado_en: Carbon}|null
     */
    public static function ultima(): ?array
    {
        $ultima = Cache::get(self::CACHE_ULTIMA);

        if (! is_array($ultima)) {
            return null;
        }

        $ultima['consultado_en'] = Carbon::parse($ultima['consultado_en']);

        return $ultima;
    }

    /**
     * @return array{fuente: string, compra: ?float, venta: float, fecha: string}|null
     */
    private function desdeDolarApi(): ?array
    {
        $json = $this->json(self::URL);

        if ($json === null || ! $this->esCotizacion($json['venta'] ?? null)) {
            return null;
        }

        return [
            'fuente' => 'BNA',
            'compra' => is_numeric($json['compra'] ?? null) ? (float) $json['compra'] : null,
            'venta' => (float) $json['venta'],
            'fecha' => (string) ($json['fechaActualizacion'] ?? ''),
        ];
    }

    /**
     * @return array{fuente: string, compra: ?float, venta: float, fecha: string}|null
     */
    private function desdeBcra(): ?array
    {
        $json = $this->json(self::URL_BCRA, ['limit' => 1]);
        $ultimo = $json['results'][0]['detalle'][0] ?? null;

        if (! is_array($ultimo) || ! $this->esCotizacion($ultimo['valor'] ?? null)) {
            return null;
        }

        return [
            'fuente' => 'BCRA',
            'compra' => null,
            'venta' => round((float) $ultimo['valor'], 2),
            'fecha' => (string) ($ultimo['fecha'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $parametros
     * @return array<string, mixed>|null
     */
    private function json(string $url, array $parametros = []): ?array
    {
        try {
            $respuesta = Http::timeout(10)->acceptJson()->get($url, $parametros);
        } catch (\Throwable) {
            return null;
        }

        $json = $respuesta->ok() ? $respuesta->json() : null;

        return is_array($json) ? $json : null;
    }

    private function esCotizacion(mixed $valor): bool
    {
        return is_numeric($valor) && (float) $valor > 0;
    }
}
