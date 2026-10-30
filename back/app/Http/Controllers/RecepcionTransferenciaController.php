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
        $datos = $request->validate([
            'detalles' => 'nullable|array|min:1',
            'detalles.*.id_detalle_movimiento' => 'required_with:detalles|integer|distinct',
            'detalles.*.cantidad_recibida' => 'required_with:detalles|integer|min:0',
            'detalles.*.observacion_recepcion' => 'nullable|string|max:1000',
        ]);
        $movimiento = DB::transaction(function () use ($request, $id, $datos) {
            $mov = MovimientoInventario::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($mov->tipo !== 'TRANSFERENCIA' || $mov->estado_recepcion !== 'PENDIENTE') {
                throw ValidationException::withMessages(['recepcion' => 'Esta transferencia ya fue procesada.']);
            }
            if (!$this->puedeRecibir($request, $mov)) abort(403, 'Solo el encargado vigente del destino puede confirmar.');
            $detalles = $mov->detalles()->orderBy('id_lote')->get();
            $entradas = collect($datos['detalles'] ?? [])->keyBy('id_detalle_movimiento');
            if ($entradas->isNotEmpty() && $entradas->count() !== $detalles->count()) {
                throw ValidationException::withMessages(['detalles' => 'Debe declarar la recepción de todos los renglones.']);
            }
            $diferencias = false;
            foreach ($detalles as $detalle) {
                $entrada = $entradas->isEmpty() ? null : $entradas->get($detalle->id_detalle_movimiento);
                if ($entradas->isNotEmpty() && !$entrada) {
                    throw ValidationException::withMessages(['detalles' => 'Falta un lote en la declaración.']);
                }
                $cantidad = $entrada ? (int) $entrada['cantidad_recibida'] : (int) $detalle->cantidad;
                if ($cantidad > (int) $detalle->cantidad) {
                    throw ValidationException::withMessages(['detalles' => 'No se puede recibir más de lo despachado.']);
                }
                $observacion = trim($entrada['observacion_recepcion'] ?? '');
                if ($cantidad < (int) $detalle->cantidad) {
                    $diferencias = true;
                    if ($observacion === '') {
                        throw ValidationException::withMessages(['detalles' => 'Indique el motivo de la diferencia para cada lote incompleto.']);
                    }
                }
                $detalle->cantidad_recibida = $cantidad;
                $detalle->observacion_recepcion = $observacion ?: null;
                $detalle->save();
                if ($cantidad === 0) continue;
                $inventario = Inventario::where('id_ubicacion', $mov->id_ubicacion_destino)
                    ->where('id_lote', $detalle->id_lote)->lockForUpdate()->first();
                if (!$inventario) {
                    $inventario = Inventario::create(['id_ubicacion' => $mov->id_ubicacion_destino,
                        'id_lote' => $detalle->id_lote, 'stock_actual' => 0, 'stock_minimo' => 0]);
                }
                $inventario->stock_actual += $cantidad;
                $inventario->save();
            }
            $mov->estado_recepcion = $diferencias ? 'OBSERVADA' : 'RECIBIDA';
            $mov->id_usuario_receptor = $request->user()->id;
            $mov->fecha_recepcion = now();
            $mov->save();
            return $mov->load(['origen','destino','detalles.lote.presentacion.producto','receptor']);
        }, 3);
        return response()->json([
            'message' => $movimiento->estado_recepcion === 'OBSERVADA'
                ? 'Recepción registrada con diferencias. El faltante queda pendiente de resolución administrativa.'
                : 'Recepción completa confirmada.',
            'movimiento' => $movimiento,
        ]);
    }

    public function seguimiento(Request $request, int $id)
    {
        $pedido = \App\Models\Pedido::with(['ubicacionSolicitante','detalles.presentacion.producto'])->findOrFail($id);
        if (!$request->user()->hasRole('ADMIN')) {
            $ids = Personal::where('id_persona', $request->user()->id_persona)->where('estado', true)->pluck('id_personal');
            $autorizado = AsignacionPersonal::whereIn('id_personal', $ids)
                ->where('id_ubicacion', $pedido->id_ubicacion_solicitante)->where('estado', true)
                ->whereDate('fecha_inicio', '<=', now()->toDateString())
                ->where(function ($q) { $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', now()->toDateString()); })->exists();
            if (!$autorizado) abort(403);
        }
        $movimientos = MovimientoInventario::with(['detalles.lote','origen','destino','receptor'])
            ->where('id_pedido', $id)->where('tipo','TRANSFERENCIA')->orderBy('id_movimiento')->get();
        $lineas = $pedido->detalles->map(function ($d) use ($movimientos) {
            $factor = (int) ($d->presentacion?->unidades_por_paquete ?? 0);
            $solicitado = (int) $d->cantidad_paquetes * $factor + (int) $d->cantidad_unidades;
            $detalles = $movimientos->flatMap->detalles->filter(fn ($x) => (int) ($x->lote?->id_presentacion) === (int) $d->id_presentacion);
            $despachado = (int) $detalles->sum('cantidad');
            $recibido = (int) $movimientos->filter(fn ($m) => in_array($m->estado_recepcion, ['RECIBIDA','OBSERVADA'], true))
                ->flatMap->detalles->filter(fn ($x) => (int) ($x->lote?->id_presentacion) === (int) $d->id_presentacion)
                ->sum(fn ($x) => $x->cantidad_recibida ?? $x->cantidad);
            $transito = (int) $movimientos->filter(fn ($m) => $m->estado_recepcion === 'PENDIENTE')
                ->flatMap->detalles->filter(fn ($x) => (int) ($x->lote?->id_presentacion) === (int) $d->id_presentacion)->sum('cantidad');
            return [
                'id_presentacion' => $d->id_presentacion,
                'presentacion' => $d->presentacion,
                'solicitado' => $solicitado,
                'despachado' => $despachado,
                'recibido' => $recibido,
                'en_transito' => $transito,
                'diferencia_observada' => max(0, $despachado - $recibido - $transito),
                'pendiente_despacho' => max(0, $solicitado - $despachado),
            ];
        });
        return response()->json(['pedido' => $pedido, 'lineas' => $lineas, 'movimientos' => $movimientos]);
    }
}
