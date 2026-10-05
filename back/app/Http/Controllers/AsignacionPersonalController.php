<?php

namespace App\Http\Controllers;

use App\Models\AsignacionPersonal;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AsignacionPersonalController extends Controller
{
    // LISTAR ASIGNACIONES
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = AsignacionPersonal::with([
            'personal.persona',
            'personal.cargo',
            'ubicacion',
            'evento'
        ]);

        // Buscar por personal
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

        // Filtrar por ubicación
        if ($request->filled('id_ubicacion')) {
            $query->where('id_ubicacion', $request->id_ubicacion);
        }

        // Filtrar por evento
        if ($request->filled('id_evento')) {
            $query->where('id_evento', $request->id_evento);
        }

        // Filtrar por estado
        if ($request->has('estado')) {
            $query->where('estado', filter_var($request->estado, FILTER_VALIDATE_BOOLEAN));
        }

        $asignaciones = $query
            ->orderBy('fecha_inicio', 'desc')
            ->paginate($limit);

        return response()->json($asignaciones);
    }


    // REGISTRAR ASIGNACIÓN
    public function store(Request $request)
    {
        $request->validate([
            'id_personal' => 'required|exists:personal,id_personal',
            'id_ubicacion' => 'nullable|exists:ubicaciones,id_ubicacion',
            'id_evento' => 'nullable|exists:eventos,id_evento',
            'funcion_asignada' => 'required|string|max:100',
            'tipo_remuneracion' => ['required', Rule::in(['SALARIO', 'MONTO_FIJO', 'COMISION', 'MIXTO'])],
            'monto_fijo' => 'nullable|numeric|min:0',
            'comision_polvo' => 'nullable|numeric|min:0',
            'comision_granola' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|boolean',
        ]);

        // Debe existir al menos una ubicación o un evento
        if (!$request->filled('id_ubicacion') && !$request->filled('id_evento')) {
            return response()->json([
                'message' => 'La asignación debe estar relacionada con una ubicación o un evento.'
            ], 422);
        }

        // Validar relación STAND - EVENTO
        $validacion = $this->validarUbicacionEvento($request);

        if ($validacion) {
            return $validacion;
        }

        $asignacion = AsignacionPersonal::create([
            'id_personal' => $request->id_personal,
            'id_ubicacion' => $request->id_ubicacion,
            'id_evento' => $request->id_evento,
            'funcion_asignada' => $request->funcion_asignada,
            'tipo_remuneracion' => $request->tipo_remuneracion,
            'monto_fijo' => $request->monto_fijo,
            'comision_polvo' => $request->comision_polvo,
            'comision_granola' => $request->comision_granola,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estado' => $request->estado ?? true,
        ]);

        return response()->json([
            'message' => 'Asignación registrada correctamente.',
            'asignacion' => $asignacion->load([
                'personal.persona',
                'personal.cargo',
                'ubicacion',
                'evento'
            ])
        ], 201);
    }


    // MOSTRAR ASIGNACIÓN
    public function show($id)
    {
        $asignacion = AsignacionPersonal::with([
            'personal.persona',
            'personal.cargo',
            'ubicacion',
            'evento'
        ])->find($id);

        if (!$asignacion) {
            return response()->json([
                'message' => 'Asignación no encontrada.'
            ], 404);
        }

        return response()->json($asignacion);
    }


    // ACTUALIZAR ASIGNACIÓN
    public function update(Request $request, $id)
    {
        $asignacion = AsignacionPersonal::find($id);

        if (!$asignacion) {
            return response()->json([
                'message' => 'Asignación no encontrada.'
            ], 404);
        }

        $request->validate([
            'id_personal' => 'required|exists:personal,id_personal',
            'id_ubicacion' => 'nullable|exists:ubicaciones,id_ubicacion',
            'id_evento' => 'nullable|exists:eventos,id_evento',
            'funcion_asignada' => 'required|string|max:100',
            'tipo_remuneracion' => ['required', Rule::in(['SALARIO', 'MONTO_FIJO', 'COMISION', 'MIXTO'])],
            'monto_fijo' => 'nullable|numeric|min:0',
            'comision_polvo' => 'nullable|numeric|min:0',
            'comision_granola' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'required|boolean',
        ]);

        if (!$request->filled('id_ubicacion') && !$request->filled('id_evento')) {
            return response()->json([
                'message' => 'La asignación debe estar relacionada con una ubicación o un evento.'
            ], 422);
        }

        $validacion = $this->validarUbicacionEvento($request);

        if ($validacion) {
            return $validacion;
        }

        $asignacion->update([
            'id_personal' => $request->id_personal,
            'id_ubicacion' => $request->id_ubicacion,
            'id_evento' => $request->id_evento,
            'funcion_asignada' => $request->funcion_asignada,
            'tipo_remuneracion' => $request->tipo_remuneracion,
            'monto_fijo' => $request->monto_fijo,
            'comision_polvo' => $request->comision_polvo,
            'comision_granola' => $request->comision_granola,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estado' => $request->estado,
        ]);

        return response()->json([
            'message' => 'Asignación actualizada correctamente.',
            'asignacion' => $asignacion->load([
                'personal.persona',
                'personal.cargo',
                'ubicacion',
                'evento'
            ])
        ]);
    }


    // ELIMINAR ASIGNACIÓN
    public function destroy($id)
    {
        $asignacion = AsignacionPersonal::find($id);

        if (!$asignacion) {
            return response()->json([
                'message' => 'Asignación no encontrada.'
            ], 404);
        }

        $asignacion->delete();

        return response()->json([
            'message' => 'Asignación eliminada correctamente.'
        ]);
    }


    // VALIDAR RELACIÓN ENTRE UBICACIÓN Y EVENTO
    private function validarUbicacionEvento(Request $request)
    {
        if (!$request->filled('id_ubicacion')) {
            return null;
        }

        $ubicacion = Ubicacion::find($request->id_ubicacion);

        if (!$ubicacion) {
            return response()->json([
                'message' => 'La ubicación seleccionada no existe.'
            ], 422);
        }

        // Si es STAND debe tener evento
        if ($ubicacion->tipo === 'STAND') {

            if (!$request->filled('id_evento')) {
                return response()->json([
                    'message' => 'Una asignación a un STAND debe indicar el evento.'
                ], 422);
            }

            if ((int) $ubicacion->id_evento !== (int) $request->id_evento) {
                return response()->json([
                    'message' => 'El stand seleccionado no pertenece al evento indicado.'
                ], 422);
            }
        }

        // ALMACÉN o SUCURSAL no deben pertenecer a un evento
        if (
            in_array($ubicacion->tipo, ['ALMACEN', 'SUCURSAL']) &&
            $request->filled('id_evento')
        ) {
            return response()->json([
                'message' => 'Una asignación a almacén o sucursal no debe estar relacionada con un evento.'
            ], 422);
        }

        return null;
    }
}

