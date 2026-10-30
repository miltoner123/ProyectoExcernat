<?php
namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Inventario;
use App\Models\AsignacionPersonal;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecepcionTransferenciaController extends Controller
{
    private function puedeRecibir(Request $request, MovimientoInventario $mov): bool
    {
        $usuario = $request->user();
        if (!$usuario) return false;
        if ($usuario->hasRole('ADMIN')) return true;
        if (!$usuario->id_persona || !$mov->id_ubicacion_destino) return false;
        $personalIds = Personal::where('id_persona', $usuario->id_persona)->where('estado', true)->pluck('id_personal');
        return AsignacionPersonal::whereIn('id_personal', $personalIds)
            ->where('id_ubicacion', $mov->id_ubicacion_destino)
            ->where('estado', true)
            ->whereDate('fecha_inicio', '<=', now()->toDateString())
            ->where(function ($q) { $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', now()->toDateString()); })
            ->whereRaw("UPPER(funcion_asignada) LIKE ?", ['%ENCARGAD%'])
            ->exists();
    }

    public function index(Request $request)
    {
        $query = MovimientoInventario::with(['origen', 'destino.evento', 'detalles.lote.presentacion.producto'])
            ->where('tipo', 'TRANSFERENCIA')->where('estado_recepcion', 'PENDIENTE');
        if (!$request->user()->hasRole('ADMIN')) {
            $ids = Personal::where('id_persona', $request->user()->id_persona)->where('estado', true)->pluck('id_personal');
            $ubicaciones = AsignacionPersonal::whereIn('id_personal', $ids)->where('estado', true)
                ->whereDate('fecha_inicio', '<=', now()->toDateString())
                ->where(function ($q) { $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', now()->toDateString()); })
                ->whereRaw("UPPER(funcion_asignada) LIKE ?", ['%ENCARGAD%'])->pluck('id_ubicacion');
            $query->whereIn('id_ubicacion_destino', $ubicaciones);
        }
        return response()->json(['data' => $query->orderBy('id_movimiento')->get()]);
    }

    public function confirmar(Request $request, int $id)
    {
        $movimiento = DB::transaction(function () use ($request, $id) {
            $mov = MovimientoInventario::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($mov->tipo !== 'TRANSFERENCIA' || $mov->estado_recepcion !== 'PENDIENTE') {
                throw ValidationException::withMessages(['recepcion' => 'Esta transferencia no está pendiente de recepción.']);
            }
            if (!$this->puedeRecibir($request, $mov)) abort(403, 'Solo el encargado vigente del destino puede confirmar la recepción.');
            $detalles = $mov->detalles()->orderBy('id_lote')->get();
            foreach ($detalles as $detalle) {
                $inventario = Inventario::where('id_ubicacion', $mov->id_ubicacion_destino)
                    ->where('id_lote', $detalle->id_lote)->lockForUpdate()->first();
                if (!$inventario) {
                    $inventario = Inventario::create(['id_ubicacion' => $mov->id_ubicacion_destino,
                        'id_lote' => $detalle->id_lote, 'stock_actual' => 0, 'stock_minimo' => 0]);
                }
                $inventario->stock_actual += (int) $detalle->cantidad;
                $inventario->save();
            }
            $mov->estado_recepcion = 'RECIBIDA';
            $mov->id_usuario_receptor = $request->user()->id;
            $mov->fecha_recepcion = now();
            $mov->save();
            return $mov->load(['origen','destino','detalles','receptor']);
        }, 3);
        return response()->json(['message' => 'Recepción confirmada. Stock del destino actualizado.', 'movimiento' => $movimiento]);
    }
}
