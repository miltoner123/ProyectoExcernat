<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovimientoInventarioController extends Controller
{
    private const RELACIONES = [
        'origen',
        'destino',
        'detalles.lote.presentacion.producto'
    ];

    // LISTAR Y BUSCAR MOVIMIENTOS
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        if (!in_array($limit, [10, 25, 50])) $limit = 10;

        $query = MovimientoInventario::with(self::RELACIONES);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('observacion', 'ilike', "%{$buscar}%")
                ->orWhere('referencia', 'ilike', "%{$buscar}%")
                  ->orWhereHas('origen', fn($s) =>
                      $s->where('nombre', 'ilike', "%{$buscar}%"))
                  ->orWhereHas('destino', fn($s) =>
                      $s->where('nombre', 'ilike', "%{$buscar}%"))
                  ->orWhereHas('detalles.lote', fn($s) =>
                      $s->where('codigo_lote', 'ilike', "%{$buscar}%"));
            });
        }

        return response()->json(
            $query->orderByDesc('id_movimiento')->paginate($limit)
        );
    }

    // CONSULTAR MOVIMIENTO
    public function show($id)
    {
        $movimiento = MovimientoInventario::with(self::RELACIONES)->find($id);

        return $movimiento
            ? response()->json($movimiento)
            : response()->json(['message' => 'Movimiento no encontrado.'], 404);
    }

    // REGISTRAR MOVIMIENTO
    public function store(Request $request)
    {
        $datos = $request->validate([
            'tipo' => 'required|in:ENTRADA,SALIDA,TRANSFERENCIA,DEVOLUCION,AJUSTE',
            'id_ubicacion_origen' => 'nullable|exists:ubicaciones,id_ubicacion',
            'id_ubicacion_destino' => 'nullable|exists:ubicaciones,id_ubicacion',
            'observacion' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_lote' => 'required|integer|distinct|exists:lotes,id_lote',
            'detalles.*.cantidad' => 'required|integer|not_in:0'
        ]);

        // La entrada de producción solo puede confirmarla Almacén Central.
        if ($datos['tipo'] === 'ENTRADA' && !$request->user()?->can('recepciones.confirmar')) {
            abort(403, 'Solo el encargado de almacén puede confirmar entradas de producción.');
        }

        if ($datos['tipo'] === 'ENTRADA') {
            throw ValidationException::withMessages([
                'tipo' => 'La producción se recibe únicamente desde Recepciones pendientes del Almacén Central.'
            ]);
        }

        $tipo = $datos['tipo'];
        $origen = $datos['id_ubicacion_origen'] ?? null;
        $destino = $datos['id_ubicacion_destino'] ?? null;

        $this->validarOperacion($datos, $tipo, $origen, $destino);

        $movimiento = DB::transaction(function () use (
            $datos, $tipo, $origen, $destino
        ) {
            // Bloquear ubicaciones en orden estable.
            $idsUbicaciones = array_values(array_unique(
                array_filter([$origen, $destino])
            ));
            sort($idsUbicaciones, SORT_NUMERIC);

            foreach ($idsUbicaciones as $id) {
                $ubicacion = Ubicacion::whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (!$ubicacion || !$ubicacion->estado) {
                    throw ValidationException::withMessages([
                        'ubicaciones' => 'Las ubicaciones deben estar activas.'
                    ]);
                }
            }

            // Bloquear lotes antes de comprobar producción.
            $idsLotes = array_column($datos['detalles'], 'id_lote');
            sort($idsLotes, SORT_NUMERIC);

            $lotes = [];

            foreach ($idsLotes as $id) {
                $lote = Lote::whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (!$lote || !$lote->estado) {
                    throw ValidationException::withMessages([
                        'detalles' => "El lote {$id} no está activo."
                    ]);
                }

                $lotes[$id] = $lote;
            }

            // Controlar ingresos históricos de producción.
            if ($tipo === 'ENTRADA') {
                foreach ($datos['detalles'] as $detalle) {
                    $this->validarProduccion(
                        $lotes[$detalle['id_lote']],
                        (int) $detalle['cantidad']
                    );
                }
            }

            // Crear encabezado del movimiento.
            $movimiento = MovimientoInventario::create([
                'tipo' => $tipo,
                'id_ubicacion_origen' => $origen,
                'id_ubicacion_destino' => $destino,
                'fecha_movimiento' => now(),
                'referencia' => $tipo === 'ENTRADA' ? 'RECEPCION-USUARIO-' . auth()->id() : null,
                'observacion' => $datos['observacion'] ?? null
            ]);

            // Aplicar movimientos y registrar detalles.
            foreach ($datos['detalles'] as $detalle) {
                $idLote = $detalle['id_lote'];
                $cantidad = (int) $detalle['cantidad'];

                switch ($tipo) {
                    case 'ENTRADA':
                        $this->modificarStock($destino, $idLote, $cantidad);
                        break;

                    case 'SALIDA':
                        $this->modificarStock($origen, $idLote, -$cantidad);
                        break;

                    case 'TRANSFERENCIA':
                    case 'DEVOLUCION':
                        $this->modificarStock($origen, $idLote, -$cantidad);
                        $this->modificarStock($destino, $idLote, $cantidad);
                        break;

                    case 'AJUSTE':
                        $this->modificarStock($origen, $idLote, $cantidad);
                        break;
                }

                $movimiento->detalles()->create([
                    'id_lote' => $idLote,
                    'cantidad' => $cantidad
                ]);
            }

            return $movimiento->load(self::RELACIONES);
        }, 3);

        return response()->json([
            'message' => 'Movimiento registrado correctamente.',
            'movimiento' => $movimiento
        ], 201);
    }

    // VALIDAR TIPO DE OPERACIÓN
    private function validarOperacion(array $datos, $tipo, $origen, $destino)
    {
        if ($tipo === 'ENTRADA') {
            if (!$destino || $origen) {
                throw ValidationException::withMessages([
                    'tipo' => 'ENTRADA requiere destino y no admite origen.'
                ]);
            }

            // La producción ingresa primero al almacén.
            $ubicacion = Ubicacion::find($destino);

            if (!$ubicacion || $ubicacion->tipo !== 'ALMACEN' || mb_strtoupper(trim($ubicacion->nombre)) !== 'ALMACÉN CENTRAL' && mb_strtoupper(trim($ubicacion->nombre)) !== 'ALMACEN CENTRAL') {
                throw ValidationException::withMessages([
                    'id_ubicacion_destino' =>
                        'La producción debe ingresar exclusivamente al ALMACÉN CENTRAL.'
                ]);
            }
        }

        if (in_array($tipo, ['SALIDA', 'AJUSTE'])) {
            if (!$origen || $destino) {
                throw ValidationException::withMessages([
                    'tipo' => "{$tipo} requiere origen y no admite destino."
                ]);
            }
        }

        if (in_array($tipo, ['TRANSFERENCIA', 'DEVOLUCION'])) {
            if (!$origen || !$destino || (int) $origen === (int) $destino) {
                throw ValidationException::withMessages([
                    'tipo' => 'Seleccione ubicaciones de origen y destino diferentes.'
                ]);
            }
        }

        foreach ($datos['detalles'] as $detalle) {
            if ($tipo !== 'AJUSTE' && (int) $detalle['cantidad'] <= 0) {
                throw ValidationException::withMessages([
                    'detalles' => 'Las cantidades deben ser mayores a cero.'
                ]);
            }
        }

        if ($tipo === 'AJUSTE' && empty(trim($datos['observacion'] ?? ''))) {
            throw ValidationException::withMessages([
                'observacion' => 'Debe justificar el ajuste de inventario.'
            ]);
        }
    }

    // EVITAR INGRESOS SUPERIORES A LA PRODUCCIÓN ORIGINAL
    private function validarProduccion(Lote $lote, int $cantidad)
    {
        $ingresado = DB::table('detalle_movimiento as d')
            ->join(
                'movimientos_inventario as m',
                'm.id_movimiento',
                '=',
                'd.id_movimiento'
            )
            ->where('d.id_lote', $lote->id_lote)
            ->where('m.tipo', 'ENTRADA')
            ->sum('d.cantidad');
        $pendiente = (int) $lote->cantidad_producida - (int) $ingresado;

        if ($cantidad > $pendiente) {
            throw ValidationException::withMessages([
                'detalles' =>
                    "El lote {$lote->codigo_lote} tiene {$pendiente} unidades pendientes de ingreso. No puede ingresar {$cantidad}."
            ]);
        }
    }

    // MODIFICAR EXISTENCIAS POR LOTE Y UBICACIÓN
    private function modificarStock($idUbicacion, $idLote, $variacion)
    {
        $inventario = Inventario::where('id_ubicacion', $idUbicacion)
            ->where('id_lote', $idLote)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            if ($variacion < 0) {
                throw ValidationException::withMessages([
                    'stock' =>
                        "No existe stock del lote {$idLote} en la ubicación {$idUbicacion}."
                ]);
            }

            $inventario = Inventario::create([
                'id_ubicacion' => $idUbicacion,
                'id_lote' => $idLote,
                'stock_actual' => 0,
                'stock_minimo' => 0
            ]);
        }

        $nuevoStock = (int) $inventario->stock_actual + $variacion;

        if ($nuevoStock < 0) {
            throw ValidationException::withMessages([
                'stock' =>
                    "Stock insuficiente del lote {$idLote}. Disponible: {$inventario->stock_actual}."
            ]);
        }

        $inventario->stock_actual = $nuevoStock;
        $inventario->save();
    }
}
