<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\Presentacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoteController extends Controller
{
    // LISTAR, BUSCAR Y FILTRAR
    public function index(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 100);
        $buscar = $request->query('buscar');
        $estado = $request->query('estado');
        $vencimiento = $request->query('vencimiento');

        $query = Lote::with('presentacion.producto');

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_lote', 'ilike', "%{$buscar}%")
                    ->orWhereHas('presentacion', function ($p) use ($buscar) {
                        $p->where('nombre_presentacion', 'ilike', "%{$buscar}%")
                          ->orWhere('codigo_sku', 'ilike', "%{$buscar}%");
                    })
                    ->orWhereHas('presentacion.producto', function ($p) use ($buscar) {
                        $p->where('nombre', 'ilike', "%{$buscar}%");
                    });
            });
        }

        if ($estado !== null && $estado !== '') {
            $query->where('estado', filter_var($estado, FILTER_VALIDATE_BOOLEAN));
        }

        $hoy = now()->startOfDay();
        $fecha90 = $hoy->copy()->addDays(90);
        $fecha180 = $hoy->copy()->addDays(180);

        switch ($vencimiento) {
            case 'VENCIDO':
                $query->whereDate('fecha_vencimiento', '<', $hoy);
                break;

            case 'PROXIMO':
                $query->whereBetween('fecha_vencimiento', [
                    $hoy->toDateString(),
                    $fecha90->toDateString()
                ]);
                break;

            case 'PREVENTIVO':
                $query->whereDate('fecha_vencimiento', '>', $fecha90)
                    ->whereDate('fecha_vencimiento', '<=', $fecha180);
                break;

            case 'VIGENTE':
                $query->whereDate('fecha_vencimiento', '>', $fecha180);
                break;
        }

        return response()->json(
            $query->orderBy('fecha_vencimiento')
                ->orderBy('id_lote')
                ->paginate($limit)
        );
    }

    // VALIDAR DATOS
    private function validar(Request $request, ?Lote $lote = null)
    {
        return $request->validate([
            'id_presentacion' => [
                'required', 'integer',
                'exists:presentaciones,id_presentacion'
            ],
            'codigo_lote' => [
                'required', 'string', 'max:100',
                Rule::unique('lotes', 'codigo_lote')
                    ->ignore($lote?->id_lote, 'id_lote')
            ],
            'fecha_produccion' => 'required|date',
            'vida_util_meses' => 'required|integer|min:1|max:120',
            'cantidad_producida' => 'required|integer|min:1',
            'observacion' => 'nullable|string'
        ]);
    }

    // CALCULAR VENCIMIENTO
    private function calcularVencimiento(array &$datos)
    {
        $datos['fecha_vencimiento'] = Carbon::parse(
            $datos['fecha_produccion']
        )->addMonthsNoOverflow(
            (int) $datos['vida_util_meses']
        )->toDateString();
    }

    // CREAR LOTE
    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $presentacion = Presentacion::with('producto')
            ->find($datos['id_presentacion']);

        if (
            !$presentacion->estado ||
            !$presentacion->producto ||
            !$presentacion->producto->estado
        ) {
            return response()->json([
                'message' => 'La presentación y su producto deben estar activos para registrar un lote.'
            ], 422);
        }

        $this->calcularVencimiento($datos);
        $datos['estado'] = true;

        $lote = Lote::create($datos);

        return response()->json([
            'message' => 'Lote registrado correctamente.',
            'lote' => $lote
        ], 201);
    }

    // MOSTRAR LOTE
    public function show($id)
    {
        $lote = Lote::with('presentacion.producto')->find($id);

        if (!$lote) {
            return response()->json([
                'message' => 'Lote no encontrado.'
            ], 404);
        }

        return response()->json($lote);
    }

    // ACTUALIZAR LOTE
    public function update(Request $request, $id)
    {
        $lote = Lote::find($id);

        if (!$lote) {
            return response()->json([
                'message' => 'Lote no encontrado.'
            ], 404);
        }

        $datos = $this->validar($request, $lote);
        $this->calcularVencimiento($datos);

        $tieneHistorial = $lote->inventarios()->exists()
            || $lote->detallesMovimiento()->exists();

        // Proteger datos históricos si existen inventarios
        // o movimientos asociados.
        if ($tieneHistorial) {
            $camposProtegidos = [
                'id_presentacion',
                'codigo_lote',
                'fecha_produccion',
                'vida_util_meses',
                'fecha_vencimiento',
                'cantidad_producida'
            ];

            foreach ($camposProtegidos as $campo) {
                $valorActual = $lote->{$campo};

                if ($valorActual instanceof \DateTimeInterface) {
                    $valorActual = $valorActual->format('Y-m-d');
                }

                if ((string) $valorActual !== (string) $datos[$campo]) {
                    return response()->json([
                        'message' => "No se puede modificar {$campo} porque el lote ya tiene registros de inventario o movimientos."
                    ], 422);
                }
            }
        }

        // Si cambia de presentación, comprobar que esté activa.
        if ((int) $lote->id_presentacion !== (int) $datos['id_presentacion']) {
            $presentacion = Presentacion::with('producto')
                ->find($datos['id_presentacion']);

            if (
                !$presentacion->estado ||
                !$presentacion->producto ||
                !$presentacion->producto->estado
            ) {
                return response()->json([
                    'message' => 'No se puede asociar el lote a una presentación o producto inactivo.'
                ], 422);
            }
        }

        // El estado se modifica únicamente desde cambiarEstado().
        $lote->update($datos);

        return response()->json([
            'message' => 'Lote actualizado correctamente.',
            'lote' => $lote->fresh()
        ]);
    }

    // ACTIVAR O INACTIVAR
    public function cambiarEstado(Request $request, $id)
    {
        $datos = $request->validate([
            'estado' => 'required|boolean'
        ]);

        $lote = Lote::with('presentacion.producto')->find($id);

        if (!$lote) {
            return response()->json([
                'message' => 'Lote no encontrado.'
            ], 404);
        }

        if ($datos['estado']) {
            $presentacion = $lote->presentacion;

            if (
                !$presentacion ||
                !$presentacion->estado ||
                !$presentacion->producto ||
                !$presentacion->producto->estado
            ) {
                return response()->json([
                    'message' => 'No se puede activar un lote si su presentación o producto está inactivo.'
                ], 422);
            }
        }

        $lote->estado = $datos['estado'];
        $lote->save();

        return response()->json([
            'message' => $lote->estado
                ? 'Lote activado correctamente.'
                : 'Lote inactivado correctamente.',
            'lote' => $lote
        ]);
    }

    // ELIMINACIÓN LÓGICA
    public function destroy($id)
    {
        $lote = Lote::find($id);

        if (!$lote) {
            return response()->json([
                'message' => 'Lote no encontrado.'
            ], 404);
        }

        if (
            $lote->inventarios()->exists() ||
            $lote->detallesMovimiento()->exists()
        ) {
            return response()->json([
                'message' => 'El lote tiene registros de inventario o movimientos. Puede inactivarlo para conservar su historial.'
            ], 422);
        }

        $lote->delete();

        return response()->json([
            'message' => 'Lote enviado a la papelera correctamente.'
        ]);
    }

    // PAPELERA
    public function papelera(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 100);

        return response()->json(
            Lote::onlyTrashed()
                ->with('presentacion.producto')
                ->orderByDesc('deleted_at')
                ->paginate($limit)
        );
    }

    // RESTAURAR LOTE
    public function restaurar($id)
    {
        $lote = Lote::onlyTrashed()->find($id);

        if (!$lote) {
            return response()->json([
                'message' => 'Lote eliminado no encontrado.'
            ], 404);
        }

        if (!Presentacion::whereKey($lote->id_presentacion)->exists()) {
            return response()->json([
                'message' => 'Primero debe restaurar la presentación asociada.'
            ], 422);
        }

        $lote->restore();

        return response()->json([
            'message' => 'Lote restaurado correctamente.',
            'lote' => $lote
        ]);
    }
}
