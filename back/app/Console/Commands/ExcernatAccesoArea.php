<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class ExcernatAccesoArea extends Command
{
    protected $signature = 'excernat:acceso-area {email} {area : produccion|almacen|comercial|administracion|consulta} {--ejecutar}';
    protected $description = 'Previsualiza y asigna permisos por área a un usuario ENCARGADO, sin cambiar su cargo.';

    public function handle(): int
    {
        $areas = [
            'produccion' => ['productos.ver','presentaciones.ver','lotes.ver','lotes.gestionar','producciones.ver','producciones.registrar'],
            'almacen' => ['productos.ver','presentaciones.ver','lotes.ver','ubicaciones.ver','inventarios.ver','movimientos.ver','movimientos.registrar','recepciones.ver','recepciones.confirmar','transferencias.recibir','pedidos.ver'],
            'comercial' => ['productos.ver','presentaciones.ver','inventarios.ver','ventas.ver','ventas.supervisar','cobros.ver','cobros.supervisar','eventos.ver','eventos.gestionar','eventos.asignar_personal','pedidos.ver','reportes.ver'],
            'administracion' => ['personas.ver','personal.ver','asistencias.ver','ventas.ver','cobros.ver','cobros.supervisar','contabilidad.ver','contabilidad.gestionar','reportes.ver'],
            'consulta' => ['productos.ver','presentaciones.ver','ubicaciones.ver','eventos.ver'],
        ];
        $area = strtolower($this->argument('area'));
        if (!isset($areas[$area])) { $this->error('Área inválida.'); return self::FAILURE; }
        $usuario = User::where('email', $this->argument('email'))->first();
        if (!$usuario) { $this->error('Usuario no encontrado.'); return self::FAILURE; }
        if ($usuario->hasRole('ADMIN')) { $this->error('No se modifica ADMIN.'); return self::FAILURE; }
        $this->info("Usuario: {$usuario->email}; área: {$area}");
        $this->line(implode(', ', $areas[$area]));
        $this->warn('Reemplaza los permisos directos y fija rol ENCARGADO (CONSULTA si área=consulta).');
        if (!$this->option('ejecutar')) { $this->info('Vista previa. Añada --ejecutar para aplicar.'); return self::SUCCESS; }
        if (!$this->confirm('¿Aplicar estos permisos al usuario?', false)) { return self::SUCCESS; }
        $faltantes = array_diff($areas[$area], Permission::where('guard_name','web')->pluck('name')->all());
        if ($faltantes) { $this->error('Permisos no existentes: '.implode(', ', $faltantes)); return self::FAILURE; }
        $usuario->syncRoles([$area === 'consulta' ? 'CONSULTA' : 'ENCARGADO']);
        $usuario->syncPermissions($areas[$area]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('Permisos actualizados. El usuario debe volver a iniciar sesión.');
        return self::SUCCESS;
    }
}
