<?php
namespace App\Services;

use App\Models\AsignacionPersonal;
use App\Models\Personal;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AccesoTemporalEvento
{
    public function asignaciones($usuario)
    {
        if (!$usuario || !$usuario->estado || !$usuario->id_persona || $usuario->hasRole('CONSULTA')) return collect();
        $ids = Personal::where('id_persona', $usuario->id_persona)->where('estado', true)->pluck('id_personal');
        // La fecha_fin de un encargo en STAND es informativa mientras el evento
        // siga abierto. En SUCURSAL la fecha_fin sigue siendo obligatoria.
        return AsignacionPersonal::with(['evento', 'ubicacion.evento'])
            ->whereIn('id_personal', $ids)->where('estado', true)
            ->whereDate('fecha_inicio', '<=', today())
            ->whereRaw('UPPER(TRIM(funcion_asignada)) LIKE ?', ['%ENCARGAD%'])
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today())
                  ->orWhereHas('ubicacion', function ($u) {
                      $u->where('tipo', 'STAND')->where('estado', true)
                        ->whereHas('evento', fn($e) => $e->where('estado', true)
                          ->whereNotIn('estado_operativo', ['CERRADO']));
                  });
            })
            ->where(function ($q) {
                $q->whereDoesntHave('ubicacion')
                  ->orWhereHas('ubicacion', function ($u) {
                      $u->where('estado', true)->where(function ($w) {
                          $w->whereNull('id_evento')->orWhereHas('evento',
                              fn($e) => $e->where('estado', true)
                                  ->where('estado_operativo', '!=', 'CERRADO'));
                      });
                  });
            })->get();
    }

    public function ubicaciones($usuario)
    {
        return $this->asignaciones($usuario)->pluck('id_ubicacion')->filter()->unique()->values();
    }

    public function eventos($usuario)
    {
        return $this->asignaciones($usuario)->map(fn($a) => $a->id_evento ?: $a->ubicacion?->id_evento)->filter()->unique()->values();
    }

    public function asignado($usuario): bool { return $this->asignaciones($usuario)->isNotEmpty(); }
    public function puedeVerEvento($usuario, int $id): bool
    {
        return $usuario && ($usuario->hasRole('ADMIN') || $usuario->can('eventos.ver') || $this->eventos($usuario)->contains($id));
    }
    public function puedeRecibirEn($usuario, int $ubicacion): bool
    {
        return $this->ubicaciones($usuario)->contains($ubicacion);
    }
}
