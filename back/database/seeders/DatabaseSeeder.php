<?php

namespace Database\Seeders;


use App\Models\Categoria;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos iniciales de EXCERNAT.
     *
     * Este seeder NO genera datos aleatorios.
     *
     * Los productos, presentaciones, lotes, eventos,
     * ubicaciones e inventarios se registrarán
     * con información real de la empresa.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | CATEGORÍAS DE PRODUCTOS
        |--------------------------------------------------------------------------
        | Se mantienen las categorías ya definidas para EXCERNAT.
        */

        Categoria::create([
            'nombre' => 'Granolas',
            'descripcion' => 'Productos elaborados a base de cereales, miel y otros ingredientes',
        ]);

        Categoria::create([
            'nombre' => 'Multi-cereales',
            'descripcion' => 'Productos elaborados a base de diferentes cereales',
        ]);

        Categoria::create([
            'nombre' => 'Monocereales',
            'descripcion' => 'Productos elaborados principalmente a base de un cereal',
        ]);

        /*
        |--------------------------------------------------------------------------
        | PRODUCTOS
        |--------------------------------------------------------------------------
        | Sin registros automáticos.
        | Se ingresarán los productos reales desde el sistema.
        */

        /*
        |--------------------------------------------------------------------------
        | PRESENTACIONES
        |--------------------------------------------------------------------------
        | Sin registros automáticos.
        | Se ingresarán los SKU, pesos, envases,
        | cantidades por paquete y precios reales.
        */

        /*
        |--------------------------------------------------------------------------
        | LOTES
        |--------------------------------------------------------------------------
        | Sin registros automáticos.
        | Cada lote se creará a partir de una producción real.
        */

        /*
        |--------------------------------------------------------------------------
        | EVENTOS
        |--------------------------------------------------------------------------
        | Sin registros automáticos.
        | Se registrarán las ferias y eventos reales.
        */

        /*
        |--------------------------------------------------------------------------
        | UBICACIONES
        |--------------------------------------------------------------------------
        | Sin registros automáticos.
        | Se registrarán los almacenes, sucursales
        | y stands reales.
        */

        /*
        |--------------------------------------------------------------------------
        | INVENTARIOS
        |--------------------------------------------------------------------------
        | Sin registros automáticos.
        | Las existencias se registrarán mediante
        | operaciones reales de inventario.
        */
    }
}
