<?php
namespace App\Http\Controllers;

use App\Models\AsignacionPersonal;
use App\Models\Personal;
use App\Models\MovimientoInventario;
use App\Models\Inventario;
use App\Services\AccesoTemporalEvento;
use Illuminate\Http\Request;

class PanelOperativoController extends Controller
{
    // Consulta aislada: nunca acepta una ubicación ajena a la asignación vigente.
    public function inventario(Request $request, AccesoTemporalEvento $acceso)
    {
        $datos = $request->validate(['id_ubicacion' => 'required|integer|exists:ubicaciones,id_ubicacion']);
        $id = (int) $datos['id_ubicacion'];
        abort_unless($request->user()->hasRole('ADMIN') || $acceso->ubicaciones($request->user())->contains($id), 403,
            'No tiene encargo vigente en esta ubicación.');
        return response()->json(Inventario::with('lote.presentacion.producto')
            ->where('id_ubicacion', $id)->where('stock_actual', '>', 0)
            ->orderBy('id_lote')->get());
    }

    public function index(Request $request, AccesoTemporalEvento $acceso)
    {
        $usuario = $request->user();
        $admin = $usuario->hasRole('ADMIN');
        $asignaciones = $admin
            ? AsignacionPersonal::with(['personal.persona', 'ubicacion.evento'])
                ->where('estado', true)->whereNotNull('id_ubicacion')->get()
            : $acceso->asignaciones($usuario)->filter(fn($a) => $a->id_ubicacion)->values();
        $ubicaciones = $asignaciones->pluck('id_ubicacion')->unique()->values();
        $pendientes = collect();
        if ($usuario->can('transferencias.recibir') || $asignaciones->contains(fn($a) => str_contains(mb_strtoupper((string)$a->funcion_asignada), 'ENCARGAD'))) {
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
                'recibir' => $usuario->can('transferencias.recibir') || $asignaciones->contains(fn($a) => str_contains(mb_strtoupper((string)$a->funcion_asignada), 'ENCARGAD')),
                'vender' => $usuario->can('ventas.registrar') || $asignaciones->contains(fn($a) => str_contains(mb_strtoupper((string)$a->funcion_asignada), 'ENCARGAD')),
                'ventas_ver' => $usuario->can('ventas.ver') || $asignaciones->isNotEmpty(),
                'cajas' => $usuario->can('cobros.ver'),
                'inventarios' => $usuario->can('inventarios.ver') || $asignaciones->contains(fn($a) => str_contains(mb_strtoupper((string)$a->funcion_asignada), 'ENCARGAD')),
                'movimientos' => $usuario->can('movimientos.ver'),
            ],
        ]);
    }
}
