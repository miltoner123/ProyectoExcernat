<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/** Los cargos definen el rol funcional; el nivel determina si puede operarlo. */
class AccesoPorCargo
{
    public const CARGOS = [
        'Administrador General' => 'GERENTE_GENERAL',
        'Responsable Administrativo' => 'ADMINISTRADOR',
        'Responsable Comercial' => 'ENCARGADO_COMERCIAL',
        'Gerente Comercial' => 'ENCARGADO_COMERCIAL',
        'Responsable de Almacén' => 'ENCARGADO_ALMACEN',
        'Encargado de Almacén' => 'ENCARGADO_ALMACEN',
        'Responsable de Producción' => 'SUBENCARGADO_PRODUCCION',
        'Encargado de Producción' => 'SUBENCARGADO_PRODUCCION',
        'Responsable de Contabilidad' => 'CONTABILIDAD',
        'Contador' => 'CONTABILIDAD',
        'Responsable de Sistemas' => 'ENCARGADO_SISTEMAS',
        'Encargado de Sistemas' => 'ENCARGADO_SISTEMAS',
        'Encargado de Stand' => 'ENCARGADO_VENTAS',
        'Encargado de Sucursal' => 'ENCARGADO_VENTAS',
    ];

    public function rolFuncional(User $usuario): ?string
    {
        $personal = $usuario->persona?->personal;
        if (!$personal || !$personal->estado || !$personal->cargo?->estado) return null;
        return self::CARGOS[$personal->cargo->nombre] ?? null;
    }

    public function sincronizar(User $usuario, string $nivel): void
    {
        if (!in_array($nivel, ['ADMIN','ENCARGADO','CONSULTA'], true)) {
            throw ValidationException::withMessages(['rol'=>'Nivel de usuario no válido.']);
        }
        if ($nivel === 'ADMIN') {
            $usuario->syncRoles(['ADMIN']);
        } elseif ($nivel === 'CONSULTA') {
            $usuario->syncRoles(['CONSULTA']);
        } else {
            $funcional = $this->rolFuncional($usuario);
            // No conceder accesos privilegiados por un cargo desconocido.
            $usuario->syncRoles($funcional ? ['ENCARGADO',$funcional] : ['ENCARGADO']);
        }
        // No conservar permisos otorgados individualmente de una función anterior.
        $usuario->syncPermissions([]);
        $usuario->unsetRelation('roles');
        $usuario->unsetRelation('permissions');
    }

    public function nivel(User $usuario): string
    {
        foreach (['ADMIN', 'CONSULTA', 'ENCARGADO'] as $nivel) {
            if ($usuario->hasRole($nivel)) return $nivel;
        }
        return 'CONSULTA'; // Cuentas históricas no equivalen a ADMIN.
    }
}
