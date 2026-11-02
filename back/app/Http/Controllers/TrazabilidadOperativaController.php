<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Venta;
use Illuminate\Http\Request;

/** Consulta consolidada, solo lectura, para verificar trazabilidad operativa. */
class TrazabilidadOperativaController extends Controller
{
    public function lote(Request $request, int $id)
    {
        // Los datos comerciales y logísticos se presentan juntos únicamente a ADMIN.
        abort_unless($request->user()?->hasRole('ADMIN'), 403);

        $lote = Lote::with('presentacion.producto')->findOrFail($id);

        $movimientos = MovimientoInventario::query()
            ->with(['origen', 'destino', 'receptor', 'detalles' => function ($query) use ($id) {
                $query->where('id_lote', $id);
            }])
            ->whereHas('detalles', fn ($query) => $query->where('id_lote', $id))
            ->orderBy('id_movimiento')->get();

        $ventas = Venta::query()
            ->with(['ubicacion', 'usuario', 'usuarioAnulacion',
                'asignacionVendedor.personal.persona',
                'detalles' => fn ($query) => $query->where('id_lote', $id)])
            ->whereHas('detalles', fn ($query) => $query->where('id_lote', $id))
            ->orderBy('id_venta')->get();

        $detallesMovimiento = $movimientos->flatMap->detalles;
        $detallesVenta = $ventas->flatMap->detalles;

        $enTransito = $movimientos
            ->filter(fn ($m) => in_array($m->tipo, ['TRANSFERENCIA', 'DEVOLUCION'], true)
                && $m->estado_recepcion === 'PENDIENTE')
            ->flatMap->detalles->sum('cantidad');

        return response()->json([
            'lote' => $lote,
            'movimientos' => $movimientos,
            'ventas' => $ventas,
            'resumen' => [
                'movimientos_registrados' => $movimientos->count(),
                'pedidos_relacionados' => $movimientos->pluck('id_pedido')->filter()->unique()->values(),
                'unidades_despachadas_en_transito' => (int) $enTransito,
                'ventas_confirmadas_unidades' => (int) $ventas->where('estado', 'CONFIRMADA')
                    ->flatMap->detalles->sum('cantidad'),
                'ventas_anuladas_unidades' => (int) $ventas->where('estado', 'ANULADA')
                    ->flatMap->detalles->sum('cantidad'),
                'unidades_movidas_registradas' => (int) $detallesMovimiento->sum('cantidad'),
            ],
            'nota' => 'Resumen histórico de operaciones; las cifras no sustituyen el saldo actual de inventarios. Las diferencias de recepción se consultan en cada detalle.',
        ]);
    }
}
