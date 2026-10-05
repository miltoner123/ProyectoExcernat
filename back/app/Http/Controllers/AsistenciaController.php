<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AsistenciaController extends Controller
{
    // LISTAR ASISTENCIAS
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = Asistencia::with([
            'personal.persona',
            'personal.cargo'
        ]);

        // Buscar trabajador
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->whereHas('personal.persona', function ($q) use ($buscar) {
                $q->where('nombres_razon_social', 'ilike', "%{$buscar}%")
                    ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                    ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
            });
        }

        // Filtrar por personal
        if ($request->filled('id_personal')) {
            $query->where('id_personal', $request->id_personal);
        }

        // Filtrar por fecha
        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        // Filtrar por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $asistencias = $query
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_entrada', 'asc')
            ->paginate($limit);

        return response()->json($asistencias);
    }


    // REGISTRAR ASISTENCIA
    public function store(Request $request)
    {
        $request->validate([
            'id_personal' => [
                'required',
                'exists:personal,id_personal',
                Rule::unique('asistencias')->where(function ($query) use ($request) {
                    return $query->where('fecha', $request->fecha);
                }),
            ],
            'fecha' => 'required|date',
            'hora_entrada' => 'nullable|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i|after_or_equal:hora_entrada',
            'estado' => ['required', Rule::in(['PRESENTE', 'AUSENTE', 'ATRASO', 'PERMISO', 'LICENCIA'])],
            'observacion' => 'nullable|string',
        ]);

        // AUSENTE no debe registrar horas
        if ($request->estado === 'AUSENTE' && ($request->filled('hora_entrada') || $request->filled('hora_salida'))) {
            return response()->json([
                'message' => 'Una asistencia con estado AUSENTE no debe registrar hora de entrada o salida.'
            ], 422);
        }

        $asistencia = Asistencia::create([
            'id_personal' => $request->id_personal,
            'fecha' => $request->fecha,
            'hora_entrada' => $request->hora_entrada,
            'hora_salida' => $request->hora_salida,
            'estado' => $request->estado,
            'observacion' => $request->observacion,
        ]);

        return response()->json([
            'message' => 'Asistencia registrada correctamente.',
            'asistencia' => $asistencia->load([
                'personal.persona',
                'personal.cargo'
            ])
        ], 201);
    }


    // MOSTRAR ASISTENCIA
    public function show($id)
    {
        $asistencia = Asistencia::with([
            'personal.persona',
            'personal.cargo'
        ])->find($id);

        if (!$asistencia) {
            return response()->json([
                'message' => 'Asistencia no encontrada.'
            ], 404);
        }

        return response()->json($asistencia);
    }


    // ACTUALIZAR ASISTENCIA
    public function update(Request $request, $id)
    {
        $asistencia = Asistencia::find($id);

        if (!$asistencia) {
            return response()->json([
                'message' => 'Asistencia no encontrada.'
            ], 404);
        }

        $request->validate([
            'id_personal' => [
                'required',
                'exists:personal,id_personal',
                Rule::unique('asistencias')
                    ->where(function ($query) use ($request) {
                        return $query->where('fecha', $request->fecha);
                    })
                    ->ignore($asistencia->id_asistencia, 'id_asistencia'),
            ],
            'fecha' => 'required|date',
            'hora_entrada' => 'nullable|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i|after_or_equal:hora_entrada',
            'estado' => ['required', Rule::in(['PRESENTE', 'AUSENTE', 'ATRASO', 'PERMISO', 'LICENCIA'])],
            'observacion' => 'nullable|string',
        ]);

        if ($request->estado === 'AUSENTE' && ($request->filled('hora_entrada') || $request->filled('hora_salida'))) {
            return response()->json([
                'message' => 'Una asistencia con estado AUSENTE no debe registrar hora de entrada o salida.'
            ], 422);
        }

        $asistencia->update([
            'id_personal' => $request->id_personal,
            'fecha' => $request->fecha,
            'hora_entrada' => $request->hora_entrada,
            'hora_salida' => $request->hora_salida,
            'estado' => $request->estado,
            'observacion' => $request->observacion,
        ]);

        return response()->json([
            'message' => 'Asistencia actualizada correctamente.',
            'asistencia' => $asistencia->load([
                'personal.persona',
                'personal.cargo'
            ])
        ]);
    }


    // ELIMINAR ASISTENCIA
    public function destroy($id)
    {
        $asistencia = Asistencia::find($id);

        if (!$asistencia) {
            return response()->json([
                'message' => 'Asistencia no encontrada.'
            ], 404);
        }

        $asistencia->delete();

        return response()->json([
            'message' => 'Asistencia eliminada correctamente.'
        ]);
    }
}