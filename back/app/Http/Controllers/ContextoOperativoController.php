<?php
namespace App\Http\Controllers;
use App\Models\AsignacionPersonal;
use App\Models\Ubicacion;
use App\Services\AccesoTemporalEvento;
use Illuminate\Http\Request;

class ContextoOperativoController extends Controller
{
    public function mio(Request $request, AccesoTemporalEvento $acceso)
    {
        $u = $request->user();
        return response()->json(['asignaciones' => $acceso->asignaciones($u),
            'eventos_ids' => $acceso->eventos($u), 'ubicaciones_ids' => $acceso->ubicaciones($u),
            'diagnostico' => $acceso->asignado($u) ? 'ENCARGO_VIGENTE_O_EVENTO_PENDIENTE_CIERRE' : 'SIN_ENCARGO_VIGENTE']);
    }
    public function standsEvento(Request $request, int $id, AccesoTemporalEvento $acceso)
    {
        if (!$acceso->puedeVerEvento($request->user(), $id)) abort(403);
        $query = Ubicacion::where('id_evento', $id)->where('tipo', 'STAND');
        if (!$request->user()->hasRole('ADMIN') && !$request->user()->can('eventos.ver')) {
            // Un responsable de un stand solo ve ese stand. Un encargado de evento ve sus stands.
            $todas = $acceso->asignaciones($request->user());
            $tieneEvento = $todas->contains(fn($a) => (int)$a->id_evento === $id && !$a->id_ubicacion);
            if (!$tieneEvento) $query->whereIn('id_ubicacion', $acceso->ubicaciones($request->user()));
        }
        return response()->json(['data' => $query->orderBy('nombre')->get()]);
    }
    public function responsables(Request $request, int $id, AccesoTemporalEvento $acceso)
    {
        $u = $request->user();
        if (!($u->hasRole('ADMIN') || $u->can('movimientos.registrar') || $u->can('eventos.gestionar') || $acceso->puedeRecibirEn($u, $id))) abort(403);
        $stand = Ubicacion::whereKey($id)->where('tipo', 'STAND')->firstOrFail();
        $asignaciones = AsignacionPersonal::with('personal.persona')
           ->where('id_ubicacion',$stand->id_ubicacion)->where('estado',true)
           ->whereDate('fecha_inicio','<=',today())
           ->where(fn($q)=>$q->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',today()))
           ->whereRaw('UPPER(TRIM(funcion_asignada)) LIKE ?', ['%ENCARGAD%'])->get();
        return response()->json(['stand' => ['id_ubicacion' => $stand->id_ubicacion, 'nombre' => $stand->nombre],
          'responsables' => $asignaciones->map(fn($a) => [
              'id_asignacion' => $a->id_asignacion,
              'nombre' => trim(($a->personal?->persona?->nombres_razon_social ?? '').' '.($a->personal?->persona?->apellidos ?? '')),
              'funcion' => $a->funcion_asignada,
          ])]);
    }
}
