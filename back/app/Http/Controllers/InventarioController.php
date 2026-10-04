<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventario;

class InventarioController extends Controller
{

    // LISTADO

    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $buscar = $request->query('buscar');

        $idUbicacion = $request->query('id_ubicacion');

        $stockBajo = $request->query('stock_bajo');


        $query = Inventario::with([
            'ubicacion',
            'lote.presentacion.producto'
        ]);


        // BUSCADOR

        if ($buscar) {

            $query->where(function ($q) use ($buscar) {

                $q->whereHas('ubicacion', function ($ubicacion) use ($buscar) {

                    $ubicacion->where(
                        'nombre',
                        'ilike',
                        '%' . $buscar . '%'
                    );

                })

                ->orWhereHas('lote', function ($lote) use ($buscar) {

                    $lote->where(
                        'codigo_lote',
                        'ilike',
                        '%' . $buscar . '%'
                    );

                })

                ->orWhereHas(
                    'lote.presentacion.producto',
                    function ($producto) use ($buscar) {

                        $producto->where(
                            'nombre',
                            'ilike',
                            '%' . $buscar . '%'
                        );

                    }
                );

            });
        }


        // FILTRO POR UBICACIÓN

        if ($idUbicacion) {

            $query->where(
                'id_ubicacion',
                $idUbicacion
            );
        }


        // FILTRO DE STOCK BAJO

        if ($stockBajo === 'true') {

            $query->whereColumn(
                'stock_actual',
                '<=',
                'stock_minimo'
            );
        }


        $inventarios = $query
            ->orderBy('id_inventario', 'desc')
            ->paginate($limit);


        return response()->json($inventarios);
    }


    // REGISTRAR INVENTARIO

    public function store(Request $request)
    {
        $request->validate([

            'id_ubicacion' =>
                'required|exists:ubicaciones,id_ubicacion',

            'id_lote' =>
                'required|exists:lotes,id_lote',

            'stock_minimo' =>
                'required|integer|min:0',

        ]);


        // VERIFICAR DUPLICADOS

        $existe = Inventario::where(
            'id_ubicacion',
            $request->id_ubicacion
        )
        ->where(
            'id_lote',
            $request->id_lote
        )
        ->exists();


        if ($existe) {

            return response()->json([
                'message' =>
                    'Este lote ya tiene un inventario registrado en la ubicación seleccionada.'
            ], 422);
        }


        $inventario = Inventario::create([

            'id_ubicacion' =>
                $request->id_ubicacion,

            'id_lote' =>
                $request->id_lote,

            'stock_actual' =>
                0,

            'stock_minimo' =>
                $request->stock_minimo,

        ]);


        return response()->json([

            'message' =>
                'Inventario registrado correctamente',

            'inventario' =>
                $inventario->load([
                    'ubicacion',
                    'lote.presentacion.producto'
                ])

        ], 201);
    }


    // CONSULTAR INVENTARIO

    public function show($id)
    {
        $inventario = Inventario::with([
            'ubicacion',
            'lote.presentacion.producto'
        ])->find($id);


        if (!$inventario) {

            return response()->json([
                'message' => 'Inventario no encontrado'
            ], 404);
        }


        return response()->json($inventario);
    }


    // ACTUALIZAR STOCK MÍNIMO

    public function update(Request $request, $id)
    {
        $inventario = Inventario::find($id);


        if (!$inventario) {

            return response()->json([
                'message' => 'Inventario no encontrado'
            ], 404);
        }


        $request->validate([

            'stock_minimo' =>
                'required|integer|min:0',

        ]);


        $inventario->stock_minimo =
            $request->stock_minimo;

        $inventario->save();


        return response()->json([

            'message' =>
                'Stock mínimo actualizado correctamente',

            'inventario' =>
                $inventario

        ]);
    }


    // ELIMINACIÓN CONTROLADA

    public function destroy($id)
    {
        $inventario = Inventario::find($id);


        if (!$inventario) {

            return response()->json([
                'message' => 'Inventario no encontrado'
            ], 404);
        }


        if ($inventario->stock_actual > 0) {

            return response()->json([
                'message' =>
                    'No se puede eliminar un inventario que todavía tiene existencias.'
            ], 422);
        }


        return response()->json([
            'message' =>
                'La eliminación de inventarios estará restringida para conservar la trazabilidad histórica.'
        ], 403);
    }
}
