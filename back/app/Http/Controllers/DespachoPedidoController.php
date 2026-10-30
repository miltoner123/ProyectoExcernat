<?php
namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DespachoPedidoController extends Controller
{
    private function unidadesSolicitadas($detalle): int
    {
        $factor = (int) ($detalle->presentacion?->unidades_por_paquete ?? 0);
        if ($factor < 1) {
            throw ValidationException::withMessages(['pedido' => 'La presentación no tiene unidades por paquete válidas.']);
        }
        return (int) $detalle->cantidad_paquetes * $factor + (int) $detalle->cantidad_unidades;
    }

    private function yaDespachado(int $idPedido): array
    {
        return DB::table('detalle_movimiento as d')
            ->join('movimientos_inventario as m', 'm.id_movimiento', '=', 'd.id_movimiento')
            ->join('lotes as l', 'l.id_lote', '=', 'd.id_lote')
            ->where('m.id_pedido', $idPedido)
            ->where('m.tipo', 'TRANSFERENCIA')
            ->select('l.id_presentacion', DB::raw('SUM(d.cantidad) as cantidad'))
            ->groupBy('l.id_presentacion')
            ->pluck('cantidad', 'id_presentacion')->toArray();
    }

    public function pendientes(Request $request)
    {
        $pedidos = Pedido::with(['ubicacionSolicitante', 'detalles.presentacion.producto'])
            ->where('estado', 'PENDIENTE')->orderBy('fecha_pedido')->get();
        return response()->json(['data' => $pedidos->map(function ($pedido) {
            $despachado = $this->yaDespachado($pedido->id_pedido);
            $lineas = $pedido->detalles->map(function ($d) use ($despachado) {
                $solicitado = $this->unidadesSolicitadas($d);
                $enviado = (int) ($despachado[$d->id_presentacion] ?? 0);
                return [
                    'id_presentacion' => $d->id_presentacion,
                    'presentacion' => $d->presentacion,
                    'solicitado' => $solicitado,
                    'despachado' => $enviado,
                    'pendiente' => max(0, $solicitado - $enviado),
                ];
            });
            return [
                'id_pedido' => $pedido->id_pedido,
                'fecha_pedido' => $pedido->fecha_pedido,
                'ubicacion_solicitante' => $pedido->ubicacionSolicitante,
                'detalles' => $lineas,
                'unidades_pendientes' => $lineas->sum('pendiente'),
            ];
        })->filter(fn ($p) => $p['unidades_pendientes'] > 0)->values()]);
    }

    public function despachar(Request $request, int $id)
    {
        $datos = $request->validate([
            'id_ubicacion_origen' => 'required|integer|exists:ubicaciones,id_ubicacion',
            'observacion' => 'nullable|string|max:2000',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_lote' => 'required|integer|distinct|exists:lotes,id_lote',
            'detalles.*.cantidad' => 'required|integer|min:1',
        ]);
        $movimiento = DB::transaction(function () use ($id, $datos) {
            $pedido = Pedido::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($pedido->estado !== 'PENDIENTE') {
                throw ValidationException::withMessages(['pedido' => 'El pedido ya no está pendiente.']);
            }
            $origen = Ubicacion::whereKey($datos['id_ubicacion_origen'])->firstOrFail();
            $destino = Ubicacion::whereKey($pedido->id_ubicacion_solicitante)->firstOrFail();
            if (!$origen->estado || !$destino->estado || $origen->tipo !== 'ALMACEN'
                || !in_array(mb_strtoupper(trim($origen->nombre)), ['ALMACÉN CENTRAL', 'ALMACEN CENTRAL'], true)
                || $origen->id_ubicacion === $destino->id_ubicacion) {
                throw ValidationException::withMessages(['origen' => 'El despacho debe salir del Almacén Central hacia otra ubicación activa.']);
            }
            $pedido->load('detalles.presentacion');
            $solicitado = [];
            foreach ($pedido->detalles as $d) $solicitado[$d->id_presentacion] = $this->unidadesSolicitadas($d);
            $enviado = $this->yaDespachado($pedido->id_pedido);
            $aEnviar = [];
            $lotes = [];
            $ids = array_column($datos['detalles'], 'id_lote');
            sort($ids, SORT_NUMERIC);
            foreach ($ids as $idLote) {
                $lote = Lote::whereKey($idLote)->lockForUpdate()->firstOrFail();
                if (!$lote->estado) throw ValidationException::withMessages(['detalles' => 'Hay lotes inactivos.']);
                $lotes[$idLote] = $lote;
            }
            foreach ($datos['detalles'] as $d) {
                $presentacion = $lotes[$d['id_lote']]->id_presentacion;
                if (!array_key_exists($presentacion, $solicitado)) {
                    throw ValidationException::withMessages(['detalles' => 'El lote seleccionado no corresponde a las presentaciones solicitadas.']);
                }
                $aEnviar[$presentacion] = ($aEnviar[$presentacion] ?? 0) + (int) $d['cantidad'];
            }
            foreach ($aEnviar as $presentacion => $cantidad) {
                if ($cantidad > $solicitado[$presentacion] - (int) ($enviado[$presentacion] ?? 0)) {
                    throw ValidationException::withMessages(['detalles' => 'La cantidad supera el saldo pendiente del pedido.']);
                }
            }
            // Bloqueos ordenados: evitar ventas/despachos simultáneos del mismo lote.
            foreach ($ids as $idLote) {
                $inv = Inventario::where('id_ubicacion', $origen->id_ubicacion)->where('id_lote', $idLote)->lockForUpdate()->first();
                $cantidad = (int) collect($datos['detalles'])->firstWhere('id_lote', $idLote)['cantidad'];
                if (!$inv || $inv->stock_actual < $cantidad) {
                    throw ValidationException::withMessages(['stock' => 'Stock insuficiente para el lote ' . $lotes[$idLote]->codigo_lote]);
                }
                $inv->stock_actual -= $cantidad;
                $inv->save();
            }
            $mov = MovimientoInventario::create([
                'tipo' => 'TRANSFERENCIA',
                'id_pedido' => $pedido->id_pedido,
                'id_ubicacion_origen' => $origen->id_ubicacion,
                'id_ubicacion_destino' => $destino->id_ubicacion,
                'fecha_movimiento' => now(),
                'referencia' => 'PEDIDO-' . $pedido->id_pedido,
                'observacion' => $datos['observacion'] ?? 'Despacho de pedido #' . $pedido->id_pedido,
                'estado_recepcion' => 'PENDIENTE',
            ]);
            foreach ($datos['detalles'] as $d) $mov->detalles()->create($d);
            $completo = true;
            foreach ($solicitado as $presentacion => $cantidad) {
                if ((int) ($enviado[$presentacion] ?? 0) + (int) ($aEnviar[$presentacion] ?? 0) < $cantidad) $completo = false;
            }
            if ($completo) {
                $pedido->estado = 'DESPACHADO';
                $pedido->save();
            }
            return $mov->load(['origen', 'destino', 'detalles.lote.presentacion.producto']);
        }, 3);
        return response()->json(['message' => 'Despacho registrado. Pendiente de recepción en destino.', 'movimiento' => $movimiento], 201);
    }
}
