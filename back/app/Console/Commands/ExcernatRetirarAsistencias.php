<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ExcernatRetirarAsistencias extends Command
{
    protected $signature = 'excernat:retirar-asistencias {--aplicar : Retirar permisos obsoletos tras respaldo}';
    protected $description = 'Revisa la retirada definitiva de permisos de asistencia; no borra tablas ni datos.';

    public function handle(): int
    {
        $nombres = ['asistencias.ver','asistencias.registrar','asistencias.gestionar'];
        $permisos = Permission::whereIn('name', $nombres)->where('guard_name','web')->get();
        $this->info('Permisos obsoletos encontrados: ' . $permisos->count());
        $this->warn('Tabla asistencias presente: ' . (Schema::hasTable('asistencias') ? 'SI (datos conservados)' : 'NO'));
        if (!$this->option('aplicar')) {
            $this->info('Solo revisión. Con respaldo previo puede ejecutar --aplicar para retirar los permisos obsoletos.');
            return self::SUCCESS;
        }
        if (!$this->confirm('¿Confirmas respaldo previo y retirada de los permisos obsoletos?', false)) return self::SUCCESS;
        DB::transaction(function () use ($permisos) {
            foreach ($permisos as $permiso) $permiso->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('Permisos obsoletos retirados. Ninguna tabla ni registro operativo se borró.');
        return self::SUCCESS;
    }
}
