<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class PersonalController extends Controller
{
    // LISTAR PERSONAL
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        if (!in_array($limit, [10, 25, 50], true)) $limit = 10;

        $query = Personal::with(['persona', 'cargo']);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_empleado', 'ilike', "%{$buscar}%")
                  ->orWhereHas('persona', function ($p) use ($buscar) {
                      $p->where('nombres_razon_social', 'ilike', "%{$buscar}%")
                        ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                        ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
                  });
            });
        }

        if ($request->filled('id_cargo')) {
            $query->where('id_cargo', $request->id_cargo);
        }

        if ($request->filled('tipo_contrato')) {
            $query->where('tipo_contrato', $request->tipo_contrato);
        }

        // No filtrar cuando estado está vacío.
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
            $query->orderByDesc('id_personal')->paginate($limit)
        );
    }

    // VALIDACIONES PARA REGISTRO Y EDICIÓN
    private function validar(Request $request, ?Personal $personal = null): array
    {
        return $request->validate([
            'id_persona' => [
                'required',
                'integer',
                'exists:personas,id_persona',
                Rule::unique('personal', 'id_persona')
                    ->ignore($personal?->id_personal, 'id_personal')
            ],
            'id_cargo' => ['required', 'integer', 'exists:cargos,id_cargo'],
            'codigo_empleado' => [
                'required',
                'string',
                'max:50',
                Rule::unique('personal', 'codigo_empleado')
                    ->ignore($personal?->id_personal, 'id_personal')
            ],
            'fecha_ingreso' => ['required', 'date'],
            'tipo_contrato' => [
                'nullable',
                Rule::in(['INDEFINIDO', 'PLAZO_FIJO', 'EVENTUAL', 'SERVICIOS'])
            ],
            'salario_base' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['sometimes', 'boolean']
        ]);
    }

    // REGISTRAR PERSONAL
    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['salario_base'] = $datos['salario_base'] ?? 0;
        $datos['estado'] = $datos['estado'] ?? true;

        $personal = Personal::create($datos);

        return response()->json([
            'message' => 'Personal registrado correctamente.',
            'personal' => $personal->load(['persona', 'cargo'])
        ], 201);
    }

    // MOSTRAR PERSONAL
    public function show($id)
    {
        return response()->json(
            Personal::with(['persona', 'cargo'])->findOrFail($id)
        );
    }

    // ACTUALIZAR PERSONAL
    public function update(Request $request, $id)
    {
        $personal = Personal::findOrFail($id);
        $datos = $this->validar($request, $personal);

        $personal->update($datos);

        return response()->json([
            'message' => 'Personal actualizado correctamente.',
            'personal' => $personal->refresh()->load(['persona', 'cargo'])
        ]);
    }

    // ELIMINAR PERSONAL
    public function destroy($id)
    {
        $personal = Personal::findOrFail($id);

        try {
            $personal->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23503') {
                return response()->json([
                    'message' => 'No se puede eliminar este trabajador porque tiene registros relacionados. Puede cambiar su estado a Inactivo.'
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Personal eliminado correctamente.'
        ]);
    }
}
