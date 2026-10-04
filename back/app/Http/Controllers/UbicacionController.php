<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ubicacion;

class UbicacionController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);

        // Evita límites exagerados desde la URL
        if (!in_array((int) $limit, [10, 25, 50])) {
            $limit = 10;
        }

        $buscar = $request->query('buscar');
        $tipo = $request->query('tipo');
        $estado = $request->query('estado');


        $query = Ubicacion::with('evento');


        // BUSCADOR
        if ($buscar) {

            $query->where(function ($q) use ($buscar) {

                $q->where(
                    'nombre',
                    'ilike',
                    '%' . $buscar . '%'
                )
                ->orWhere(
                    'departamento',
                    'ilike',
                    '%' . $buscar . '%'
                )
                ->orWhere(
                    'direccion',
                    'ilike',
                    '%' . $buscar . '%'
                );

            });
        }


        // FILTRO POR TIPO
        if ($tipo) {

            $query->where(
                'tipo',
                strtoupper($tipo)
            );
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


        $ubicaciones = $query
            ->orderBy('id_ubicacion', 'desc')
            ->paginate($limit);


        return response()->json($ubicaciones);
    }


    public function store(Request $request)
    {
        $request->validate([

            'nombre' =>
                'required|string|max:255',

            'tipo' =>
                'required|in:ALMACEN,SUCURSAL,STAND',

            'propiedad' =>
                'required|in:PROPIA,EXTERNA,TEMPORAL',

            'departamento' =>
                'required|string|max:100',

            'direccion' =>
                'nullable|string|max:255',

            'id_evento' =>
                'nullable|exists:eventos,id_evento',

            'estado' =>
                'nullable|boolean',
        ]);


        /*
        |--------------------------------------------------------------------------
        | REGLAS PARA STANDS
        |--------------------------------------------------------------------------
        */

        if ($request->tipo === 'STAND' && !$request->id_evento) {

            return response()->json([
                'message' =>
                    'Debe seleccionar un evento para registrar un stand.'
            ], 422);
        }


        if ($request->tipo !== 'STAND' && $request->id_evento) {

            return response()->json([
                'message' =>
                    'Solo las ubicaciones de tipo STAND pueden estar asociadas a un evento.'
            ], 422);
        }


        $ubicacion = new Ubicacion();

        $ubicacion->id_evento =
            $request->tipo === 'STAND'
                ? $request->id_evento
                : null;

        $ubicacion->nombre =
            $request->nombre;

        $ubicacion->tipo =
            $request->tipo;

        $ubicacion->propiedad =
            $request->propiedad;

        $ubicacion->departamento =
            $request->departamento;

        $ubicacion->direccion =
            $request->direccion;

        $ubicacion->estado =
            $request->estado ?? true;

        $ubicacion->save();


        return response()->json([
            'message' =>
                'Ubicación registrada correctamente',

            'ubicacion' =>
                $ubicacion->load('evento')
        ], 201);
    }


    public function show($id)
    {
        $ubicacion =
            Ubicacion::with('evento')->find($id);


        if (!$ubicacion) {

            return response()->json([
                'message' =>
                    'Ubicación no encontrada'
            ], 404);
        }


        return response()->json($ubicacion);
    }


    public function update(Request $request, $id)
    {
        $ubicacion =
            Ubicacion::find($id);


        if (!$ubicacion) {

            return response()->json([
                'message' =>
                    'Ubicación no encontrada'
            ], 404);
        }


        $request->validate([

            'nombre' =>
                'required|string|max:255',

            'tipo' =>
                'required|in:ALMACEN,SUCURSAL,STAND',

            'propiedad' =>
                'required|in:PROPIA,EXTERNA,TEMPORAL',

            'departamento' =>
                'required|string|max:100',

            'direccion' =>
                'nullable|string|max:255',

            'id_evento' =>
                'nullable|exists:eventos,id_evento',

            'estado' =>
                'required|boolean',
        ]);


        if ($request->tipo === 'STAND' && !$request->id_evento) {

            return response()->json([
                'message' =>
                    'Debe seleccionar un evento para registrar un stand.'
            ], 422);
        }


        if ($request->tipo !== 'STAND' && $request->id_evento) {

            return response()->json([
                'message' =>
                    'Solo los stands pueden estar asociados a un evento.'
            ], 422);
        }


        $ubicacion->id_evento =
            $request->tipo === 'STAND'
                ? $request->id_evento
                : null;

        $ubicacion->nombre =
            $request->nombre;

        $ubicacion->tipo =
            $request->tipo;

        $ubicacion->propiedad =
            $request->propiedad;

        $ubicacion->departamento =
            $request->departamento;

        $ubicacion->direccion =
            $request->direccion;

        $ubicacion->estado =
            $request->estado;

        $ubicacion->save();


        return response()->json([
            'message' =>
                'Ubicación actualizada correctamente',

            'ubicacion' =>
                $ubicacion->load('evento')
        ]);
    }


    public function destroy($id)
    {
        $ubicacion =
            Ubicacion::find($id);


        if (!$ubicacion) {

            return response()->json([
                'message' =>
                    'Ubicación no encontrada'
            ], 404);
        }


        try {

            $ubicacion->delete();


            return response()->json([
                'message' =>
                    'Ubicación eliminada correctamente'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' =>
                    'No se puede eliminar la ubicación porque tiene registros relacionados.'
            ], 409);
        }
    }
}