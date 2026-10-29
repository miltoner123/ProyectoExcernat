<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // CONFIGURACIÓN DEL ADMINISTRADOR
        // ==========================================

        $password = env('EXCERNAT_ADMIN_PASSWORD');

        $adminExistente = User::where(
            'email',
            'admin@excernat.test'
        )->exists();

        if (
            !$adminExistente &&
            (!is_string($password) || strlen($password) < 12)
        ) {
            throw new RuntimeException(
                'Configure EXCERNAT_ADMIN_PASSWORD con al menos 12 caracteres en .env.'
            );
        }

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        DB::transaction(function () use ($password) {

            // ==========================================
            // 1. CATEGORÍAS
            // ==========================================

            $categorias = [
                [
                    'nombre' => 'Granolas',
                    'descripcion' =>
                        'Productos elaborados a base de cereales, miel y otros ingredientes',
                ],
                [
                    'nombre' => 'Multi-cereales',
                    'descripcion' =>
                        'Productos elaborados a base de diferentes cereales',
                ],
                [
                    'nombre' => 'Monocereales',
                    'descripcion' =>
                        'Productos elaborados principalmente a base de un cereal',
                ],
            ];

            foreach ($categorias as $categoria) {
                Categoria::firstOrCreate(
                    ['nombre' => $categoria['nombre']],
                    ['descripcion' => $categoria['descripcion']]
                );
            }

            // ==========================================
            // 2. PERSONA ADMINISTRADORA
            // ==========================================

            $persona = Persona::firstOrCreate(
                [
                    'numero_documento' =>
                        'ADM-EXCERNAT-001'
                ],
                [
                    'nombres_razon_social' =>
                        'Administrador EXCERNAT',
                    'tipo_persona' => 'PERSONAL',
                    'estado' => true,
                ]
            );

            // ==========================================
            // 3. USUARIO ADMINISTRADOR
            // ==========================================

            $admin = User::firstOrCreate(
                [
                    'email' => 'admin@excernat.test'
                ],
                [
                    'id_persona' => $persona->id_persona,
                    'name' => 'Administrador EXCERNAT',
                    'password' => Hash::make($password),
                    'estado' => true,
                ]
            );

            // ==========================================
            // 4. PERMISOS DEL SISTEMA
            // ==========================================

            $permisos = [

                // SEGURIDAD
                'usuarios.ver',
                'usuarios.crear',
                'usuarios.editar',
                'usuarios.roles',

                // PERSONAS Y PERSONAL
                'personas.ver',
                'personas.gestionar',
                'personal.ver',
                'personal.gestionar',

                // ASISTENCIAS
                'asistencias.ver',
                'asistencias.registrar',
                'asistencias.gestionar',

                // CATEGORÍAS
                'categorias.ver',
                'categorias.gestionar',

                // PRODUCTOS
                'productos.ver',
                'productos.gestionar',

                // PRESENTACIONES
                'presentaciones.ver',
                'presentaciones.gestionar',

                // LOTES Y PRODUCCIÓN
                'lotes.ver',
                'lotes.gestionar',
                'producciones.ver',
                'producciones.registrar',
                'producciones.aprobar',
                'producciones.rechazar',
                'recepciones.ver',
                'recepciones.confirmar',

                // UBICACIONES
                'ubicaciones.ver',
                'ubicaciones.gestionar',

                // INVENTARIOS
                'inventarios.ver',
                'inventarios.gestionar',

                // MOVIMIENTOS
                'movimientos.ver',
                'movimientos.registrar',
                'recepciones.ver',
                'recepciones.confirmar',

                // PEDIDOS DE ABASTECIMIENTO
                'pedidos.ver',
                'pedidos.gestionar',

                // VENTAS
                'ventas.ver',
                'ventas.registrar',
                'ventas.supervisar',

                // EVENTOS
                'eventos.ver',
                'eventos.gestionar',
                'eventos.asignar_personal',

                // COMPRAS
                'compras.ver',
                'compras.gestionar',

                // CONTABILIDAD
                'contabilidad.ver',
                'contabilidad.gestionar',

                // REPORTES
                'reportes.ver',
            ];

            foreach ($permisos as $nombre) {
                Permission::findOrCreate(
                    $nombre,
                    'web'
                );
            }

            // ==========================================
            // 5. ROLES DE EXCERNAT
            // ==========================================

            $roles = [

                // ADMINISTRADOR TÉCNICO
                'ADMIN' => $permisos,

                // GERENTE GENERAL
                'GERENTE_GENERAL' => [
                    'usuarios.ver',
                    'personas.ver',
                    'personal.ver',
                    'asistencias.ver',
                    'categorias.ver',
                    'productos.ver',
                    'presentaciones.ver',
                    'lotes.ver',
                    'producciones.ver',
                    'ubicaciones.ver',
                    'inventarios.ver',
                    'movimientos.ver',
                    'recepciones.ver',
                    'ventas.ver',
                    'eventos.ver',
                    'compras.ver',
                    'contabilidad.ver',
                    'reportes.ver',
                    'pedidos.ver',
                ],

                // ADMINISTRACIÓN
                'ADMINISTRADOR' => [
                    'personas.ver',
                    'personas.gestionar',
                    'personal.ver',
                    'personal.gestionar',
                    'asistencias.ver',
                    'asistencias.gestionar',
                    'contabilidad.ver',
                    'reportes.ver',
                    'pedidos.ver',
                ],

                // CONTABILIDAD
                'CONTABILIDAD' => [
                    'ventas.ver',
                    'compras.ver',
                    'contabilidad.ver',
                    'contabilidad.gestionar',
                    'reportes.ver',
                ],

                // ENCARGADO COMERCIAL
                'ENCARGADO_COMERCIAL' => [
                    'personas.ver',
                    'categorias.ver',
                    'productos.ver',
                    'presentaciones.ver',
                    'inventarios.ver',
                    'ventas.ver',
                    'ventas.supervisar',
                    'eventos.ver',
                    'eventos.gestionar',
                    'eventos.asignar_personal',
                    'compras.ver',
                    'compras.gestionar',
                    'reportes.ver',
                    'pedidos.ver',
                    'pedidos.gestionar',
                ],

                // ENCARGADO DE STAND O SUCURSAL
                'ENCARGADO_VENTAS' => [
                    'productos.ver',
                    'presentaciones.ver',
                    'ubicaciones.ver',
                    'inventarios.ver',
                    'ventas.ver',
                    'ventas.registrar',
                    'eventos.ver',
                    'pedidos.ver',
                    'pedidos.gestionar',
                ],

                // ENCARGADO DE OPERACIONES
                'ENCARGADO_ALMACEN' => [
                    'productos.ver', 'presentaciones.ver', 'lotes.ver',
                    'ubicaciones.ver', 'inventarios.ver', 'movimientos.ver',
                    'movimientos.registrar', 'recepciones.ver', 'recepciones.confirmar',
                    'pedidos.ver',
                ],

                'ENCARGADO_OPERACIONES' => [
                    'productos.ver',
                    'presentaciones.ver',
                    'lotes.ver',
                    'producciones.ver',
                    'producciones.registrar',
                    'producciones.aprobar',
                    'producciones.rechazar',
                    'ubicaciones.ver',
                    'ubicaciones.gestionar',
                    'inventarios.ver',
                    'inventarios.gestionar',
                    'movimientos.ver',
                    'movimientos.registrar',
                    'recepciones.ver',
                    'recepciones.confirmar',
                    'reportes.ver',
                    'pedidos.ver',
                    'pedidos.gestionar',
                ],

                // RESPONSABLE DE RECEPCIÓN EN ALMACÉN CENTRAL
                'ENCARGADO_ALMACEN' => [
                    'productos.ver', 'presentaciones.ver', 'lotes.ver',
                    'ubicaciones.ver', 'inventarios.ver', 'inventarios.gestionar',
                    'movimientos.ver', 'movimientos.registrar',
                    'recepciones.ver', 'recepciones.confirmar',
                    'pedidos.ver',
                ],

                // PERSONAL DE PRODUCCIÓN (SIN PERMISO DE CONFIRMAR RECEPCIONES)
                'OPERARIO_PRODUCCION' => [
                    'productos.ver', 'presentaciones.ver', 'lotes.ver',
                    'lotes.gestionar', 'producciones.ver', 'producciones.registrar',
                ],

                // SUBENCARGADO DE PRODUCCIÓN
                'SUBENCARGADO_PRODUCCION' => [
                    'productos.ver',
                    'presentaciones.ver',
                    'lotes.ver',
                    'lotes.gestionar',
                    'producciones.ver',
                    'producciones.registrar',
                    'inventarios.ver',
                ],

                // ENCARGADO DE SISTEMAS
                'ENCARGADO_SISTEMAS' => [
                    'usuarios.ver',
                    'usuarios.crear',
                    'usuarios.editar',
                    'usuarios.roles',
                    'personas.ver',
                    'reportes.ver',
                ],
            ];

            foreach ($roles as $nombre => $permisosRol) {
                $rol = Role::findOrCreate(
                    $nombre,
                    'web'
                );

                $rol->syncPermissions($permisosRol);
            }

            // ==========================================
            // 6. ASIGNAR ROL ADMIN
            // ==========================================

            $admin->assignRole('ADMIN');

            $this->command?->info(
                'Administrador: ' . $admin->email
            );

            $this->command?->info(
                'Roles configurados: ' . count($roles)
            );

            $this->command?->info(
                'Permisos configurados: ' . count($permisos)
            );
        });

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
