<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotizacion extends Model
{
    public const PENDIENTE = 'pendiente';

    public const APROBADA = 'aprobada';

    public const FINALIZADA = 'finalizada';

    /** En el orden en que avanza una cotizacion. */
    public const ESTADOS = [
        self::PENDIENTE => 'Pendiente',
        self::APROBADA => 'Aprobada',
        self::FINALIZADA => 'Finalizada',
    ];

    protected $table = 'cotizaciones';

    protected $fillable = [
        'numero', 'fecha', 'contacto_id', 'vendedor_id', 'categoria', 'ajuste_categoria',
        'ajuste_vendedor', 'referencia', 'tipo_producto', 'estado', 'aprobada_en', 'datos',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'aprobada_en' => 'datetime',
            'ajuste_categoria' => 'decimal:2',
            'ajuste_vendedor' => 'decimal:2',
            'datos' => 'array',
        ];
    }

    /** Peso en kilos que quedo guardado con la cotizacion (bobinas). */
    /**
     * Kilos de toda la cotizacion: el producto principal mas los que se
     * sumaron con Duplicar / Nuevo producto.
     */
    public function pesoKg(): float
    {
        $extras = collect($this->datos['productos_extra'] ?? [])->sum(fn (array $producto) => (float) ($producto['bobinas']['peso'] ?? 0));

        return (float) ($this->datos['bobinas']['peso'] ?? 0) + $extras;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Contacto::class, 'contacto_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class);
    }

    /**
     * Correlativo de 6 digitos por año: 000001/2026, 000002/2026...
     */
    public static function siguienteNumero(?int $anio = null): string
    {
        $anio ??= now()->year;

        $ultimo = static::where('numero', 'like', '%/'.$anio)
            ->pluck('numero')
            ->map(fn (string $numero) => (int) explode('/', $numero)[0])
            ->max();

        return sprintf('%06d/%d', (int) $ultimo + 1, $anio);
    }
}
