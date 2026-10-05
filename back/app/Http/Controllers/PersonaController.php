<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonaController extends Controller
{
    // LISTAR PERSONAS
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = Persona::query();

        // Buscar por nombre, apellido o documento
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('nombres_razon_social', 'ilike', "%{$buscar}%")
                    ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                    ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
            });
        }

        // Filtrar por tipo de persona
        if ($request->filled('tipo_persona')) {
            $query->where('tipo_persona', $request->tipo_persona);
        }

        // Filtrar por estado
        if ($request->has('estado')) {
            $query->where('estado', filter_var($request->estado, FILTER_VALIDATE_BOOLEAN));
        }

        $personas = $query->orderBy('id_persona', 'desc')->paginate($limit);

        return response()->json($personas);
    }


    // REGISTRAR PERSONA
    public function store(Request $request)
    {
        $request->validate([
            'tipo_documento' => ['nullable', 'string', Rule::in(['CI', 'NIT', 'OTRO', 'SIN_DOCUMENTO'])],
            'numero_documento' => 'nullable|string|max:30|unique:personas,numero_documento',
            'nombres_razon_social' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:30',
            'sexo' => ['nullable', 'string', Rule::in(['MASCULINO', 'FEMENINO'])],
            'tipo_persona' => ['required', 'string', Rule::in(['PERSONAL', 'CLIENTE', 'OTRO'])],
            'estado' => 'nullable|boolean',
        ]);

        // Si tiene documento, debe especificar el tipo
        if ($request->filled('numero_documento') && !$request->filled('tipo_documento')) {
            return response()->json([
                'message' => 'Debe seleccionar el tipo de documento.'
            ], 422);
        }

        // Si selecciona SIN_DOCUMENTO, no debe registrar número
        if ($request->tipo_documento === 'SIN_DOCUMENTO' && $request->filled('numero_documento')) {
            return response()->json([
                'message' => 'Una persona SIN_DOCUMENTO no debe tener número de documento.'
            ], 422);
        }

        $persona = Persona::create([
            'tipo_documento' => $request->tipo_documento,
            'numero_documento' => $request->numero_documento,
            'nombres_razon_social' => $request->nombres_razon_social,
            'apellidos' => $request->apellidos,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'sexo' => $request->sexo,
            'tipo_persona' => $request->tipo_persona,
            'estado' => $request->estado ?? true,
        ]);

        return response()->json([
            'message' => 'Persona registrada correctamente.',
            'persona' => $persona
        ], 201);
    }


    // MOSTRAR PERSONA
    public function show($id)
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return response()->json([
                'message' => 'Persona no encontrada.'
            ], 404);
        }

        return response()->json($persona);
    }


    // ACTUALIZAR PERSONA
    public function update(Request $request, $id)
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return response()->json([
                'message' => 'Persona no encontrada.'
            ], 404);
        }

        $request->validate([
            'tipo_documento' => ['nullable', 'string', Rule::in(['CI', 'NIT', 'OTRO', 'SIN_DOCUMENTO'])],
            'numero_documento' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('personas', 'numero_documento')->ignore($persona->id_persona, 'id_persona')
            ],
            'nombres_razon_social' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:30',
            'sexo' => ['nullable', 'string', Rule::in(['MASCULINO', 'FEMENINO'])],
            'tipo_persona' => ['required', 'string', Rule::in(['PERSONAL', 'CLIENTE', 'OTRO'])],
            'estado' => 'required|boolean',
        ]);

        if ($request->filled('numero_documento') && !$request->filled('tipo_documento')) {
            return response()->json([
                'message' => 'Debe seleccionar el tipo de documento.'
            ], 422);
        }

        if ($request->tipo_documento === 'SIN_DOCUMENTO' && $request->filled('numero_documento')) {
            return response()->json([
                'message' => 'Una persona SIN_DOCUMENTO no debe tener número de documento.'
            ], 422);
        }

        $persona->update([
            'tipo_documento' => $request->tipo_documento,
            'numero_documento' => $request->numero_documento,
            'nombres_razon_social' => $request->nombres_razon_social,
            'apellidos' => $request->apellidos,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'sexo' => $request->sexo,
            'tipo_persona' => $request->tipo_persona,
            'estado' => $request->estado,
        ]);

        return response()->json([
            'message' => 'Persona actualizada correctamente.',
            'persona' => $persona
        ]);
    }


    // ELIMINAR PERSONA
    public function destroy($id)
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return response()->json([
                'message' => 'Persona no encontrada.'
            ], 404);
        }

        $persona->delete();

        return response()->json([
            'message' => 'Persona eliminada correctamente.'
        ]);
    }
}