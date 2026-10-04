<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Presentacion;
use App\Models\Evento;
use App\Models\Lote;
use App\Models\Ubicacion;
use App\Models\Inventario;
use Carbon\Carbon;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        /*
        |--------------------------------------------------------------------------
        | USUARIO DE PRUEBA
        |--------------------------------------------------------------------------
        */

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);


        /*
        |--------------------------------------------------------------------------
        | CATEGORÍAS
        |--------------------------------------------------------------------------
        */

        Categoria::create([
            'nombre' => 'Granolas',
            'descripcion' => 'Productos elaborados a base de cereales, miel y otros ingredientes'
        ]);

        Categoria::create([
            'nombre' => 'Multi-cereales',
            'descripcion' => 'Productos elaborados a base de diferentes cereales'
        ]);

        Categoria::create([
            'nombre' => 'Monocereales',
            'descripcion' => 'Productos elaborados principalmente a base de un cereal'
        ]);


        /*
        |--------------------------------------------------------------------------
        | PRODUCTO PRINCIPAL
        |--------------------------------------------------------------------------
        */

        Producto::create([
            'nombre' => 'Nutrigranola',
            'descripcion' => 'Granola hecha de avena, miel y frutos secos',
            'imagen' => null,
            'categoria_id' => 1,
            'activo' => true
        ]);


        /*
        |--------------------------------------------------------------------------
        | PRODUCTOS DE PRUEBA
        |--------------------------------------------------------------------------
        */

        for ($i = 1; $i <= 20; $i++) {

            Producto::create([

                'nombre' => 'Producto ' . $i,

                'descripcion' =>
                    'Descripción del producto ' . $i,

                'imagen' => null,

                'categoria_id' => rand(1, 3),

                'activo' => true

            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | OBTENER PRODUCTOS EXISTENTES
        |--------------------------------------------------------------------------
        */

        $productos = Producto::pluck('id')->values();


        /*
        |--------------------------------------------------------------------------
        | PRESENTACIONES
        |--------------------------------------------------------------------------
        */

        for ($i = 1; $i <= 20; $i++) {

            /*
             * Distribuye las presentaciones entre
             * los productos existentes.
             */
            $idProducto =
                $productos[
                    ($i - 1) % $productos->count()
                ];


            /*
             * Alternamos distintos tipos de presentación
             * para tener mejores datos de prueba.
             */

            if ($i % 3 == 0) {

                // Presentación escolar

                $peso = 10;
                $envase = 'Bolsa';
                $unidadesPaquete = 30;
                $precioUnitario = 1;
                $precioPaquete = 20;

            } elseif ($i % 2 == 0) {

                // Presentación de 500 gramos

                $peso = 500;
                $envase = 'Caja';
                $unidadesPaquete = 26;
                $precioUnitario = 35;
                $precioPaquete = 780;

            } else {

                // Presentación de 100 gramos

                $peso = 100;
                $envase = 'Bolsa';
                $unidadesPaquete = 25;
                $precioUnitario = 15;
                $precioPaquete = 375;

            }


            Presentacion::create([

                'id_producto' => $idProducto,

                'codigo_sku' =>
                    'SKU-' .
                    str_pad(
                        $i,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),

                'nombre_presentacion' =>
                    $peso . ' g - ' . $envase,

                'peso_neto' =>
                    $peso,

                'unidad_medida' =>
                    'g',

                'tipo_envase' =>
                    $envase,

                'unidades_por_paquete' =>
                    $unidadesPaquete,

                'precio_unitario' =>
                    $precioUnitario,

                'precio_paquete' =>
                    $precioPaquete,

                'estado' =>
                    true

            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | OBTENER PRESENTACIONES EXISTENTES
        |--------------------------------------------------------------------------
        */

        $presentaciones =
            Presentacion::pluck(
                'id_presentacion'
            )->values();


        /*
        |--------------------------------------------------------------------------
        | LOTES
        |--------------------------------------------------------------------------
        */

        for ($i = 1; $i <= 20; $i++) {

            /*
             * Cada lote tendrá una presentación existente.
             */

            $idPresentacion =
                $presentaciones[
                    ($i - 1) %
                    $presentaciones->count()
                ];


            /*
             * Generamos diferentes fechas
             * de producción.
             */

            $fechaProduccion =
                Carbon::now()
                    ->subDays($i * 5);


            /*
             * El vencimiento será un año
             * después de la producción.
             */

            $fechaVencimiento =
                $fechaProduccion
                    ->copy()
                    ->addYear();


            Lote::create([

                'id_presentacion' =>
                    $idPresentacion,

                'codigo_lote' =>
                    'LOTE-' .
                    str_pad(
                        $i,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),

                'fecha_produccion' =>
                    $fechaProduccion
                        ->format('Y-m-d'),

                'fecha_vencimiento' =>
                    $fechaVencimiento
                        ->format('Y-m-d'),

                'cantidad_producida' =>
                    rand(500, 2000),

                'estado' =>
                    true,

                'observacion' =>
                    'Lote de prueba número ' . $i

            ]);
            /*
|--------------------------------------------------------------------------
| EVENTOS DE PRUEBA
|--------------------------------------------------------------------------
*/

        Evento::create([
            'nombre' => 'FIPAZ 2026',
            'tipo' => 'FERIA NACIONAL',
            'departamento' => 'La Paz',
            'lugar' => 'Campo Ferial Chuquiago Marka',
            'fecha_inicio' => '2026-10-10',
            'fecha_fin' => '2026-10-19',
            'estado' => 'PLANIFICADO',
            'observacion' => 'Evento de prueba para gestión de stands'
        ]);

        Evento::create([
            'nombre' => 'Feria Productiva Cochabamba 2026',
            'tipo' => 'FERIA NACIONAL',
            'departamento' => 'Cochabamba',
            'lugar' => 'Recinto Ferial',
            'fecha_inicio' => '2026-11-05',
            'fecha_fin' => '2026-11-14',
            'estado' => 'PLANIFICADO',
            'observacion' => 'Evento de prueba'
        ]);
        /*
|--------------------------------------------------------------------------
| UBICACIONES DE PRUEBA
|--------------------------------------------------------------------------
*/

// ALMACÉN CENTRAL

    Ubicacion::create([
        'id_evento' => null,
        'nombre' => 'Almacén Central EXCERNAT',
        'tipo' => 'ALMACEN',
        'propiedad' => 'PROPIA',
        'departamento' => 'La Paz',
        'direccion' => 'El Alto',
        'estado' => true
    ]);


    // SUCURSAL PROPIA

    Ubicacion::create([
        'id_evento' => null,
        'nombre' => 'Sucursal Ceja',
        'tipo' => 'SUCURSAL',
        'propiedad' => 'PROPIA',
        'departamento' => 'La Paz',
        'direccion' => 'Ceja de El Alto, Calle 4',
        'estado' => true
    ]);


    // SUCURSAL EXTERNA

    Ubicacion::create([
        'id_evento' => null,
        'nombre' => 'Tienda Irupana - Mercado Rodríguez',
        'tipo' => 'SUCURSAL',
        'propiedad' => 'EXTERNA',
        'departamento' => 'La Paz',
        'direccion' => 'Mercado Rodríguez',
        'estado' => true
    ]);


    // STANDS DEL EVENTO 1

    for ($i = 1; $i <= 3; $i++) {

        Ubicacion::create([

            'id_evento' => 1,
            'nombre' => 'Stand ' . $i . ' - FIPAZ 2026',
            'tipo' => 'STAND',
            'propiedad' =>'TEMPORAL',
            'departamento' =>'La Paz',
            'direccion' =>'Campo Ferial Chuquiago Marka',
            'estado' =>true

        ]);
    }

    
// --------------------------------------------------
// INVENTARIOS DE PRUEBA
// --------------------------------------------------

$ubicaciones = \App\Models\Ubicacion::pluck('id_ubicacion')->values();

$lotes = \App\Models\Lote::pluck('id_lote')->values();

if ($ubicaciones->isNotEmpty() && $lotes->isNotEmpty()) {

    for ($i = 1; $i <= 20; $i++) {

        $idUbicacion = $ubicaciones[
            ($i - 1) % $ubicaciones->count()
        ];

        $idLote = $lotes[
            ($i - 1) % $lotes->count()
        ];


        Inventario::firstOrCreate(

            [
                'id_ubicacion' => $idUbicacion,
                'id_lote' => $idLote,
            ],

            [
                'stock_actual' => rand(0, 500),
                'stock_minimo' => rand(20, 100),
            ]

        );

    }
}


        }

    }
}