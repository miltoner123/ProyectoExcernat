<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Categoria;
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

        Categoria::create([
            'nombre' => 'Granolas',
            'descripcion' => 'Productos secos'
        ]);
        Categoria::create([
            'nombre' => 'Multi-cereales',
            'descripcion' => 'Productos líquidos hechos de varios cereales'
        ]);
        Categoria::create([
            'nombre' => 'Monocereales',
            'descripcion' => 'Productos líquidos hechos de un solo cereal'
        ]);

        Producto::create([
            'nombre' => 'Nutrigranola',
            'descripcion' => 'Granola hecha de avena, miel y frutos secos',
            'imagen' => null,
            'categoria_id' => 1,
            'activo' => true
        ]);
        for ($i = 1; $i <= 20; $i++) {
            Producto::create([
                'nombre' => 'Producto ' . $i,
                'descripcion' => 'Descripción del producto ' . $i,
                'imagen' => null,
                'categoria_id' => rand(1, 3),
                'activo' => true
            ]);
        }
    }
}
