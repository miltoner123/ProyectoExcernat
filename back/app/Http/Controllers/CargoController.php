<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class CargoController extends Controller
{
    // LISTAR CARGOS
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        if (!in_array($limit, [10, 25, 50], true)) $limit = 10;

        $query = Cargo::query();

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'ilike', "%{$buscar}%")
                  ->orWhere('descripcion', 'ilike', "%{$buscar}%");
            });
        }

        // Evita filtrar por inactivos cuando estado está vacío.
        if ($request->filled('estado')) {
            $estado = filter_var(
                $request->estado,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($estado !== null) {
                $query->where('estado', $estado);
            }
        }

        return response()->json(
            $query->orderBy('nombre')->paginate($limit)
        );
    }

    // VALIDACIÓN COMPARTIDA
    private function validar(Request $request, ?Cargo $cargo = null): array
    {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cargos', 'nombre')
                    ->ignore($cargo?->id_cargo, 'id_cargo')
            ],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['sometimes', 'boolean']
        ]);
    }

    // REGISTRAR CARGO
    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $datos['estado'] = $datos['estado'] ?? true;

        $cargo = Cargo::create($datos);

        return response()->json([
            'message' => 'Cargo registrado correctamente.',
            'cargo' => $cargo
        ], 201);
    }

    // MOSTRAR CARGO
    public function show($id)
    {
        return response()->json(Cargo::findOrFail($id));
    }

    // ACTUALIZAR CARGO
    public function update(Request $request, $id)
    {
        $cargo = Cargo::findOrFail($id);
        $cargo->update($this->validar($request, $cargo));

        return response()->json([
            'message' => 'Cargo actualizado correctamente.',
            'cargo' => $cargo->refresh()
        ]);
    }

    // ELIMINAR CARGO
    public function destroy($id)
    {
        $cargo = Cargo::findOrFail($id);

        try {
            $cargo->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23503') {
                return response()->json([
                    'message' => 'No se puede eliminar este cargo porque tiene personal relacionado. Puede cambiar su estado a Inactivo.'
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Cargo eliminado correctamente.'
        ]);
    }
}
