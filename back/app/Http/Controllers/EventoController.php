<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    // LISTAR EVENTOS
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = Evento::with('responsable.persona');

        // Buscar
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'ilike', "%{$buscar}%")
                    ->orWhere('tipo', 'ilike', "%{$buscar}%")
                    ->orWhere('departamento', 'ilike', "%{$buscar}%")
                    ->orWhere('lugar', 'ilike', "%{$buscar}%");
            });
        }

        // Filtrar por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // Filtrar por departamento
        if ($request->filled('departamento')) {
            $query->where('departamento', $request->departamento);
        }

        // Filtrar por estado
        if ($request->has('estado')) {
            $query->where('estado', filter_var($request->estado, FILTER_VALIDATE_BOOLEAN));
        }

        $eventos = $query->orderBy('fecha_inicio', 'desc')->paginate($limit);

        return response()->json($eventos);
    }


    // REGISTRAR EVENTO
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string|max:50',
            'departamento' => 'required|string|max:100',
            'lugar' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'id_responsable' => 'nullable|exists:personal,id_personal',
            'presupuesto_estimado' => 'nullable|numeric|min:0',
            'estado' => 'nullable|boolean',
            'observacion' => 'nullable|string',
        ]);

        $evento = Evento::create([
            'nombre' => $request->nombre,
            'tipo' => $request->tipo,
            'departamento' => $request->departamento,
            'lugar' => $request->lugar,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'id_responsable' => $request->id_responsable,
            'presupuesto_estimado' => $request->presupuesto_estimado,
            'estado' => $request->estado ?? true,
            'observacion' => $request->observacion,
        ]);

        return response()->json([
            'message' => 'Evento registrado correctamente.',
            'evento' => $evento->load('responsable.persona')
        ], 201);
    }


    // MOSTRAR EVENTO
    public function show($id)
    {
        $evento = Evento::with([
            'responsable.persona',
            'ubicaciones'
        ])->find($id);

        if (!$evento) {
            return response()->json([
                'message' => 'Evento no encontrado.'
            ], 404);
        }

        return response()->json($evento);
    }


    // ACTUALIZAR EVENTO
    public function update(Request $request, $id)
    {
        $evento = Evento::find($id);

        if (!$evento) {
            return response()->json([
                'message' => 'Evento no encontrado.'
            ], 404);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string|max:50',
            'departamento' => 'required|string|max:100',
            'lugar' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'id_responsable' => 'nullable|exists:personal,id_personal',
            'presupuesto_estimado' => 'nullable|numeric|min:0',
            'estado' => 'required|boolean',
            'observacion' => 'nullable|string',
        ]);

        $evento->update([
            'nombre' => $request->nombre,
            'tipo' => $request->tipo,
            'departamento' => $request->departamento,
            'lugar' => $request->lugar,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'id_responsable' => $request->id_responsable,
            'presupuesto_estimado' => $request->presupuesto_estimado,
            'estado' => $request->estado,
            'observacion' => $request->observacion,
        ]);

        return response()->json([
            'message' => 'Evento actualizado correctamente.',
            'evento' => $evento->load('responsable.persona')
        ]);
    }


    // ELIMINAR EVENTO
    public function destroy($id)
    {
        $evento = Evento::find($id);

        if (!$evento) {
            return response()->json([
                'message' => 'Evento no encontrado.'
            ], 404);
        }

        // No eliminar si tiene ubicaciones/stands relacionados
        if ($evento->ubicaciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el evento porque tiene ubicaciones o stands relacionados.'
            ], 422);
        }

        $evento->delete();

        return response()->json([
            'message' => 'Evento eliminado correctamente.'
        ]);
    }
}