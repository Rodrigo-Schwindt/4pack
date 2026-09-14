<?php

namespace Database\Seeders;

use App\Livewire\Cotizaciones\Form;
use App\Models\Contacto;
use App\Models\ContactoDireccion;
use App\Models\Cotizacion;
use App\Models\FleteTramo;
use App\Models\FleteZona;
use App\Models\InsumoItem;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use ReflectionMethod;

/**
 * Seis cotizaciones de bobinas de ejemplo, cargadas a traves del mismo
 * formulario para que los calculos y los textos queden como si se hubieran
 * hecho a mano. Solo corre si no hay ninguna cotizacion todavia.
 */
class CotizacionesDemoSeeder extends Seeder
{
    /**
     * Cada una: cliente, vendedor, materiales [nombre, mic], y el resto de los
     * datos. El estado y la antiguedad arman el dashboard: dos aprobadas hoy,
     * tres pendientes (dos viejas, para las alertas) y una finalizada.
     */
    private const COTIZACIONES = [
        [
            'cliente' => 'Bagley', 'categoria' => 'A', 'referencia' => 'PED-2026-118',
            'producto' => 'Flowpack bilaminado impreso Carrefour Caseras x 700g',
            'laminado' => 2, 'materiales' => [['Bopp Cristal', 20], ['Polietileno Blanco', 45]],
            'ancho' => 44, 'modulos_ancho' => 2, 'paso' => 58, 'desarrollo' => 50, 'buje' => 3,
            'cantidad' => 67000, 'impresion' => 'Si', 'colores' => 8, 'disenos' => 1, 'cambios' => 0,
            'porcentaje_impreso' => 100, 'blanco' => 100, 'laminacion' => 'Simple', 'solvente' => 'No',
            'impresion_scrap' => 3, 'laminacion_scrap' => 2,
            'entregas' => [['Caba', 3500, 40000], ['Quilmes', 3500, 27000]], 'dias_ff' => 30,
            'estado' => 'aprobada', 'hace_dias' => 0,
        ],
        [
            'cliente' => 'Arcor', 'categoria' => 'A', 'referencia' => 'OC 4471',
            'producto' => 'Bobina PE cristal para envasado automático',
            'laminado' => 1, 'materiales' => [['Polietileno Cristal', 60]],
            'ancho' => 35, 'modulos_ancho' => 2, 'paso' => 45, 'desarrollo' => 40, 'buje' => 3,
            'cantidad' => 20000, 'impresion' => 'No', 'colores' => 0, 'disenos' => 0, 'cambios' => 0,
            'porcentaje_impreso' => 0, 'blanco' => 0, 'laminacion' => '', 'solvente' => 'No',
            'impresion_scrap' => 0, 'laminacion_scrap' => 0,
            'entregas' => [['Munro', 900, 20000]], 'dias_ff' => 30,
            'estado' => 'aprobada', 'hace_dias' => 0,
        ],
        [
            'cliente' => 'Molinos Río de la Plata', 'categoria' => 'B', 'referencia' => 'Muestra harina 1kg',
            'producto' => 'Bobina trilaminada PET / Foil / PE',
            'laminado' => 3, 'materiales' => [['Poliester Cristal', 12], ['Foil Aluminio', 9], ['Polietileno Cristal', 50]],
            'ancho' => 30, 'modulos_ancho' => 3, 'paso' => 40, 'desarrollo' => 60, 'buje' => 6,
            'cantidad' => 35000, 'impresion' => 'Si', 'colores' => 6, 'disenos' => 2, 'cambios' => 1,
            'porcentaje_impreso' => 80, 'blanco' => 50, 'laminacion' => 'Bi.', 'solvente' => 'Si',
            'impresion_scrap' => 3, 'laminacion_scrap' => 2, 'bilaminacion_scrap' => 2,
            'entregas' => [['La Plata', 7000, 35000]], 'dias_ff' => 45,
            'estado' => 'pendiente', 'hace_dias' => 12,
        ],
        [
            'cliente' => 'La Serenísima', 'categoria' => 'A', 'referencia' => 'Renovación stock',
            'producto' => 'Bobina Bopp mate + PE para snacks',
            'laminado' => 2, 'materiales' => [['Bopp Mate', 20], ['Polietileno Cristal', 40]],
            'ancho' => 52, 'modulos_ancho' => 2, 'paso' => 60, 'desarrollo' => 52, 'buje' => 3,
            'cantidad' => 120000, 'impresion' => 'Si', 'colores' => 8, 'disenos' => 3, 'cambios' => 2,
            'porcentaje_impreso' => 100, 'blanco' => 100, 'laminacion' => 'Simple', 'solvente' => 'No',
            'impresion_scrap' => 3, 'laminacion_scrap' => 2,
            'entregas' => [['Ezeiza', 10000, 60000], ['Ezeiza', 10000, 60000]], 'dias_ff' => 60,
            'estado' => 'pendiente', 'hace_dias' => 9,
        ],
        [
            'cliente' => 'Bagley', 'categoria' => 'A', 'referencia' => 'PED-2026-131',
            'producto' => 'Bobina Bopp metalizado + Bopp cristal',
            'laminado' => 2, 'materiales' => [['Bopp Cristal', 20], ['Bopp Metalizado', 18]],
            'ancho' => 40, 'modulos_ancho' => 2, 'paso' => 50, 'desarrollo' => 48, 'buje' => 3,
            'cantidad' => 45000, 'impresion' => 'Si', 'colores' => 4, 'disenos' => 1, 'cambios' => 0,
            'porcentaje_impreso' => 60, 'blanco' => 0, 'laminacion' => 'Simple', 'solvente' => 'No',
            'impresion_scrap' => 3, 'laminacion_scrap' => 2,
            'entregas' => [['Quilmes', 3500, 45000]], 'dias_ff' => 30,
            'estado' => 'pendiente', 'hace_dias' => 2,
        ],
        [
            'cliente' => 'Arcor', 'categoria' => 'A', 'referencia' => 'OC 4390',
            'producto' => 'Bobina PE blanco impresa 2 colores',
            'laminado' => 1, 'materiales' => [['Polietileno Blanco', 70]],
            'ancho' => 38, 'modulos_ancho' => 2, 'paso' => 40, 'desarrollo' => 45, 'buje' => 3,
            'cantidad' => 15000, 'impresion' => 'Si', 'colores' => 2, 'disenos' => 1, 'cambios' => 0,
            'porcentaje_impreso' => 30, 'blanco' => 0, 'laminacion' => '', 'solvente' => 'No',
            'impresion_scrap' => 3, 'laminacion_scrap' => 0,
            'entregas' => [['Pilar', 900, 15000]], 'dias_ff' => 30,
            'estado' => 'finalizada', 'hace_dias' => 25,
        ],
    ];

    public function run(): void
    {
        if (Cotizacion::exists()) {
            $this->command?->warn('Ya hay cotizaciones: no se cargan las de ejemplo.');

            return;
        }

        // persistir() toma el autor de la actividad del usuario logueado si el vendedor no esta.
        if (! Auth::check() && ($usuario = User::first())) {
            Auth::login($usuario);
        }

        foreach (self::COTIZACIONES as $definicion) {
            $this->crear($definicion);
        }

        $this->command?->info(sprintf('cotizaciones de ejemplo: %d.', Cotizacion::count()));
    }

    /**
     * @param  array<string, mixed>  $d
     */
    private function crear(array $d): void
    {
        $cliente = Contacto::enEstado(Contacto::CLIENTE)->where('razon_social', $d['cliente'])->firstOrFail();
        $vendedor = $cliente->vendedor ?? Vendedor::firstOrFail();

        $form = app(Form::class);
        $form->mount();

        $form->cliente_id = $cliente->id;
        $form->vendedor_id = $vendedor->id;
        $form->categoria = $d['categoria'];
        $form->referencia = $d['referencia'];
        $form->fecha = now()->subDays($d['hace_dias'])->format('Y-m-d');
        $form->tipo_producto = 'bobinas';

        // El producto es del cliente: se crea si no lo tiene.
        $form->bobinas['producto_id'] = $cliente->productos()->firstOrCreate(['nombre' => $d['producto']])->id;

        // Cada campo pasa por el mismo hook que dispara el formulario, para que se recalcule todo.
        $this->set($form, 'laminado', $d['laminado']);
        $this->set($form, 'ancho', $d['ancho']);
        $this->set($form, 'modulos_ancho', $d['modulos_ancho']);

        foreach (['paso', 'desarrollo', 'buje', 'impresion', 'colores', 'disenos', 'cambios', 'porcentaje_impreso', 'blanco', 'laminacion', 'solvente', 'impresion_scrap', 'laminacion_scrap'] as $campo) {
            $this->set($form, $campo, $d[$campo]);
        }

        $this->set($form, 'bilaminacion_scrap', $d['bilaminacion_scrap'] ?? 0);

        foreach ($d['materiales'] as $indice => [$nombre, $mic]) {
            $item = InsumoItem::where('nombre', $nombre)->firstOrFail();
            $this->set($form, "materiales.{$indice}.material_id", $item->id);
            $this->set($form, "materiales.{$indice}.mic", $mic);
        }

        $this->set($form, 'cantidad', $d['cantidad']);

        // Entregas: zona, tramo y cantidad; la direccion del cliente en esa zona si la tiene.
        $form->entregas = [];
        foreach ($d['entregas'] as [$zona, $tramoKg, $cantidad]) {
            $zonaId = FleteZona::where('nombre', $zona)->value('id');
            $form->agregarEntrega();
            $indice = array_key_last($form->entregas);
            $form->entregas[$indice]['flete_zona_id'] = (string) $zonaId;
            $form->entregas[$indice]['flete_tramo_id'] = (string) FleteTramo::where('kg', $tramoKg)->value('id');
            $form->entregas[$indice]['cantidad'] = (string) $cantidad;
            $form->entregas[$indice]['direccion_id'] = (string) (ContactoDireccion::where('contacto_id', $cliente->id)->where('flete_zona_id', $zonaId)->value('id') ?? '');
        }

        $form->pagos[0]['valor_kgrs'] = 'Si';
        $form->pagos[1]['valor_kgrs'] = 'Si';
        $form->pagos[1]['dias_ff'] = (string) $d['dias_ff'];

        // Guardar sin redirigir: el metodo privado que usan guardar() y aprobar().
        $persistir = new ReflectionMethod($form, 'persistir');
        $persistir->invoke($form);

        $guardada = $form->guardada;
        $guardada->update(['created_at' => now()->subDays($d['hace_dias'])]);

        if ($d['estado'] === Cotizacion::APROBADA) {
            $guardada->update(['estado' => Cotizacion::APROBADA, 'aprobada_en' => now()->subHours(random_int(1, 6))]);
            $cliente->actividades()->create(['fecha' => now(), 'descripcion' => 'Se aprobó la cotización '.$guardada->numero, 'autor' => $vendedor->nombre]);
        } elseif ($d['estado'] === Cotizacion::FINALIZADA) {
            $guardada->update(['estado' => Cotizacion::FINALIZADA, 'aprobada_en' => now()->subDays($d['hace_dias'] - 3)]);
        }
    }

    /**
     * Setea un campo de bobinas y dispara el recalculo como si viniera del navegador.
     */
    private function set(Form $form, string $clave, mixed $valor): void
    {
        $valor = (string) $valor;

        if (str_starts_with($clave, 'materiales.')) {
            [, $indice, $campo] = explode('.', $clave);
            $form->bobinas['materiales'][(int) $indice][$campo] = $valor;
        } else {
            $form->bobinas[$clave] = $valor;
        }

        $form->updatedBobinas($valor, $clave);
    }
}
