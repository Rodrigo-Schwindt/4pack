<?php

namespace Database\Seeders;

use App\Livewire\Cotizaciones\Form;
use App\Models\Contacto;
use App\Models\ContactoDireccion;
use App\Models\Cotizacion;
use App\Models\FleteTramo;
use App\Models\FleteZona;
use App\Models\InsumoItem;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use ReflectionMethod;

/**
 * Cuatro cotizaciones de Confeccion DPK de ejemplo, completas: tres materiales
 * con su proveedor, impresion con reprint, bilaminado con solvente, refilado,
 * zipper, troquel, pico y extras. Se cargan por el mismo formulario para que
 * los calculos y los textos queden como hechos a mano. Solo corre si todavia
 * no hay ninguna cotizacion de DPK.
 */
class CotizacionesDpkDemoSeeder extends Seeder
{
    /**
     * Cada una: cliente, materiales [nombre, mic, proveedor] y el resto de los
     * datos del envase. El desarrollo (ancho x modulos) cae siempre en una
     * manga cargada en Ajustes.
     */
    private const COTIZACIONES = [
        [
            'cliente' => 'Bagley', 'categoria' => 'A', 'referencia' => 'DPK-2026-041',
            'producto' => 'Doypack galletitas Sonrisas x 300g con zipper',
            'ancho' => 15, 'alto' => 22, 'fuelle' => 8, 'modulos_ancho' => 1, 'calle' => 0, 'modulos_desarrollo' => 3,
            'materiales' => [['Poliester Cristal', 12, 'Enimar'], ['Bopp Metalizado', 18, 'Vitopel'], ['Polietileno Dpk', 90, 'Polifilm']],
            'envases' => 30000, 'colores' => 8, 'disenos' => 1, 'variedades' => 1, 'cambios' => 0,
            'porcentaje_impreso' => 100, 'blanco' => 100, 'bonifica_polimeros' => 10,
            'impresion_scrap' => 3, 'laminacion_scrap' => 2, 'bilaminacion_scrap' => 2, 'extras_usd' => 120,
            'entregas' => [['Caba', 3500, 30000]], 'dias_ff' => 30,
            'estado' => 'aprobada', 'hace_dias' => 0,
        ],
        [
            'cliente' => 'Arcor', 'categoria' => 'A', 'referencia' => 'OC 4512',
            'producto' => 'Doypack caramelos Butter Toffees x 150g',
            'ancho' => 11, 'alto' => 17, 'fuelle' => 7, 'modulos_ancho' => 2, 'calle' => 1, 'modulos_desarrollo' => 4,
            'materiales' => [['Poliester Cristal', 12, 'Silvapack'], ['Bopp Mate', 20, 'Vitopel'], ['Polietileno Cristal', 80, 'Inepol']],
            'envases' => 50000, 'colores' => 6, 'disenos' => 2, 'variedades' => 2, 'cambios' => 1,
            'porcentaje_impreso' => 100, 'blanco' => 80, 'bonifica_polimeros' => 5,
            'impresion_scrap' => 3, 'laminacion_scrap' => 2, 'bilaminacion_scrap' => 2, 'extras_usd' => 80,
            'entregas' => [['Quilmes', 3500, 35000], ['Munro', 900, 15000]], 'dias_ff' => 45,
            'estado' => 'pendiente', 'hace_dias' => 3,
        ],
        [
            'cliente' => 'Molinos Río de la Plata', 'categoria' => 'B', 'referencia' => 'Premezcla 1kg con pico',
            'producto' => 'Doypack premezcla Exquisita x 1kg con pico dosificador',
            'ancho' => 20, 'alto' => 30, 'fuelle' => 10, 'modulos_ancho' => 1, 'calle' => 0, 'modulos_desarrollo' => 2,
            'materiales' => [['Poliester Cristal', 12, 'Enimar'], ['Polietileno EVOH Bco', 40, 'Plastiandino'], ['Polietileno Dpk', 120, 'Polifilm']],
            'envases' => 20000, 'colores' => 7, 'disenos' => 1, 'variedades' => 1, 'cambios' => 0,
            'porcentaje_impreso' => 90, 'blanco' => 100, 'bonifica_polimeros' => 0,
            'impresion_scrap' => 3, 'laminacion_scrap' => 2, 'bilaminacion_scrap' => 2, 'extras_usd' => 250,
            'entregas' => [['La Plata', 7000, 20000]], 'dias_ff' => 60,
            'estado' => 'pendiente', 'hace_dias' => 10,
        ],
        [
            'cliente' => 'La Serenísima', 'categoria' => 'A', 'referencia' => 'Renovación rallado',
            'producto' => 'Doypack queso rallado x 250g con zipper',
            'ancho' => 12, 'alto' => 20, 'fuelle' => 6, 'modulos_ancho' => 2, 'calle' => 0.5, 'modulos_desarrollo' => 4,
            'materiales' => [['Poliester Metalizado', 12, 'Silvapack'], ['Polietileno Blanco', 70, 'Plastiandino'], ['Polietileno Cristal', 50, 'Polifilm']],
            'envases' => 80000, 'colores' => 5, 'disenos' => 3, 'variedades' => 3, 'cambios' => 2,
            'porcentaje_impreso' => 70, 'blanco' => 60, 'bonifica_polimeros' => 15,
            'impresion_scrap' => 3, 'laminacion_scrap' => 2, 'bilaminacion_scrap' => 2, 'extras_usd' => 60,
            'entregas' => [['Ezeiza', 10000, 50000], ['Pilar', 3500, 30000]], 'dias_ff' => 30,
            'estado' => 'finalizada', 'hace_dias' => 20,
        ],
    ];

    public function run(): void
    {
        if (Cotizacion::where('tipo_producto', 'confeccion-dpk')->exists()) {
            $this->command?->warn('Ya hay cotizaciones de DPK: no se cargan las de ejemplo.');

            return;
        }

        if (! Auth::check() && ($usuario = User::first())) {
            Auth::login($usuario);
        }

        foreach (self::COTIZACIONES as $definicion) {
            $this->crear($definicion);
        }

        $this->command?->info(sprintf('cotizaciones DPK de ejemplo: %d.', Cotizacion::where('tipo_producto', 'confeccion-dpk')->count()));
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
        $form->tipo_producto = 'confeccion-dpk';

        $form->bobinas['producto_id'] = $cliente->productos()->firstOrCreate(['nombre' => $d['producto']])->id;

        // Medidas del envase y modulos: de aca salen paso, desarrollo, anchos y metros.
        foreach (['ancho', 'alto', 'fuelle', 'modulos_ancho', 'calle', 'modulos_desarrollo'] as $campo) {
            $this->set($form, $campo, $d[$campo]);
        }

        // Todo en Si: impresion con reprint, refilado, bilaminado con solvente, liquido, envio.
        $this->set($form, 'laminado', 3);
        foreach (['impresion' => 'Si', 'reprint' => 'Si', 'refilado' => 'Si', 'contiene_liquido' => 'Si', 'laminacion' => 'Bi.', 'solvente' => 'Si', 'forma_entrega' => Form::ENVIO, 'peso_neto' => 'Si'] as $campo => $valor) {
            $this->set($form, $campo, $valor);
        }

        foreach (['colores', 'disenos', 'variedades', 'cambios', 'porcentaje_impreso', 'blanco', 'bonifica_polimeros'] as $campo) {
            $this->set($form, $campo, $d[$campo]);
        }

        // Tres materiales, cada uno con su micraje y el proveedor que lo cotiza.
        foreach ($d['materiales'] as $indice => [$nombre, $mic, $proveedor]) {
            $item = InsumoItem::where('nombre', $nombre)->firstOrFail();
            $this->set($form, "materiales.{$indice}.material_id", $item->id);
            $this->set($form, "materiales.{$indice}.mic", $mic);
            $this->set($form, "materiales.{$indice}.proveedor_id", Proveedor::where('nombre', $proveedor)->firstOrFail()->id);
        }

        $this->set($form, 'envases', $d['envases']);

        foreach (['impresion_scrap', 'laminacion_scrap', 'bilaminacion_scrap'] as $campo) {
            $this->set($form, $campo, $d[$campo]);
        }

        // Accesorios: zipper, troquel y pico con el tipo cargado en Insumos, mas extras en U$S.
        foreach (['zipper' => ['tipo_zipper_id', 'Zipper'], 'troquel' => ['tipo_troquel_id', 'Troquel'], 'pico' => ['tipo_pico_id', 'Pico']] as $campo => [$tipo, $item]) {
            $this->set($form, $campo, 'Si');
            $this->set($form, $tipo, InsumoItem::where('nombre', $item)->firstOrFail()->id);
        }
        $this->set($form, 'extras', 'Si');
        $this->set($form, 'extras_usd', $d['extras_usd']);

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
     * Setea un campo y dispara el recalculo como si viniera del navegador.
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
