<?php

namespace App\Livewire\Entregas;

use App\Livewire\Concerns\Paginado;
use App\Livewire\Cotizaciones\Form;
use App\Models\Cotizacion;
use App\Models\Vendedor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Todas las entregas de los pedidos: una fila por cada entrega de las
 * cotizaciones aprobadas o finalizadas, con lo pactado, lo entregado y la
 * comision que le toca. Es la solapa Entrega de cada cotizacion, junta.
 */
#[Layout('components.layouts.panel')]
#[Title('Entregas')]
class Index extends Component
{
    use Paginado;

    public bool $mostrarFiltros = false;

    public string $cliente = '';

    public string $vendedor = '';

    /** '' todas, 'pendientes' sin cantidad entregada, 'entregadas' con cantidad entregada. */
    public string $estado = '';

    public string $desde = '';

    public string $hasta = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset('cliente', 'vendedor', 'estado', 'desde', 'hasta');
        $this->resetPage();
    }

    /**
     * Cada entrega de cada pedido, armada con el mismo formulario de la
     * cotizacion para que los numeros den igual que en su solapa Entrega.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function entregas(): Collection
    {
        $filas = collect();

        $pedidos = Cotizacion::with(['cliente', 'vendedor'])
            ->whereIn('estado', [Cotizacion::APROBADA, Cotizacion::FINALIZADA])
            ->get();

        foreach ($pedidos as $cotizacion) {
            try {
                $formulario = new Form;
                $formulario->mount($cotizacion);
                $entregas = $formulario->entregasDelPedido;
            } catch (\Throwable $error) {
                // Una cotizacion que no se puede recalcular no tumba la pantalla.
                report($error);

                continue;
            }

            foreach ($entregas as $entrega) {
                $datos = (array) data_get($formulario, $entrega['modelo'], []);
                $real = (string) ($datos['fecha_real'] ?? '');
                $pactada = (string) ($datos['fecha_entrega'] ?? '');

                $filas->push([
                    'cotizacion_id' => $cotizacion->id,
                    'numero' => $cotizacion->numero,
                    'cliente' => (string) $cotizacion->cliente?->razon_social,
                    'vendedor_id' => $cotizacion->vendedor_id,
                    'entrega' => $entrega['nombre'],
                    // La fecha en que se entrego; si todavia no, la pactada en la OC.
                    'fecha' => $real !== '' ? $real : ($pactada !== '' ? $pactada : null),
                    'fecha_real' => $real !== '',
                    'a_entregar' => $entrega['a_entregar'],
                    'entregada' => $entrega['entregada'],
                    'unidad' => $entrega['unidad'],
                    'proyectada' => $entrega['proyectada'],
                    'real' => $entrega['real'],
                ]);
            }
        }

        return $filas;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $filas
     * @return Collection<int, array<string, mixed>>
     */
    private function filtrar(Collection $filas): Collection
    {
        $buscado = mb_strtolower(trim($this->cliente));

        return $filas
            ->when($buscado !== '', fn (Collection $c) => $c->filter(fn (array $f) => str_contains(mb_strtolower($f['cliente']), $buscado)))
            ->when($this->vendedor !== '', fn (Collection $c) => $c->where('vendedor_id', (int) $this->vendedor))
            ->when($this->estado === 'pendientes', fn (Collection $c) => $c->whereNull('entregada'))
            ->when($this->estado === 'entregadas', fn (Collection $c) => $c->whereNotNull('entregada'))
            ->when($this->desde !== '', fn (Collection $c) => $c->filter(fn (array $f) => $f['fecha'] !== null && $f['fecha'] >= $this->desde))
            ->when($this->hasta !== '', fn (Collection $c) => $c->filter(fn (array $f) => $f['fecha'] !== null && $f['fecha'] <= $this->hasta))
            // Por fecha, las mas proximas primero; las que no tienen fecha al final.
            ->sortBy(fn (array $f) => [$f['fecha'] === null ? 1 : 0, $f['fecha'] ?? '', $f['numero']])
            ->values();
    }

    public function render()
    {
        $filas = $this->filtrar($this->entregas());
        $pagina = $this->getPage();

        return view('livewire.entregas.index', [
            'entregas' => new LengthAwarePaginator(
                $filas->forPage($pagina, self::POR_PAGINA)->values(),
                $filas->count(),
                self::POR_PAGINA,
                $pagina,
                ['path' => route('entregas.index')],
            ),
            'vendedores' => Vendedor::orderBy('nombre')->pluck('nombre', 'id'),
            'hayFiltros' => $this->cliente !== '' || $this->vendedor !== '' || $this->estado !== '' || $this->desde !== '' || $this->hasta !== '',
        ]);
    }
}
