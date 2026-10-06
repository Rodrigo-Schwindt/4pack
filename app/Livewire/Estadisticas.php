<?php

namespace App\Livewire;

use App\Livewire\Cotizaciones\Form;
use App\Models\Contacto;
use App\Models\Cotizacion;
use App\Models\Vendedor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Indicadores del periodo, por vendedor y cliente: cotizaciones, ordenes de
 * compra, prospectos y la comision de cada vendedor, comparados con el
 * periodo anterior de la misma duracion.
 */
#[Layout('components.layouts.panel')]
#[Title('Estadísticas')]
class Estadisticas extends Component
{
    public string $vendedor = '';

    public string $cliente = '';

    public string $desde = '';

    public string $hasta = '';

    /**
     * Filtros con los que se calcula la pantalla: los campos de arriba se
     * pueden ir cambiando y recien cuentan al aplicar.
     *
     * @var array{vendedor: string, cliente: string, desde: string, hasta: string}
     */
    public array $aplicados = ['vendedor' => '', 'cliente' => '', 'desde' => '', 'hasta' => ''];

    public function mount(): void
    {
        $this->limpiar();
    }

    public function aplicar(): void
    {
        $this->validate(
            [
                'vendedor' => ['nullable', 'exists:vendedores,id'],
                'cliente' => ['nullable', 'exists:contactos,id'],
                'desde' => ['required', 'date'],
                'hasta' => ['required', 'date', 'after_or_equal:desde'],
            ],
            ['hasta.after_or_equal' => 'El hasta no puede ser anterior al desde.'],
            ['vendedor' => 'vendedor', 'cliente' => 'cliente', 'desde' => 'desde', 'hasta' => 'hasta'],
        );

        $this->aplicados = ['vendedor' => $this->vendedor, 'cliente' => $this->cliente, 'desde' => $this->desde, 'hasta' => $this->hasta];
    }

    /** Todos los vendedores y clientes, en el mes en curso. */
    public function limpiar(): void
    {
        $this->vendedor = '';
        $this->cliente = '';
        $this->desde = now()->startOfMonth()->format('Y-m-d');
        $this->hasta = now()->endOfMonth()->format('Y-m-d');
        $this->resetValidation();

        $this->aplicados = ['vendedor' => '', 'cliente' => '', 'desde' => $this->desde, 'hasta' => $this->hasta];
    }

    public function render()
    {
        $desde = Carbon::parse($this->aplicados['desde'])->startOfDay();
        $hasta = Carbon::parse($this->aplicados['hasta'])->startOfDay();

        // El periodo anterior dura lo mismo y termina el dia antes.
        $dias = (int) $desde->diffInDays($hasta) + 1;
        $antesHasta = $desde->copy()->subDay();
        $antesDesde = $antesHasta->copy()->subDays($dias - 1);

        $cotizaciones = $this->cotizacionesEntre($desde, $hasta);
        $anteriores = $this->cotizacionesEntre($antesDesde, $antesHasta);

        $prospectos = $this->prospectosEntre($desde, $hasta);
        $prospectosAntes = $this->prospectosEntre($antesDesde, $antesHasta)->count();

        $aprobadas = $cotizaciones->filter->aprobada();
        $rechazadas = $cotizaciones->where('estado', Cotizacion::RECHAZADA);
        $conOc = $cotizaciones->filter->tieneOrdenCompra();

        $vendedores = Vendedor::orderBy('nombre')->get(['id', 'nombre', 'activo']);

        return view('livewire.estadisticas', [
            'vendedores' => $vendedores,
            'clientes' => Contacto::enEstado(Contacto::CLIENTE)->orderBy('razon_social')->pluck('razon_social', 'id'),
            'tarjetas' => [
                'procesadas' => ['cantidad' => $cotizaciones->count()] + $this->desglose($cotizaciones),
                'aprobadas' => ['cantidad' => $aprobadas->count(), 'tendencia' => $this->tendencia($aprobadas->count(), $anteriores->filter->aprobada()->count())] + $this->desglose($aprobadas),
                'rechazadas' => ['cantidad' => $rechazadas->count(), 'tendencia' => $this->tendencia($rechazadas->count(), $anteriores->where('estado', Cotizacion::RECHAZADA)->count())] + $this->desglose($rechazadas),
                'con_oc' => ['cantidad' => $conOc->count(), 'tendencia' => $this->tendencia($conOc->count(), $anteriores->filter->tieneOrdenCompra()->count())],
                'prospectos' => ['cantidad' => $prospectos->count(), 'tendencia' => $this->tendencia($prospectos->count(), $prospectosAntes)],
                'sin_cotizar' => $this->clientesSinCotizar($desde, $hasta, $antesDesde, $antesHasta),
            ],
            'oc' => [
                'total' => $aprobadas->count(),
                'con' => $aprobadas->filter->tieneOrdenCompra()->count(),
                'sin' => $aprobadas->reject->tieneOrdenCompra()->count(),
            ],
            'prospectosPorVendedor' => $this->porVendedor($vendedores, $prospectos->countBy('vendedor_id')),
            'cotizacionesPorVendedor' => $this->porVendedor($vendedores, $cotizaciones->countBy('vendedor_id')),
            'detalle' => $this->detalleProspectos($prospectos),
            'comisiones' => $this->comisiones($aprobadas),
        ]);
    }

    /**
     * Cotizaciones con fecha en el rango, con el vendedor y cliente filtrados.
     *
     * @return Collection<int, Cotizacion>
     */
    private function cotizacionesEntre(Carbon $desde, Carbon $hasta): Collection
    {
        return Cotizacion::with('vendedor')
            ->whereBetween('fecha', [$desde->format('Y-m-d'), $hasta->format('Y-m-d')])
            ->when($this->aplicados['vendedor'] !== '', fn ($consulta) => $consulta->where('vendedor_id', $this->aplicados['vendedor']))
            ->when($this->aplicados['cliente'] !== '', fn ($consulta) => $consulta->where('contacto_id', $this->aplicados['cliente']))
            ->get();
    }

    /**
     * Prospectos dados de alta en el rango. El filtro de cliente no aplica:
     * un prospecto todavia no es cliente.
     *
     * @return Collection<int, Contacto>
     */
    private function prospectosEntre(Carbon $desde, Carbon $hasta): Collection
    {
        return Contacto::with(['vendedor', 'actividades'])
            ->enEstado(Contacto::PROSPECTO)
            ->whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when($this->aplicados['vendedor'] !== '', fn ($consulta) => $consulta->where('vendedor_id', $this->aplicados['vendedor']))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return array{cantidad: int, tendencia: ?int}
     */
    private function clientesSinCotizar(Carbon $desde, Carbon $hasta, Carbon $antesDesde, Carbon $antesHasta): array
    {
        $clientes = Contacto::enEstado(Contacto::CLIENTE)
            ->when($this->aplicados['vendedor'] !== '', fn ($consulta) => $consulta->where('vendedor_id', $this->aplicados['vendedor']))
            ->when($this->aplicados['cliente'] !== '', fn ($consulta) => $consulta->whereKey($this->aplicados['cliente']))
            ->pluck('id');

        // Cuenta cualquier cotizacion del cliente, la haya hecho quien la haya hecho.
        $sinCotizar = fn (Carbon $inicio, Carbon $fin) => $clientes->diff(
            Cotizacion::whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])->distinct()->pluck('contacto_id')
        )->count();

        $actual = $sinCotizar($desde, $hasta);

        return ['cantidad' => $actual, 'tendencia' => $this->tendencia($actual, $sinCotizar($antesDesde, $antesHasta))];
    }

    /**
     * @param  Collection<int, Cotizacion>  $cotizaciones
     * @return array{bobinas_kg: float, envases_kg: float, envases_ud: float}
     */
    private function desglose(Collection $cotizaciones): array
    {
        $total = ['bobinas_kg' => 0.0, 'envases_kg' => 0.0, 'envases_ud' => 0.0];

        foreach ($cotizaciones as $cotizacion) {
            foreach ($cotizacion->cantidades() as $clave => $valor) {
                $total[$clave] += $valor;
            }
        }

        return $total;
    }

    /** Variacion porcentual contra el periodo anterior; null si antes no hubo nada. */
    private function tendencia(int $actual, int $anterior): ?int
    {
        return $anterior === 0 ? null : (int) round(($actual - $anterior) / $anterior * 100);
    }

    /**
     * Cantidad por vendedor, de mayor a menor. Los inactivos solo aparecen si
     * tienen algo en el periodo.
     *
     * @param  Collection<int, Vendedor>  $vendedores
     * @param  Collection<int|string, int>  $conteo
     * @return list<array{nombre: string, cantidad: int}>
     */
    private function porVendedor(Collection $vendedores, Collection $conteo): array
    {
        return $vendedores
            ->when($this->aplicados['vendedor'] !== '', fn ($lista) => $lista->where('id', (int) $this->aplicados['vendedor']))
            ->map(fn (Vendedor $vendedor) => ['nombre' => self::nombreCorto($vendedor->nombre), 'cantidad' => (int) ($conteo[$vendedor->id] ?? 0), 'activo' => $vendedor->activo])
            ->filter(fn (array $fila) => $fila['activo'] || $fila['cantidad'] > 0)
            ->sortByDesc('cantidad')
            ->map(fn (array $fila) => ['nombre' => $fila['nombre'], 'cantidad' => $fila['cantidad']])
            ->values()
            ->all();
    }

    /**
     * Los prospectos del periodo y su ultimo contacto. Sin seguimiento: no
     * hubo ninguna actividad despues del dia en que se cargo.
     *
     * @param  Collection<int, Contacto>  $prospectos
     * @return list<array<string, mixed>>
     */
    private function detalleProspectos(Collection $prospectos): array
    {
        return $prospectos->map(function (Contacto $prospecto) {
            $creado = $prospecto->created_at->copy()->startOfDay();
            $ultimo = $prospecto->actividades->max('fecha') ?? $creado;

            return [
                'id' => $prospecto->id,
                'razon_social' => $prospecto->razon_social,
                'vendedor' => $prospecto->vendedor ? self::nombreCorto($prospecto->vendedor->nombre) : 'Sin vendedor',
                'creado' => $creado->format('d/m/Y'),
                'ultimo_contacto' => $ultimo->format('d/m/Y'),
                'sin_seguimiento' => $ultimo->lte($creado),
            ];
        })->values()->all();
    }

    /**
     * Comision de cada vendedor sobre lo aprobado en el periodo, con el mismo
     * calculo de la solapa Entrega: la proyectada por lo pactado en cada
     * entrega y la real por lo efectivamente entregado.
     *
     * @param  Collection<int, Cotizacion>  $aprobadas
     * @return list<array{vendedor: string, real: float, proyectada: float}>
     */
    private function comisiones(Collection $aprobadas): array
    {
        $porVendedor = [];

        foreach ($aprobadas->filter(fn (Cotizacion $cotizacion) => $cotizacion->vendedor !== null) as $cotizacion) {
            try {
                $formulario = new Form;
                $formulario->mount($cotizacion);
                $entregas = collect($formulario->entregasDelPedido);
            } catch (\Throwable $error) {
                // Una cotizacion que no se puede recalcular no tumba la pantalla.
                report($error);

                continue;
            }

            $id = $cotizacion->vendedor_id;
            $porVendedor[$id] ??= [
                'vendedor' => $cotizacion->vendedor->nombre,
                'real' => 0.0,
                'proyectada' => 0.0,
            ];

            $porVendedor[$id]['proyectada'] += $entregas->sum(fn (array $entrega) => $entrega['proyectada'] ?? 0);
            $porVendedor[$id]['real'] += $entregas->sum(fn (array $entrega) => $entrega['real'] ?? 0);
        }

        return collect($porVendedor)->sortByDesc('proyectada')->values()->all();
    }

    /** "Juan Martinez" -> "Juan M."; un solo nombre queda igual. */
    public static function nombreCorto(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre)) ?: [];

        return count($partes) > 1 ? $partes[0].' '.Str::upper(Str::substr($partes[1], 0, 1)).'.' : trim($nombre);
    }
}
