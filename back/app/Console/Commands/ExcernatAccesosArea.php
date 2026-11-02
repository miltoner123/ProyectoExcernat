<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ExcernatAccesosArea extends Command
{
    protected $signature = 'excernat:accesos-area {email} {area : produccion|almacen|comercial|contabilidad|administracion|consulta|stand} {--aplicar : Guardar cambios}';
    protected $description = 'Vista previa y asignacion segura de permisos por area para usuarios no ADMIN';

    public function handle(): int
    {
        $perfiles = [
            'produccion' => ['productos.ver','presentaciones.ver','lotes.ver','lotes.gestionar','producciones.ver','producciones.registrar','inventarios.ver'],
            'almacen' => ['productos.ver','presentaciones.ver','lotes.ver','ubicaciones.ver','inventarios.ver','movimientos.ver','movimientos.registrar','recepciones.ver','recepciones.confirmar','transferencias.recibir','pedidos.ver'],
            'comercial' => ['personas.ver','personal.ver','productos.ver','presentaciones.ver','ubicaciones.ver','inventarios.ver','eventos.ver','eventos.gestionar','eventos.asignar_personal','pedidos.ver','pedidos.gestionar','ventas.ver','cobros.ver','reportes.ver'],
            'contabilidad' => ['ventas.ver','cobros.ver','cobros.supervisar','contabilidad.ver','reportes.ver','eventos.ver'],
            'administracion' => ['personas.ver','personas.gestionar','personal.ver','personal.gestionar','ventas.ver','cobros.ver','contabilidad.ver','reportes.ver','eventos.ver'],
            'consulta' => ['productos.ver','presentaciones.ver','eventos.ver','reportes.ver'],
            'stand' => ['productos.ver','presentaciones.ver','ubicaciones.ver','inventarios.ver','eventos.ver','pedidos.ver','pedidos.gestionar','ventas.ver','ventas.registrar','cobros.ver','cobros.registrar','transferencias.recibir'],
        ];
        $area = strtolower($this->argument('area'));
        if (!isset($perfiles[$area])) { $this->error('Area invalida.'); return self::FAILURE; }
        $user = User::where('email', $this->argument('email'))->first();
        if (!$user) { $this->error('Usuario no encontrado.'); return self::FAILURE; }
        if ($user->id === 1 || $user->hasRole('ADMIN')) { $this->error('ADMIN protegido: no se modifica.'); return self::FAILURE; }
        $faltantes = array_diff($perfiles[$area], Permission::where('guard_name','web')->pluck('name')->all());
        if ($faltantes) { $this->error('Permisos no registrados: '.implode(', ', $faltantes)); return self::FAILURE; }
        $this->info('Usuario ID: '.$user->id.' | Area: '.$area);
        $this->line('Permisos: '.implode(', ', $perfiles[$area]));
        if (!$this->option('aplicar')) { $this->warn('Vista previa. Agregue --aplicar para guardar.'); return self::SUCCESS; }
        DB::transaction(function () use ($user, $area, $perfiles) {
            $role = Role::findByName($area === 'consulta' ? 'CONSULTA' : 'ENCARGADO','web');
            $user->syncRoles([$role]);
            $user->syncPermissions($perfiles[$area]);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('Accesos actualizados. Cierre sesion y vuelva a ingresar.');
        return self::SUCCESS;
    }
}
