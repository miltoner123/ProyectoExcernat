<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lote;

class LoteController extends Controller
{

    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);
        $buscar = $request->query('buscar');
        $estado = $request->query('estado');
        $query = Lote::with([
            'presentacion.producto'
        ]);


        // BUSCADOR
        if ($buscar) {

            $query->where(function ($q) use ($buscar) {

                $q->where(
                    'codigo_lote',
                    'ilike',
                    '%' . $buscar . '%'
                )

                ->orWhereHas(
                    'presentacion',
                    function ($presentacion) use ($buscar) {

                        $presentacion
                            ->where(
                                'nombre_presentacion',
                                'ilike',
                                '%' . $buscar . '%'
                            )

                            ->orWhere(
                                'codigo_sku',
                                'ilike',
                                '%' . $buscar . '%'
                            );

                    }
                )

                ->orWhereHas(
                    'presentacion.producto',
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


        // FILTRO POR ESTADO
        if ($estado !== null && $estado !== '') {

            $query->where(
                'estado',
                filter_var(
                    $estado,
                    FILTER_VALIDATE_BOOLEAN
                )
            );

        }


        $lotes = $query
            ->orderBy('id_lote', 'desc')
            ->paginate($limit);


        return response()->json($lotes);
    }


    public function store(Request $request)
    {
        $request->validate([
            'id_presentacion' =>
                'required|exists:presentaciones,id_presentacion',

            'codigo_lote' =>
                'required|string|max:100|unique:lotes,codigo_lote',

            'fecha_produccion' =>
                'required|date',

            'fecha_vencimiento' =>
                'required|date|after:fecha_produccion',

            'cantidad_producida' =>
                'required|integer|min:1',

            'observacion' =>
                'nullable|string'
        ]);


        $lote = new Lote();

        $lote->id_presentacion =
            $request->id_presentacion;

        $lote->codigo_lote =
            $request->codigo_lote;

        $lote->fecha_produccion =
            $request->fecha_produccion;

        $lote->fecha_vencimiento =
            $request->fecha_vencimiento;

        $lote->cantidad_producida =
            $request->cantidad_producida;

        $lote->estado =
            $request->estado ?? true;

        $lote->observacion =
            $request->observacion;

        $lote->save();


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


        $request->validate([

            'id_presentacion' =>
                'required|exists:presentaciones,id_presentacion',

            'codigo_lote' =>
                'required|string|max:100|unique:lotes,codigo_lote,'
                . $id . ',id_lote',

            'fecha_produccion' =>
                'required|date',

            'fecha_vencimiento' =>
                'required|date|after:fecha_produccion',

            'cantidad_producida' =>
                'required|integer|min:1',

            'observacion' =>
                'nullable|string'

        ]);


        $lote->id_presentacion =
            $request->id_presentacion;

        $lote->codigo_lote =
            $request->codigo_lote;

        $lote->fecha_produccion =
            $request->fecha_produccion;

        $lote->fecha_vencimiento =
            $request->fecha_vencimiento;

        $lote->cantidad_producida =
            $request->cantidad_producida;

        $lote->estado =
            $request->estado;

        $lote->observacion =
            $request->observacion;

        $lote->save();


        return response()->json([
            'message' => 'Lote actualizado correctamente',
            'lote' => $lote
        ]);
    }


    public function destroy($id)
    {
        $lote = Lote::find($id);


        if (!$lote) {

            return response()->json([
                'message' => 'Lote no encontrado'
            ], 404);

        }


        try {

            $lote->delete();


            return response()->json([
                'message' => 'Lote eliminado correctamente'
            ]);


        } catch (\Exception $e) {

            return response()->json([
                'message' =>
                    'No se puede eliminar el lote porque tiene registros relacionados'
            ], 409);

        }
    }
}