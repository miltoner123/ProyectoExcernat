<?php
namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecepcionProduccionController extends Controller
{
    private function central(): Ubicacion
    {
        $almacenes = Ubicacion::query()->where('tipo', 'ALMACEN')
            ->where('estado', true)->whereRaw("UPPER(TRIM(nombre)) = 'ALMACÉN CENTRAL' OR UPPER(TRIM(nombre)) = 'ALMACEN CENTRAL'")->get();
        if ($almacenes->count() !== 1) {
            throw ValidationException::withMessages(['almacen' => 'Debe existir exactamente un ALMACÉN CENTRAL activo.']);
        }
        return $almacenes->first();
    }

    private function totalIngresado(int $id): int
    {
        return (int) DB::table('detalle_movimiento as d')
            ->join('movimientos_inventario as m', 'm.id_movimiento', '=', 'd.id_movimiento')
            ->where('d.id_lote', $id)->where('m.tipo', 'ENTRADA')->sum('d.cantidad');
    }

    public function index(Request $request)
    {
        $central = $this->central();
        $lotes = Lote::with('presentacion.producto')->where('estado', true)
            ->orderBy('id_lote')->get()->map(function ($lote) {
                $ingresado = $this->totalIngresado((int) $lote->id_lote);
                return [
                    'id_lote' => $lote->id_lote,
                    'codigo_lote' => $lote->codigo_lote,
                    'producto' => $lote->presentacion?->producto?->nombre,
                    'presentacion' => $lote->presentacion?->nombre_presentacion,
                    'cantidad_producida' => (int) $lote->cantidad_producida,
                    'cantidad_recibida' => $ingresado,
                    'cantidad_pendiente' => max(0, (int) $lote->cantidad_producida - $ingresado),
                    'fecha_produccion' => $lote->fecha_produccion,
                ];
            })->filter(fn ($l) => $l['cantidad_pendiente'] > 0)->values();
        return response()->json(['almacen' => $central->nombre, 'total' => $lotes->count(), 'data' => $lotes]);
    }

    public function confirmar(Request $request, int $id)
    {
        $datos = $request->validate(['observacion' => 'nullable|string|max:1000']);
        $resultado = DB::transaction(function () use ($id, $datos, $request) {
            $lote = Lote::whereKey($id)->lockForUpdate()->firstOrFail();
            if (!$lote->estado) {
                throw ValidationException::withMessages(['lote' => 'El lote está inactivo.']);
            }
            $central = $this->central();
            $pendiente = (int) $lote->cantidad_producida - $this->totalIngresado($id);
            if ($pendiente <= 0) {
                throw ValidationException::withMessages(['lote' => 'Este lote ya fue recibido.']);
            }
            $movimiento = MovimientoInventario::create([
                'tipo' => 'ENTRADA',
                'id_ubicacion_origen' => null,
                'id_ubicacion_destino' => $central->id_ubicacion,
                'fecha_movimiento' => now(),
                'referencia' => 'RECEPCION-LOTE-' . $id,
                'observacion' => 'Recepción confirmada por usuario #' . $request->user()->getKey()
                    . '. ' . ($datos['observacion'] ?? ''),
            ]);
            $movimiento->detalles()->create(['id_lote' => $id, 'cantidad' => $pendiente]);
            $inventario = Inventario::where('id_ubicacion', $central->id_ubicacion)
                ->where('id_lote', $id)->lockForUpdate()->first();
            if (!$inventario) {
                $inventario = Inventario::create([
                    'id_ubicacion' => $central->id_ubicacion, 'id_lote' => $id,
                    'stock_actual' => 0, 'stock_minimo' => 0,
                ]);
            }
            $inventario->stock_actual = (int) $inventario->stock_actual + $pendiente;
            $inventario->save();
            return ['id_movimiento' => $movimiento->id_movimiento, 'cantidad' => $pendiente];
        }, 3);
        return response()->json(['message' => 'Producción recibida correctamente.', 'recepcion' => $resultado], 201);
    }
}
