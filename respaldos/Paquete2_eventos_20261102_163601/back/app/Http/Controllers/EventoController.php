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
    if (!in_array($limit, [10, 25, 50], true)) $limit = 10;

    $query = Evento::with('responsable.persona');

    if ($request->filled('buscar')) {
        $buscar = trim($request->query('buscar'));

        $query->where(function ($q) use ($buscar) {
            $q->where('nombre', 'ilike', "%{$buscar}%")
              ->orWhere('tipo', 'ilike', "%{$buscar}%")
              ->orWhere('departamento', 'ilike', "%{$buscar}%")
              ->orWhere('lugar', 'ilike', "%{$buscar}%");
        });
    }

    if ($request->filled('tipo')) {
        $query->where('tipo', $request->query('tipo'));
    }

    if ($request->filled('departamento')) {
        $query->where('departamento', $request->query('departamento'));
    }

    if ($request->filled('estado')) {
        $estado = filter_var(
            $request->query('estado'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($estado !== null) {
            $query->where('estado', $estado);
        }
    }

    return response()->json(
        $query->orderByDesc('fecha_inicio')->paginate($limit)
    );
}


    // VALIDACIONES COMPARTIDAS
    private function validar(Request $request)
    {
        return $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string|max:50',
            'departamento' => 'required|string|max:100',
            'lugar' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'id_responsable' => 'nullable|integer|exists:personal,id_personal',
            'presupuesto_estimado' => 'nullable|numeric|min:0',
            'observacion' => 'nullable|string',
            'estado' => 'sometimes|boolean'
        ]);
    }

    // COMPROBAR STOCK EN LOS STANDS DEL EVENTO
    private function tieneStockPendiente(Evento $evento)
    {
        return $evento->ubicaciones()
            ->where('tipo', 'STAND')
            ->whereHas('inventarios', function ($query) {
                $query->where('stock_actual', '>', 0);
            })
            ->exists();
    }

    // COMPROBAR HISTORIAL OPERATIVO
    private function tieneHistorial(Evento $evento)
    {
        if ($evento->asignacionesPersonal()->exists()) {
            return true;
        }

        return $evento->ubicaciones()
            ->where(function ($query) {
                $query->whereHas('inventarios')
                    ->orWhereHas('movimientosOrigen')
                    ->orWhereHas('movimientosDestino');
            })
            ->exists();
    }

    // CREAR EVENTO
    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $datos['estado'] = $datos['estado'] ?? true;

        $evento = Evento::create($datos);

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
            'ubicaciones',
            'asignacionesPersonal'
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

        $datos = $this->validar($request);
        $nuevoEstado = $datos['estado'] ?? $evento->estado;
        if (in_array($evento->estado_operativo, ['FINALIZADO','CERRADO'], true) && $nuevoEstado) {
            return response()->json(['message'=>'No se puede reactivar un evento finalizado o cerrado desde edición.'],422);
        }

        // No permitir cerrar el evento con mercadería pendiente.
        if (!$nuevoEstado && $evento->estado_operativo !== 'CERRADO') {
            return response()->json(['message'=>'Utilice Finalizar y Cerrar evento para validar cajas, transferencias y sobrantes.'],422);
        }
        if (!$nuevoEstado && $this->tieneStockPendiente($evento)) {
            return response()->json([
                'message' => 'No se puede cerrar el evento: uno o más stands todavía tienen existencias. Registre las ventas, devoluciones o ajustes correspondientes.'
            ], 422);
        }

        // Proteger datos históricos cuando ya existan operaciones.
        if ($this->tieneHistorial($evento)) {
            $protegidos = [
                'nombre', 'tipo', 'departamento', 'lugar',
                'fecha_inicio', 'fecha_fin', 'id_responsable'
            ];

            foreach ($protegidos as $campo) {
                $actual = $evento->{$campo};
                $nuevo = $datos[$campo] ?? null;

                if ($actual instanceof \DateTimeInterface) {
                    $actual = $actual->format('Y-m-d');
                }

                if ((string) ($actual ?? '') !== (string) ($nuevo ?? '')) {
                    return response()->json([
                        'message' => "No se puede modificar {$campo} porque el evento ya tiene historial operativo."
                    ], 422);
                }
            }
        }

        $datos['estado'] = $nuevoEstado;
        $evento->update($datos);

        return response()->json([
            'message' => $nuevoEstado
                ? 'Evento actualizado correctamente.'
                : 'Evento inactivado correctamente. Las existencias de sus stands están en cero.',
            'evento' => $evento->fresh()->load('responsable.persona')
        ]);
    }

    // ELIMINAR EVENTO SIN HISTORIAL
    public function destroy($id)
    {
        $evento = Evento::find($id);

        if (!$evento) {
            return response()->json([
                'message' => 'Evento no encontrado.'
            ], 404);
        }

        if (
            $evento->ubicaciones()->exists() ||
            $evento->asignacionesPersonal()->exists()
        ) {
            return response()->json([
                'message' => 'El evento tiene ubicaciones o personal asignado. No puede eliminarse; conserve su historial.'
            ], 422);
        }

        $evento->delete();

        return response()->json([
            'message' => 'Evento eliminado correctamente.'
        ]);
    }
}
