<?php
namespace App\Console\Commands;

use App\Models\Cargo;
use App\Models\User;
use App\Services\AccesoPorCargo;
use Illuminate\Console\Command;

class ExcernatDiagnosticarCargos extends Command
{
    protected $signature = 'excernat:diagnosticar-cargos';
    protected $description = 'Revisa correspondencias de cargos y roles; no modifica datos';

    public function handle(AccesoPorCargo $acceso): int
    {
        $this->info('Cargos registrados (SIN CAMBIOS)');
        $cargos = Cargo::orderBy('nombre')->get();
        $this->table(['Cargo', 'Rol funcional', 'Estado'], $cargos->map(fn ($c) => [
            $c->nombre,
            AccesoPorCargo::rolDeNombreCargo($c->nombre) ?? 'SIN CORRESPONDENCIA',
            $c->estado ? 'Activo' : 'Inactivo',
        ])->all());
        $this->info('Usuarios (SIN CAMBIOS)');
        $usuarios = User::with(['persona.personal.cargo', 'roles'])->orderBy('id')->get();
        $this->table(['ID', 'Usuario', 'Cargo', 'Nivel', 'Rol esperado', 'Roles actuales'],
            $usuarios->map(fn ($u) => [
                $u->id, $u->name,
                $u->persona?->personal?->cargo?->nombre ?? 'SIN CARGO',
                $acceso->nivel($u),
                $acceso->rolFuncional($u) ?? 'NINGUNO',
                $u->getRoleNames()->implode(', '),
            ])->all());
        return self::SUCCESS;
    }
}
