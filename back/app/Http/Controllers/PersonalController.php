<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonalController extends Controller
{
    // LISTAR PERSONAL
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = Personal::with(['persona', 'cargo']);

        // Buscar por nombre, apellido, documento o código de empleado
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_empleado', 'ilike', "%{$buscar}%")
                    ->orWhereHas('persona', function ($persona) use ($buscar) {
                        $persona->where('nombres_razon_social', 'ilike', "%{$buscar}%")
                            ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                            ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
                    });
            });
        }

        // Filtrar por cargo
        if ($request->filled('id_cargo')) {
            $query->where('id_cargo', $request->id_cargo);
        }

        // Filtrar por tipo de contrato
        if ($request->filled('tipo_contrato')) {
            $query->where('tipo_contrato', $request->tipo_contrato);
        }

        // Filtrar por estado
        if ($request->has('estado')) {
            $query->where('estado', filter_var($request->estado, FILTER_VALIDATE_BOOLEAN));
        }

        $personal = $query->orderBy('id_personal', 'desc')->paginate($limit);

        return response()->json($personal);
    }


    // REGISTRAR PERSONAL
    public function store(Request $request)
    {
        $request->validate([
            'id_persona' => 'required|exists:personas,id_persona|unique:personal,id_persona',
            'id_cargo' => 'required|exists:cargos,id_cargo',
            'codigo_empleado' => 'required|string|max:50|unique:personal,codigo_empleado',
            'fecha_ingreso' => 'required|date',
            'tipo_contrato' => ['nullable', 'string', Rule::in(['INDEFINIDO', 'PLAZO_FIJO', 'EVENTUAL', 'SERVICIOS'])],
            'salario_base' => 'nullable|numeric|min:0',
            'estado' => 'nullable|boolean',
        ]);

        $personal = Personal::create([
            'id_persona' => $request->id_persona,
            'id_cargo' => $request->id_cargo,
            'codigo_empleado' => $request->codigo_empleado,
            'fecha_ingreso' => $request->fecha_ingreso,
            'tipo_contrato' => $request->tipo_contrato,
            'salario_base' => $request->salario_base ?? 0,
            'estado' => $request->estado ?? true,
        ]);

        return response()->json([
            'message' => 'Personal registrado correctamente.',
            'personal' => $personal->load(['persona', 'cargo'])
        ], 201);
    }


    // MOSTRAR PERSONAL
    public function show($id)
    {
        $personal = Personal::with(['persona', 'cargo'])->find($id);

        if (!$personal) {
            return response()->json([
                'message' => 'Personal no encontrado.'
            ], 404);
        }

        return response()->json($personal);
    }


    // ACTUALIZAR PERSONAL
    public function update(Request $request, $id)
    {
        $personal = Personal::find($id);

        if (!$personal) {
            return response()->json([
                'message' => 'Personal no encontrado.'
            ], 404);
        }

        $request->validate([
            'id_persona' => [
                'required',
                'exists:personas,id_persona',
                Rule::unique('personal', 'id_persona')->ignore($personal->id_personal, 'id_personal')
            ],
            'id_cargo' => 'required|exists:cargos,id_cargo',
            'codigo_empleado' => [
                'required',
                'string',
                'max:50',
                Rule::unique('personal', 'codigo_empleado')->ignore($personal->id_personal, 'id_personal')
            ],
            'fecha_ingreso' => 'required|date',
            'tipo_contrato' => ['nullable', 'string', Rule::in(['INDEFINIDO', 'PLAZO_FIJO', 'EVENTUAL', 'SERVICIOS'])],
            'salario_base' => 'nullable|numeric|min:0',
            'estado' => 'required|boolean',
        ]);

        $personal->update([
            'id_persona' => $request->id_persona,
            'id_cargo' => $request->id_cargo,
            'codigo_empleado' => $request->codigo_empleado,
            'fecha_ingreso' => $request->fecha_ingreso,
            'tipo_contrato' => $request->tipo_contrato,
            'salario_base' => $request->salario_base ?? 0,
            'estado' => $request->estado,
        ]);

        return response()->json([
            'message' => 'Personal actualizado correctamente.',
            'personal' => $personal->load(['persona', 'cargo'])
        ]);
    }


    // ELIMINAR PERSONAL
    public function destroy($id)
    {
        $personal = Personal::find($id);

        if (!$personal) {
            return response()->json([
                'message' => 'Personal no encontrado.'
            ], 404);
        }

        $personal->delete();

        return response()->json([
            'message' => 'Personal eliminado correctamente.'
        ]);
    }
}
