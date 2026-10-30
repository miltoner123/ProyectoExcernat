<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Persona;
use Illuminate\Support\Str;
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
        if ($request->filled('estado')) {
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

            'fecha_ingreso' => 'required|date',
            'tipo_contrato' => ['nullable', 'string', Rule::in(['INDEFINIDO', 'PLAZO_FIJO', 'EVENTUAL', 'SERVICIOS'])],
            'salario_base' => 'nullable|numeric|min:0',
            'estado' => 'nullable|boolean',
        ]);

        $persona = Persona::findOrFail($request->id_persona);
        $nombre = trim(($persona->nombres_razon_social ?? '') . ' ' . ($persona->apellidos ?? ''));
        $palabras = preg_split('/\s+/u', Str::ascii($nombre), -1, PREG_SPLIT_NO_EMPTY);
        $iniciales = implode('', array_map(fn ($palabra) => strtoupper(substr($palabra, 0, 1)), $palabras));
        $ci = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($persona->numero_documento ?? '')));
        if ($iniciales === '' || $ci === '') {
            return response()->json(['message' => 'La persona debe tener nombre completo y documento para generar el código.'], 422);
        }
        $codigo = substr($iniciales . '-' . $ci, 0, 50);
        if (Personal::where('codigo_empleado', $codigo)->exists()) {
            return response()->json(['message' => 'El código generado ya está registrado. Revise los datos personales y el CI.'], 422);
        }

        $personal = Personal::create([
            'id_persona' => $request->id_persona,
            'id_cargo' => $request->id_cargo,
            'codigo_empleado' => $codigo,
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
