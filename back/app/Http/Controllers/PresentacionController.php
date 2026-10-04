<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presentacion;


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


    function destroy($id)
    {
        $presentacion = Presentacion::find($id);
        if ($presentacion) {
            $presentacion->delete();
            return response()->json([
                'message' => 'Presentación eliminada correctamente'
            ]);
        } else {
            return response()->json([
                'message' => 'Presentación no encontrada'
            ], 404);
        }
    }
}