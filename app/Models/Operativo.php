<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Costo operativo de un sector: lo que vale la hora, cuanto produce y cuanto
 * tarda en prepararse. Es la hoja "Tabla" A4:E8 de la planilla.
 */
class Operativo extends Model
{
    public const IMPRESION = 'Impresión';

    public const LAMINACION = 'Laminación';

    public const REBOBINADO = 'Rebobinado';

    public const CONFECCION = 'Confección';

    protected $table = 'operativos';

    protected $fillable = ['sector', 'valor_hora', 'produccion_mts_hora', 'setup_horas', 'scrap_pct'];

    protected function casts(): array
    {
        return [
            'valor_hora' => 'decimal:2',
            'produccion_mts_hora' => 'decimal:2',
            'setup_horas' => 'decimal:2',
            'scrap_pct' => 'decimal:2',
        ];
    }

    public static function delSector(string $sector): ?self
    {
        return static::where('sector', $sector)->first();
    }
}
