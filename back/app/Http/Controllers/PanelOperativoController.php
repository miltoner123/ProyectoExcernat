<?php
namespace App\Http\Controllers;

use App\Models\AsignacionPersonal;
use App\Models\Personal;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;

class PanelOperativoController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $admin = $usuario->hasRole('ADMIN');
        $query = AsignacionPersonal::with(['personal.persona','ubicacion.evento'])
            ->where('estado', true)
            ->whereNotNull('id_ubicacion')
            ->whereDate('fecha_inicio', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', now()->toDateString());
            });
        if (!$admin) {
            $personalIds = Personal::where('id_persona', $usuario->id_persona)
                ->where('estado', true)->pluck('id_personal');
            $query->whereIn('id_personal', $personalIds);
        }
        $asignaciones = $query->orderBy('id_ubicacion')->get();
        $ubicaciones = $asignaciones->pluck('id_ubicacion')->unique()->values();
        $pendientes = collect();
        if ($usuario->can('transferencias.recibir')) {
            $pendientes = MovimientoInventario::query()
                ->whereIn('id_ubicacion_destino', $ubicaciones)
                ->whereIn('tipo', ['TRANSFERENCIA', 'DEVOLUCION'])
                ->where('estado_recepcion', 'PENDIENTE')
                ->selectRaw('id_ubicacion_destino, COUNT(*) as total')
                ->groupBy('id_ubicacion_destino')->pluck('total','id_ubicacion_destino');
        }
        return response()->json([
            'asignaciones' => $asignaciones,
            'recepciones_pendientes' => $pendientes,
            'permisos' => [
                'recibir' => $usuario->can('transferencias.recibir'),
                'vender' => $usuario->can('ventas.registrar'),
                'ventas_ver' => $usuario->can('ventas.ver'),
                'cajas' => $usuario->can('cobros.ver'),
                'inventarios' => $usuario->can('inventarios.ver'),
                'movimientos' => $usuario->can('movimientos.ver'),
            ],
        ]);
    }
}
