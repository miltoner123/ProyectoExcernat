<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presentacion;
use App\Models\Producto;

class PresentacionController extends Controller
{
    function index(Request $request)
    {
        $limit = $request->query('limit', 10);
        $presentaciones = Presentacion::with('producto')
            ->paginate($limit);
        return response()->json($presentaciones);
    }


    function store(Request $request)
    {
        $presentacion = new Presentacion();
        $presentacion->id_producto = $request->id_producto;
        $presentacion->codigo_sku = $request->codigo_sku;
        $presentacion->nombre_presentacion = $request->nombre_presentacion;
        $presentacion->peso_neto = $request->peso_neto;
        $presentacion->unidad_medida = $request->unidad_medida;
        $presentacion->tipo_envase = $request->tipo_envase;
        $presentacion->unidades_por_paquete = $request->unidades_por_paquete;
        $presentacion->precio_unitario = $request->precio_unitario;
        $presentacion->precio_paquete = $request->precio_paquete;
        $presentacion->estado = true;
        $presentacion->save();
        return $presentacion;
    }


    function show($id)
    {
        $presentacion = Presentacion::with('producto')
            ->find($id);
        if ($presentacion) {
            return $presentacion;
        } else {
            return response()->json([
                'message' => 'Presentación no encontrada'
            ], 404);
        }
    }


    function update(Request $request, $id)
    {
        $presentacion = Presentacion::find($id);
        if ($presentacion) {
            $presentacion->id_producto = $request->id_producto;
            $presentacion->codigo_sku = $request->codigo_sku;
            $presentacion->nombre_presentacion = $request->nombre_presentacion;
            $presentacion->peso_neto = $request->peso_neto;
            $presentacion->unidad_medida = $request->unidad_medida;
            $presentacion->tipo_envase = $request->tipo_envase;
            $presentacion->unidades_por_paquete = $request->unidades_por_paquete;
            $presentacion->precio_unitario = $request->precio_unitario;
            $presentacion->precio_paquete = $request->precio_paquete;
            $presentacion->estado = $request->estado;
            $presentacion->save();

            return response()->json([
                'message' => 'Presentación actualizada correctamente',
                'presentacion' => $presentacion
            ]);

        } else {
            return response()->json([
                'message' => 'Presentación no encontrada'
            ], 404);
        }
    }

    public function destroy($id)
    {
        $presentacion = Presentacion::find($id);

        if (!$presentacion) {
            return response()->json(['message' => 'Presentación no encontrada'], 404);
        }

        if ($presentacion->lotes()->exists()) {
            return response()->json(['message' => 'La presentación tiene lotes asociados. Puede inactivarla para conservar el historial.'], 422);
        }

        $presentacion->delete();

        return response()->json(['message' => 'Presentación enviada a la papelera correctamente']);
    }

    public function papelera(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 10), 1), 100);

        return response()->json(Presentacion::onlyTrashed()->with('producto')->orderByDesc('deleted_at')->paginate($limit));
    }

    public function restaurar($id)
    {
        $presentacion = Presentacion::onlyTrashed()->find($id);

        if (!$presentacion) {
            return response()->json(['message' => 'Presentación eliminada no encontrada'], 404);
        }

        if (!Producto::whereKey($presentacion->id_producto)->exists()) {
            return response()->json(['message' => 'Primero debe restaurar el producto asociado'], 422);
        }

        $presentacion->restore();

        return response()->json(['message' => 'Presentación restaurada correctamente']);
    }
}