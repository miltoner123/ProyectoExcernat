<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class PersonaController extends Controller
{
    // LISTAR PERSONAS
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        if (!in_array($limit, [10, 25, 50], true)) $limit = 10;

        $query = Persona::query();

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres_razon_social', 'ilike', "%{$buscar}%")
                  ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                  ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
            });
        }

        if ($request->filled('tipo_persona')) {
            $query->where('tipo_persona', $request->tipo_persona);
        }

        if ($request->filled('estado')) {
            $estado = filter_var($request->estado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($estado !== null) $query->where('estado', $estado);
        }

        return response()->json($query->orderByDesc('id_persona')->paginate($limit));
    }

    // VALIDAR PERSONA
    private function validar(Request $request, ?Persona $persona = null): array
    {
        $datos = $request->validate([
            'tipo_documento' => ['nullable', Rule::in(['CI', 'NIT', 'OTRO', 'SIN_DOCUMENTO'])],
            'numero_documento' => [
                'nullable', 'string', 'max:30',
                Rule::unique('personas', 'numero_documento')
                    ->ignore($persona?->id_persona, 'id_persona')
            ],
            'nombres_razon_social' => ['required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'sexo' => ['nullable', Rule::in(['MASCULINO', 'FEMENINO'])],
            'tipo_persona' => ['required', Rule::in(['PERSONAL', 'CLIENTE', 'OTRO'])],
            'estado' => ['sometimes', 'boolean']
        ]);

        if (!empty($datos['numero_documento']) && empty($datos['tipo_documento'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'tipo_documento' => 'Debe seleccionar el tipo de documento.'
            ]);
        }

        if (($datos['tipo_documento'] ?? null) === 'SIN_DOCUMENTO'
            && !empty($datos['numero_documento'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'numero_documento' => 'Una persona sin documento no debe registrar número de documento.'
            ]);
        }

        return $datos;
    }

    // REGISTRAR PERSONA
    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $datos['estado'] = $datos['estado'] ?? true;

        $persona = Persona::create($datos);

        return response()->json([
            'message' => 'Persona registrada correctamente.',
            'persona' => $persona
        ], 201);
    }

    // MOSTRAR PERSONA
    public function show($id)
    {
        return response()->json(Persona::findOrFail($id));
    }

    // ACTUALIZAR PERSONA
    public function update(Request $request, $id)
    {
        $persona = Persona::findOrFail($id);
        $datos = $this->validar($request, $persona);

        $persona->update($datos);

        return response()->json([
            'message' => 'Persona actualizada correctamente.',
            'persona' => $persona->refresh()
        ]);
    }

    // ELIMINAR PERSONA
    public function destroy($id)
    {
        $persona = Persona::findOrFail($id);

        try {
            $persona->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23503') {
                return response()->json([
                    'message' => 'No se puede eliminar esta persona porque tiene registros relacionados.'
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Persona eliminada correctamente.'
        ]);
    }
}

