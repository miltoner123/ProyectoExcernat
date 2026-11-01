<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteContableController extends Controller
{
    public function resumen(Request $request)
    {
        abort_unless($request->user()->hasRole('ADMIN') || $request->user()->can('contabilidad.ver'), 403);
        $data = $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
        ]);
        $ventas = DB::table('ventas')->where('estado', 'CONFIRMADA');
        $cobros = DB::table('cobros_ventas')->join('ventas', 'ventas.id_venta', '=', 'cobros_ventas.id_venta')
            ->where('ventas.estado', 'CONFIRMADA');
        if (!empty($data['desde'])) {
            $ventas->whereDate('fecha_venta', '>=', $data['desde']);
            $cobros->whereDate('ventas.fecha_venta', '>=', $data['desde']);
        }
        if (!empty($data['hasta'])) {
            $ventas->whereDate('fecha_venta', '<=', $data['hasta']);
            $cobros->whereDate('ventas.fecha_venta', '<=', $data['hasta']);
        }
        return response()->json([
            'ventas_confirmadas' => (clone $ventas)->count(),
            'total_ventas' => (clone $ventas)->sum('total'),
            'total_cobrado' => (clone $cobros)->sum('cobros_ventas.monto'),
            'cobros_por_metodo' => (clone $cobros)->select('cobros_ventas.metodo_pago')
                ->selectRaw('COUNT(*) as operaciones, SUM(cobros_ventas.monto) as total')
                ->groupBy('cobros_ventas.metodo_pago')->get(),
        ]);
    }
}
