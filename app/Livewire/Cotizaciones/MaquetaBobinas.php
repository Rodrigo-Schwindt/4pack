<?php

namespace App\Livewire\Cotizaciones;

/**
 * Valores de las solapas de Bobinas tal como los muestra la maqueta.
 *
 * Todo lo que hay aca sale de un calculo sobre los datos cargados: la clase
 * existe para poder ver y revisar la pantalla, y desaparece cuando definamos
 * las formulas.
 */
class MaquetaBobinas
{
    /**
     * Secciones que son una tabla comun. La primera columna es el contador de
     * la izquierda y la segunda el detalle; el resto va alineado a la derecha.
     *
     * @return list<array{titulo: string, columnas: list<string>, filas: list<list<string>>}>
     */
    public static function secciones(): array
    {
        return [
            [
                'titulo' => 'Proveedores',
                'columnas' => ['', 'Detalle', 'Mic', 'Valor x kgrs', 'Mts a trabajar', 'Kgrs a trabajar', 'Kgrs x 1000 mts', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor x kgrs', 'Incidencia'],
                'filas' => [
                    ['1', 'BoppCristal', '20', 'U$S 3,80', '68.500', '1.122,03', '16,38', '16,02', 'U$S 63,64', 'U$S 1,99', '33,05%'],
                    ['1', 'BoppCristal', '20', 'U$S 3,80', '67.100', '1.099,10', '16,38', '16,02', 'U$S 62,34', 'U$S 1,95', '32,37%'],
                    ['1', 'Otro', '0', 'U$S 0,00', '67.100', '0,00', '0,00', '0,00', 'U$S 0,00', 'U$S 0,00', '0,00%'],
                ],
            ],
            [
                'titulo' => 'Impresión y Reprint',
                'columnas' => ['', 'Detalle', 'Prep', 'Prod', '', '', 'Valor hs', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor kgrs', 'Incidencia'],
                'filas' => [
                    ['0', 'Reprint', '1,5', '3,35', '0', '0', 'U$S 58,46', '', 'U$S 4,23', 'U$S 0,13', '2,20%'],
                    ['1', 'Tela Doble Fax', '', '', '0,000', '', 'x Color U$S', '12,728', '', 'U$S 0,05', '0,79%'],
                    ['1', 'Tintas + Diluyentes', '', '', '0,000', '', 'Valor grs/mts2', '0,032', 'U$S 28,91', 'U$S 0,90', '15,01%'],
                    ['1', 'Limpieza Diluyentes', '24', '', '', '', 'Valor Lts', '1,250', 'U$S 0,448', 'U$S 0,01', '0,23%'],
                ],
            ],
            [
                'titulo' => 'Laminación y solventes',
                'columnas' => ['', 'Detalle', 'Prep', 'Prod', '', '', 'Valor hs', 'Kgrs x 1000 mts', 'Valor x 1000 mts', 'Valor kgrs', 'Incidencia'],
                'filas' => [
                    ['1', 'Costo laminado', '0,5', '4,19', '', '', 'U$S 41,35', '', 'U$S 2,89', 'U$S 0,09', '1,50%'],
                    ['1', 'Sin solvente', '', '', '', '', 'Valor grs/mts2', '0,014', 'U$S 12,92', 'U$S 0,40', '6,71%'],
                    ['0', 'Con solvente', '', '', '', '', 'Valor grs/mts2', '0,000', 'U$S 0,00', 'U$S 0,00', '0,00%'],
                ],
            ],
            [
                'titulo' => 'Refilado y Material Scrap',
                'columnas' => ['', 'Detalle', 'Prep', 'Prod', '', 'Valor hs', 'Valor x kgrs', 'Valor kgrs', 'Incidencia'],
                'filas' => [
                    ['1', 'Refilado', '0', '8,38', '', 'U$S 39,54', 'U$S 4,94', 'U$S 0,15', '2,57%'],
                    ['5%', 'Material Scrap', '', '', '', '', '', 'U$S 0,28', '4,72%'],
                ],
            ],
            [
                'titulo' => 'Otros costos',
                'columnas' => ['', 'Detalle', '', '', '', 'Valor kgrs', 'Incidencia'],
                'filas' => [
                    ['1', 'Flete', 'Caba', '3500', '109,31', 'U$S 0,21', '2,45%'],
                ],
            ],
        ];
    }

    /**
     * Rentabilidad, financiado y costo bruto: tres bloques que comparten filas.
     *
     * @return array{costo_bruto: string, filas: list<array<string, string>>}
     */
    public static function rentabilidad(): array
    {
        return [
            'costo_bruto' => 'U$S 6,01',
            'filas' => [
                ['porcentaje' => '17,5', 'detalle' => '% Plus', 'origen' => 'Clientes', 'valor' => 'A', 'importe' => 'U$S 2.804,72', 'por_kg' => '1,31 xKg', 'financiado' => '2,15', 'bruto' => 'U$S 1,31'],
                ['porcentaje' => '0', 'detalle' => '%', 'origen' => 'Ajuste', 'valor' => '0', 'importe' => 'U$S 5.609,43', 'por_kg' => '2,61 xKg', 'financiado' => '988,33', 'bruto' => 'U$S 1,31'],
                ['porcentaje' => '2,00', 'detalle' => '% Comisión', 'origen' => '', 'valor' => 'AK', 'importe' => 'U$S 320,54', 'por_kg' => '0,15 xKg', 'financiado' => '4.621,10', 'bruto' => 'U$S 0,15'],
                ['porcentaje' => '0%', 'detalle' => 'Polímeros', 'origen' => '', 'valor' => '100%', 'importe' => 'U$S 2.098,80', 'por_kg' => '0,98 xKg', 'financiado' => '', 'bruto' => 'U$S 00,00'],
                ['porcentaje' => '', 'detalle' => 'Rentabilidad bonificando polímeros', 'origen' => '', 'valor' => '', 'importe' => 'U$S 5.609,43', 'por_kg' => '2,61 xKg', 'financiado' => '', 'bruto' => 'U$S 00,00'],
            ],
        ];
    }

    /**
     * Renglones de la orden de compra: lo cotizado, ya resuelto.
     *
     * @return array{columnas: list<string>, filas: list<array<string, mixed>>}
     */
    public static function ordenCompra(): array
    {
        return [
            'columnas' => ['Tipo de producto', 'Producto', 'Características', 'Cantidad', 'Precio unitario', 'Importe total'],
            'filas' => [
                [
                    'tipo' => 'Bobina',
                    'producto' => 'Flowpack Bilaminado Impreso Carrefour Caseras x 700g',
                    'caracteristicas' => [
                        'Materiales: Bopp Mate de 20 mic + Pe Blanco 45mic',
                        'Ancho de bobina: 580 mm buje 3´´',
                        'Paso: 580mm',
                        'Impresión: 8 colores con fotocromia HD',
                        'Laminación: Libre de solventes Apto alimentos',
                    ],
                    'cantidad' => '67.000kg',
                    'precio_unitario' => 'USD 57,50',
                    'importe_total' => 'USD 862,50',
                ],
            ],
        ];
    }

    /**
     * Conclusiones de la solapa Entrega: cumplimiento y comision por entrega.
     *
     * @return array{columnas: list<string>, filas: list<list<string>>}
     */
    public static function conclusiones(): array
    {
        return [
            'columnas' => ['N° entrega', 'Cumplimiento de la entrega', 'Comisión proyectada', 'Comisión real'],
            'filas' => [
                ['Entrega 1', '99,2%', 'U$S 10,00', 'U$S 9,80'],
                ['Entrega 2', '101,1%', 'U$S 10,00', 'U$S 11,10'],
                ['Total', '100,8%', 'U$S 20,00', 'U$S 20,90'],
            ],
        ];
    }

    /**
     * Costo final: las mismas condiciones de pago del tab de datos, ya resueltas.
     *
     * @return array{columnas: list<string>, filas: list<list<string>>}
     */
    public static function costoFinal(): array
    {
        return [
            'columnas' => ['Detalle', 'Días FF', 'Ajuste cambiario', 'Financiación bancaria', 'Costo total'],
            'filas' => [
                ['Valor por Kgrs al contado', '', '', '', 'U$S 7,47'],
                ['Valor por Kgrs a', '30', '4,17%', 'U$S 0,31', 'U$S 7,78'],
            ],
        ];
    }
}
