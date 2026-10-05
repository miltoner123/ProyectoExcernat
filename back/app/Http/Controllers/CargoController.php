<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\Request;

class CargoController extends Controller
{
    // LISTAR CARGOS
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = Cargo::query();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'ilike', "%{$buscar}%")
                    ->orWhere('descripcion', 'ilike', "%{$buscar}%");
            });
        }

        if ($request->has('estado')) {
            $query->where('estado', filter_var($request->estado, FILTER_VALIDATE_BOOLEAN));
        }

        $cargos = $query->orderBy('nombre')->paginate($limit);

        return response()->json($cargos);
    }


    // REGISTRAR CARGO
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:cargos,nombre',
            'descripcion' => 'nullable|string',
            'estado' => 'nullable|boolean',
        ]);

        $cargo = Cargo::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado ?? true,
        ]);

        return response()->json([
            'message' => 'Cargo registrado correctamente.',
            'cargo' => $cargo
        ], 201);
    }


    // MOSTRAR CARGO
    public function show($id)
    {
        $cargo = Cargo::find($id);

        if (!$cargo) {
            return response()->json([
                'message' => 'Cargo no encontrado.'
            ], 404);
        }

        return response()->json($cargo);
    }


    // ACTUALIZAR CARGO
    public function update(Request $request, $id)
    {
        $cargo = Cargo::find($id);

        if (!$cargo) {
            return response()->json([
                'message' => 'Cargo no encontrado.'
            ], 404);
        }

        $request->validate([
            'nombre' => 'required|string|max:255|unique:cargos,nombre,' . $cargo->id_cargo . ',id_cargo',
            'descripcion' => 'nullable|string',
            'estado' => 'required|boolean',
        ]);

        $cargo->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado,
        ]);

        return response()->json([
            'message' => 'Cargo actualizado correctamente.',
            'cargo' => $cargo
        ]);
    }


    // ELIMINAR CARGO
    public function destroy($id)
    {
        $cargo = Cargo::find($id);

        if (!$cargo) {
            return response()->json([
                'message' => 'Cargo no encontrado.'
            ], 404);
        }

        $cargo->delete();

        return response()->json([
            'message' => 'Cargo eliminado correctamente.'
        ]);
    }
}