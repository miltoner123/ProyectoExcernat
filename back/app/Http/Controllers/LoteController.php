<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lote;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class LoteController extends Controller
{

        
    public function index(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 100);
        $buscar = $request->query('buscar');
        $estado = $request->query('estado');
        $vencimiento = $request->query('vencimiento');

        $query = Lote::with('presentacion.producto');

        // BUSCADOR
        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_lote', 'ilike', '%' . $buscar . '%')
                    ->orWhereHas('presentacion', function ($presentacion) use ($buscar) {
                        $presentacion->where('nombre_presentacion', 'ilike', '%' . $buscar . '%')
                            ->orWhere('codigo_sku', 'ilike', '%' . $buscar . '%');
                    })
                    ->orWhereHas('presentacion.producto', function ($producto) use ($buscar) {
                        $producto->where('nombre', 'ilike', '%' . $buscar . '%');
                    });
            });
        }

        // FILTRO DE ESTADO
        if ($estado !== null && $estado !== '') {
            $query->where('estado', filter_var($estado, FILTER_VALIDATE_BOOLEAN));
        }

        // FILTRO DE VENCIMIENTO
        $hoy = now()->startOfDay();
        $fecha90 = $hoy->copy()->addDays(90);
        $fecha180 = $hoy->copy()->addDays(180);

        switch ($vencimiento) {
            case 'VENCIDO':
                $query->whereDate('fecha_vencimiento', '<', $hoy->toDateString());
                break;

            case 'PROXIMO':
                $query->whereDate('fecha_vencimiento', '>=', $hoy->toDateString())
                    ->whereDate('fecha_vencimiento', '<=', $fecha90->toDateString());
                break;

            case 'PREVENTIVO':
                $query->whereDate('fecha_vencimiento', '>', $fecha90->toDateString())
                    ->whereDate('fecha_vencimiento', '<=', $fecha180->toDateString());
                break;

            case 'VIGENTE':
                $query->whereDate('fecha_vencimiento', '>', $fecha180->toDateString());
                break;
        }

        // MOSTRAR PRIMERO LOS LOTES CON VENCIMIENTO MÁS CERCANO
        $lotes = $query->orderBy('fecha_vencimiento', 'asc')
            ->orderBy('id_lote', 'asc')
            ->paginate($limit);

        return response()->json($lotes);
    }


    public function store(Request $request)
{
    $datos = $request->validate([
        'id_presentacion' => 'required|exists:presentaciones,id_presentacion',
        'codigo_lote' => ['required', 'string', 'max:100', Rule::unique('lotes', 'codigo_lote')],
        'fecha_produccion' => 'required|date',
        'vida_util_meses' => 'required|integer|min:1|max:120',
        'cantidad_producida' => 'required|integer|min:1',
        'estado' => 'sometimes|boolean',
        'observacion' => 'nullable|string',
    ]);

    $fechaProduccion = Carbon::parse($datos['fecha_produccion']);

    $datos['fecha_vencimiento'] = $fechaProduccion->copy()->addMonthsNoOverflow((int) $datos['vida_util_meses'])->toDateString();

    $datos['estado'] = $datos['estado'] ?? true;

    $lote = Lote::create($datos);

    return response()->json([
        'message' => 'Lote registrado correctamente',
        'lote' => $lote
    ], 201);
}


    public function show($id)
    {
        $lote = Lote::with([
            'presentacion.producto'
        ])->find($id);


        if (!$lote) {

            return response()->json([
                'message' => 'Lote no encontrado'
            ], 404);

        }


        return response()->json($lote);
    }


    public function update(Request $request, $id)
{
    $lote = Lote::find($id);

    if (!$lote) {
        return response()->json([
            'message' => 'Lote no encontrado'
        ], 404);
    }

    $datos = $request->validate([
        'id_presentacion' => 'required|exists:presentaciones,id_presentacion',
        'codigo_lote' => ['required', 'string', 'max:100', Rule::unique('lotes', 'codigo_lote')->ignore($lote->id_lote, 'id_lote')],
        'fecha_produccion' => 'required|date',
        'vida_util_meses' => 'required|integer|min:1|max:120',
        'cantidad_producida' => 'required|integer|min:1',
        'estado' => 'required|boolean',
        'observacion' => 'nullable|string',
    ]);

    $fechaProduccion = Carbon::parse($datos['fecha_produccion']);

    $datos['fecha_vencimiento'] = $fechaProduccion->copy()->addMonthsNoOverflow((int) $datos['vida_util_meses'])->toDateString();

    $lote->update($datos);

    return response()->json([
        'message' => 'Lote actualizado correctamente',
        'lote' => $lote->fresh()
    ]);
}


    public function destroy($id)
    {
        $lote = Lote::find($id);

        if (!$lote) {
            return response()->json(['message' => 'Lote no encontrado'], 404);
        }

        $tieneInventario = $lote->inventarios()->exists();
        $tieneMovimientos = $lote->detallesMovimiento()->exists();

        if ($tieneInventario || $tieneMovimientos) {
            return response()->json([
                'message' => 'Este lote tiene registros de inventario o movimientos. No puede enviarse a la papelera. Puede inactivarlo para conservar su historial.'
            ], 422);
        }

        $lote->delete();

        return response()->json([
            'message' => 'Lote enviado a la papelera correctamente.'
        ]);
    }
    public function papelera(Request $request)
        {
            $limit = min(max((int) $request->input('limit', 10), 1), 100);

            $lotes = Lote::onlyTrashed()->with('presentacion.producto')->orderBy('deleted_at', 'desc')->paginate($limit);

            return response()->json($lotes);
        }
        public function restaurar($id)
        {
            $lote = Lote::onlyTrashed()->find($id);

            if (!$lote) {
                return response()->json([
                    'message' => 'Lote eliminado no encontrado.'
                ], 404);
            }

            $lote->restore();

            return response()->json([
                'message' => 'Lote restaurado correctamente.',
                'lote' => $lote
            ]);
        }
    public function cambiarEstado(Request $request, $id)
    {
        $datos = $request->validate([
            'estado' => 'required|boolean'
        ]);

        $lote = Lote::find($id);

        if (!$lote) {
            return response()->json([
                'message' => 'Lote no encontrado.'
            ], 404);
        }

        $lote->estado = $datos['estado'];
        $lote->save();

        return response()->json([
            'message' => $lote->estado ? 'Lote activado correctamente.' : 'Lote inactivado correctamente.',
            'lote' => $lote
        ]);
    }
}