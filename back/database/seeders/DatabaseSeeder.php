<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CategoriaProducto;
use App\Models\Producto;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        CategoriaProducto::create([
            'nombre' => 'Granolas',
            'descripcion' => 'Productos secos'
        ]);
        CategoriaProducto::create([
            'nombre' => 'Multi-cereales',
            'descripcion' => 'Productos líquidos hechos de varios cereales'
        ]);
        CategoriaProducto::create([
            'nombre' => 'Monocereales',
            'descripcion' => 'Productos líquidos hechos de un solo cereal'
        ]);

        Producto::create([
            'nombre' => 'Nutrigranola',
            'descripcion' => 'Granola hecha de avena, miel y frutos secos',
            'imagen' => null,
            'categoria_producto_id' => 1
        ]);
    }
}
