<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Cotizacion extends Model
{
    public const PENDIENTE = 'pendiente';

    public const APROBADA = 'aprobada';

    public const FINALIZADA = 'finalizada';

    /** El cliente no la acepto. */
    public const RECHAZADA = 'rechazada';

    /** En el orden en que avanza una cotizacion. */
    public const ESTADOS = [
        self::PENDIENTE => 'Pendiente',
        self::APROBADA => 'Aprobada',
        self::FINALIZADA => 'Finalizada',
        self::RECHAZADA => 'Rechazada',
    ];

    /** Aprobada por el cliente: la finalizada ya paso por aprobada. */
    public function aprobada(): bool
    {
        return in_array($this->estado, [self::APROBADA, self::FINALIZADA], true);
    }

    /** Si se cargo la orden de compra: su numero, la fecha de recibo o el archivo. */
    public function tieneOrdenCompra(): bool
    {
        $oc = $this->datos['oc'] ?? [];

        return trim((string) ($oc['numero'] ?? '')) !== ''
            || trim((string) ($oc['fecha_recibo'] ?? '')) !== ''
            || ! empty($this->datos['oc_archivos']);
    }

    /**
     * Lo cotizado separado como lo cuenta la planilla: los kilos de bobinas,
     * y los kilos y unidades de los envases (las confecciones).
     *
     * @return array{bobinas_kg: float, envases_kg: float, envases_ud: float}
     */
    public function cantidades(): array
    {
        $cantidades = ['bobinas_kg' => 0.0, 'envases_kg' => 0.0, 'envases_ud' => 0.0];

        foreach ($this->productos() as $producto) {
            $peso = (float) ($producto['bobinas']['peso'] ?? 0);

            if (($producto['tipo_producto'] ?? '') === 'bobinas') {
                $cantidades['bobinas_kg'] += $peso;

                continue;
            }

            if (($producto['tipo_producto'] ?? '') !== '') {
                $cantidades['envases_kg'] += $peso;
                $cantidades['envases_ud'] += (float) ($producto['bobinas']['envases'] ?? 0);
            }
        }

        return $cantidades;
    }

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

    /**
     * Fecha en que entro la orden de compra, si entro: la del recibo de la OC
     * y, si no se cargo, la de la aprobacion.
     */
    public function fechaOrdenCompra(): ?Carbon
    {
        $recibo = trim((string) ($this->datos['oc']['fecha_recibo'] ?? ''));

        if ($recibo !== '') {
            try {
                return Carbon::parse($recibo)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        return in_array($this->estado, [self::APROBADA, self::FINALIZADA], true)
            ? $this->aprobada_en?->copy()->startOfDay()
            : null;
    }

    /**
     * Kilos entregados con fecha real dentro del rango. Las entregas se
     * reparten en metros o kilos segun el producto, asi que lo entregado se
     * lleva a kilos con la proporcion que represente de todo el producto.
     */
    public function kgEntregadosEntre(Carbon $desde, Carbon $hasta): float
    {
        $kilos = 0.0;

        foreach ($this->productos() as $producto) {
            $peso = (float) ($producto['bobinas']['peso'] ?? 0);
            $repartido = array_sum(array_map(fn (array $entrega) => (float) ($entrega['cantidad'] ?? 0), $producto['entregas']));

            if ($peso <= 0 || $repartido <= 0) {
                continue;
            }

            foreach ($producto['entregas'] as $entrega) {
                $entregada = trim((string) ($entrega['cantidad_entregada'] ?? ''));
                $fecha = trim((string) ($entrega['fecha_real'] ?? ''));

                if ($entregada === '' || $fecha === '') {
                    continue;
                }

                try {
                    $real = Carbon::parse($fecha)->startOfDay();
                } catch (\Throwable) {
                    continue;
                }

                if ($real->betweenIncluded($desde, $hasta)) {
                    $kilos += $peso * (float) $entregada / $repartido;
                }
            }
        }

        return $kilos;
    }

    /**
     * El producto principal y los que se sumaron con Duplicar / Nuevo producto.
     *
     * @return list<array{tipo_producto: string, bobinas: array<string, mixed>, entregas: list<array<string, mixed>>}>
     */
    private function productos(): array
    {
        $productos = [[
            'tipo_producto' => (string) $this->tipo_producto,
            'bobinas' => $this->datos['bobinas'] ?? [],
            'entregas' => array_values($this->datos['entregas'] ?? []),
        ]];

        foreach ($this->datos['productos_extra'] ?? [] as $extra) {
            $productos[] = [
                'tipo_producto' => (string) ($extra['tipo_producto'] ?? ''),
                'bobinas' => $extra['bobinas'] ?? [],
                'entregas' => array_values($extra['entregas'] ?? []),
            ];
        }

        return $productos;
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
