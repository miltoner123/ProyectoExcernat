<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteContableController extends Controller
{
    public function pdf(Request $request)
    {
        // Se reutiliza el endpoint de resumen: mismos filtros y misma autorización.
        $datos = $this->resumen($request)->getData(true);
        $pdf = new \App\Support\ReportePdfSimple();
        $pdf->titulo('EXCERNAT - REPORTE DE VENTAS Y COBROS');
        $pdf->texto('Generado: '.now()->format('d/m/Y H:i'));
        $pdf->texto('Emitido por: '.$request->user()->name);
        $pdf->texto('Periodo: '.($request->query('desde') ?: 'Inicio').' - '.($request->query('hasta') ?: 'Hoy'));
        if ($request->filled('id_evento')) {
            $nombre = DB::table('eventos')->where('id_evento', $request->query('id_evento'))->value('nombre');
            $pdf->texto('Evento: '.($nombre ?: $request->query('id_evento')));
        }
        if ($request->filled('id_ubicacion')) {
            $nombre = DB::table('ubicaciones')->where('id_ubicacion', $request->query('id_ubicacion'))->value('nombre');
            $pdf->texto('Ubicacion: '.($nombre ?: $request->query('id_ubicacion')));
        }
        $pdf->separador();
        $pdf->texto('VENTAS CONFIRMADAS: '.($datos['ventas_confirmadas'] ?? 0), 11, true);
        $pdf->texto('TOTAL VENTAS: Bs '.number_format((float)($datos['total_ventas'] ?? 0), 2, '.', ','), 11, true);
        $pdf->texto('TOTAL COBRADO: Bs '.number_format((float)($datos['total_cobrado'] ?? 0), 2, '.', ','), 11, true);
        $pdf->separador();
        $pdf->texto('VENTAS POR UBICACION', 12, true);
        foreach ($datos['por_ubicacion'] ?? [] as $fila) {
            $pdf->texto(sprintf('%s | %s | %d ventas | Bs %s',
                $fila['nombre'], $fila['tipo'], $fila['operaciones'],
                number_format((float)$fila['total'],2,'.',',')));
        }
        if (empty($datos['por_ubicacion'])) $pdf->texto('No existen ventas en los filtros seleccionados.');
        $pdf->separador();
        $pdf->texto('COBROS POR METODO',12,true);
        foreach ($datos['cobros_por_metodo'] ?? [] as $fila) {
            $pdf->texto(sprintf('%s | %d cobros | Bs %s',
                $fila['metodo_pago'], $fila['operaciones'], number_format((float)$fila['total'],2,'.',',')));
        }
        if (empty($datos['cobros_por_metodo'])) $pdf->texto('No existen cobros para este periodo.');
        $pdf->separador();
        $pdf->texto('Reporte operativo: ventas no equivalen a utilidad neta.');
        return response($pdf->generar(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="excernat_reporte_ventas.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function resumen(Request $request)
    {
        abort_unless($request->user()->hasRole('ADMIN') || $request->user()->can('contabilidad.ver'), 403);
        $data = $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
            'id_evento' => 'nullable|integer|exists:eventos,id_evento',
            'id_ubicacion' => 'nullable|integer|exists:ubicaciones,id_ubicacion',
        ]);
        $ventas = DB::table('ventas')->where('ventas.estado', 'CONFIRMADA');
        $cobros = DB::table('cobros_ventas')->join('ventas', 'ventas.id_venta', '=', 'cobros_ventas.id_venta')
            ->where('ventas.estado', 'CONFIRMADA');
        if (!empty($data['id_ubicacion'])) {
            $ventas->where('ventas.id_ubicacion', $data['id_ubicacion']);
            $cobros->where('ventas.id_ubicacion', $data['id_ubicacion']);
        }
        if (!empty($data['id_evento'])) {
            $ventas->whereIn('ventas.id_ubicacion', DB::table('ubicaciones')->where('id_evento', $data['id_evento'])->select('id_ubicacion'));
            $cobros->whereIn('ventas.id_ubicacion', DB::table('ubicaciones')->where('id_evento', $data['id_evento'])->select('id_ubicacion'));
        }
        if (!empty($data['desde'])) {
            $ventas->whereDate('ventas.fecha_venta', '>=', $data['desde']);
            $cobros->whereDate('ventas.fecha_venta', '>=', $data['desde']);
        }
        if (!empty($data['hasta'])) {
            $ventas->whereDate('ventas.fecha_venta', '<=', $data['hasta']);
            $cobros->whereDate('ventas.fecha_venta', '<=', $data['hasta']);
        }
        return response()->json([
            'por_ubicacion' => (clone $ventas)->join('ubicaciones', 'ubicaciones.id_ubicacion', '=', 'ventas.id_ubicacion')
                ->select('ubicaciones.id_ubicacion', 'ubicaciones.nombre', 'ubicaciones.tipo', 'ubicaciones.id_evento')
                ->selectRaw('COUNT(*) as operaciones, SUM(ventas.total) as total')
                ->groupBy('ubicaciones.id_ubicacion', 'ubicaciones.nombre', 'ubicaciones.tipo', 'ubicaciones.id_evento')
                ->orderBy('ubicaciones.nombre')->get(),
            'ventas_confirmadas' => (clone $ventas)->count(),
            'total_ventas' => (clone $ventas)->sum('ventas.total'),
            'total_cobrado' => (clone $cobros)->sum('cobros_ventas.monto'),
            'cobros_por_metodo' => (clone $cobros)->select('cobros_ventas.metodo_pago')
                ->selectRaw('COUNT(*) as operaciones, SUM(cobros_ventas.monto) as total')
                ->groupBy('cobros_ventas.metodo_pago')->get(),
        ]);
    }
}
