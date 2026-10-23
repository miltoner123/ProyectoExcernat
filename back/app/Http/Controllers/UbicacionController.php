<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UbicacionController extends Controller
{
    // LISTAR Y FILTRAR UBICACIONES
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        if (!in_array($limit, [10, 25, 50])) $limit = 10;

        $query = Ubicacion::with('evento');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'ilike', "%{$buscar}%")
                  ->orWhere('departamento', 'ilike', "%{$buscar}%")
                  ->orWhere('direccion', 'ilike', "%{$buscar}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', strtoupper($request->tipo));
        }

        if ($request->filled('id_evento')) {
            $query->where('id_evento', $request->integer('id_evento'));
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where(
                'estado',
                filter_var($request->estado, FILTER_VALIDATE_BOOLEAN)
            );
        }

        return response()->json(
            $query->orderByDesc('id_ubicacion')->paginate($limit)
        );
    }

    // VALIDACIONES COMPARTIDAS
    private function validar(Request $request)
    {
        return $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:ALMACEN,SUCURSAL,STAND',
            'propiedad' => 'required|in:PROPIA,EXTERNA,TEMPORAL',
            'departamento' => 'required|string|max:100',
            'direccion' => 'nullable|string|max:255',
            'id_evento' => 'nullable|integer|exists:eventos,id_evento',
            'estado' => 'sometimes|boolean'
        ]);
    }

    // REGLAS PARA UBICACIONES TEMPORALES
    private function validarRelacion(array $datos)
    {
        $tipo = $datos['tipo'];
        $eventoId = $datos['id_evento'] ?? null;

        if ($tipo === 'STAND') {
            if (!$eventoId) {
                throw ValidationException::withMessages([
                    'id_evento' => 'El stand debe estar vinculado a un evento.'
                ]);
            }

            if ($datos['propiedad'] !== 'TEMPORAL') {
                throw ValidationException::withMessages([
                    'propiedad' => 'Un stand de feria debe tener propiedad TEMPORAL.'
                ]);
            }

            $evento = Evento::find($eventoId);

            if (!$evento || !$evento->estado) {
                throw ValidationException::withMessages([
                    'id_evento' => 'El evento seleccionado no está activo.'
                ]);
            }
        } elseif ($eventoId) {
            throw ValidationException::withMessages([
                'id_evento' => 'Solo las ubicaciones STAND pueden vincularse a eventos.'
            ]);
        }
    }

    // COMPROBAR EXISTENCIAS
    private function tieneStock(Ubicacion $ubicacion)
    {
        return $ubicacion->inventarios()
            ->where('stock_actual', '>', 0)
            ->exists();
    }

    // CREAR UBICACIÓN
    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $this->validarRelacion($datos);

        $datos['id_evento'] = $datos['tipo'] === 'STAND'
            ? $datos['id_evento']
            : null;

        $datos['estado'] = $datos['estado'] ?? true;

        $ubicacion = Ubicacion::create($datos);

        return response()->json([
            'message' => 'Ubicación registrada correctamente.',
            'ubicacion' => $ubicacion->load('evento')
        ], 201);
    }

    // MOSTRAR UBICACIÓN
    public function show($id)
    {
        $ubicacion = Ubicacion::with('evento')->find($id);

        if (!$ubicacion) {
            return response()->json([
                'message' => 'Ubicación no encontrada.'
            ], 404);
        }

        return response()->json($ubicacion);
    }

    // ACTUALIZAR UBICACIÓN
        
    public function update(Request $request, $id)
    {
        $ubicacion = Ubicacion::find($id);

        if (!$ubicacion) {
            return response()->json([
                'message' => 'Ubicación no encontrada.'
            ], 404);
        }

        $datos = $this->validar($request);
        $datos['id_evento'] = $datos['tipo'] === 'STAND'
            ? ($datos['id_evento'] ?? null)
            : null;

        $tieneHistorial =
            $ubicacion->inventarios()->exists() ||
            $ubicacion->movimientosOrigen()->exists() ||
            $ubicacion->movimientosDestino()->exists() ||
            $ubicacion->asignacionesPersonal()->exists();

        $cambiaTipo = $ubicacion->tipo !== $datos['tipo'];
        $cambiaEvento = (int) $ubicacion->id_evento !== (int) $datos['id_evento'];

        if ($tieneHistorial && ($cambiaTipo || $cambiaEvento)) {
            return response()->json([
                'message' => 'Esta ubicación tiene historial operativo. Puede modificar su nombre, departamento y dirección, pero no su tipo ni el evento asociado.'
            ], 422);
        }

        if ($cambiaTipo || $cambiaEvento) {
            $this->validarRelacion($datos);
        } elseif ($datos['tipo'] === 'STAND' && $datos['propiedad'] !== 'TEMPORAL') {
            return response()->json([
                'message' => 'Los stands deben tener propiedad TEMPORAL.'
            ], 422);
        }

        $nuevoEstado = $datos['estado'] ?? $ubicacion->estado;

        if (!$nuevoEstado && $this->tieneStock($ubicacion)) {
            return response()->json([
                'message' => 'No puede inactivar esta ubicación porque todavía tiene existencias. Registre primero las ventas, devoluciones o movimientos correspondientes.'
            ], 422);
        }

        $datos['estado'] = $nuevoEstado;
        $ubicacion->update($datos);

        return response()->json([
            'message' => 'Ubicación actualizada correctamente.',
            'ubicacion' => $ubicacion->fresh()->load('evento')
        ]);
    }

    // ELIMINAR UBICACIÓN SIN HISTORIAL
    public function destroy($id)
    {
        $ubicacion = Ubicacion::find($id);

        if (!$ubicacion) {
            return response()->json([
                'message' => 'Ubicación no encontrada.'
            ], 404);
        }

        $tieneHistorial =
            $ubicacion->inventarios()->exists() ||
            $ubicacion->movimientosOrigen()->exists() ||
            $ubicacion->movimientosDestino()->exists() ||
            $ubicacion->asignacionesPersonal()->exists();

        if ($tieneHistorial) {
            return response()->json([
                'message' => 'Esta ubicación tiene historial. No debe eliminarse; puede inactivarla cuando su inventario esté en cero.'
            ], 422);
        }

        $ubicacion->delete();

        return response()->json([
            'message' => 'Ubicación eliminada correctamente.'
        ]);
    }
}

