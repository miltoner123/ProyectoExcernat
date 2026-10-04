<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\MovimientoInventario;
use App\Models\Inventario;
use App\Models\Ubicacion;
use App\Models\Lote;

class MovimientoInventarioController extends Controller
{
    // LISTADO

    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = MovimientoInventario::with([
            'origen',
            'destino',
            'detalles.lote.presentacion.producto'
        ]);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('buscar')) {

            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {

                $q->where('referencia', 'ilike', "%{$buscar}%")

                  ->orWhereHas('origen', function ($sub) use ($buscar) {
                      $sub->where('nombre', 'ilike', "%{$buscar}%");
                  })

                  ->orWhereHas('destino', function ($sub) use ($buscar) {
                      $sub->where('nombre', 'ilike', "%{$buscar}%");
                  })

                  ->orWhereHas('detalles.lote', function ($sub) use ($buscar) {
                      $sub->where('codigo_lote', 'ilike', "%{$buscar}%");
                  });
            });
        }

        return response()->json(
            $query->orderBy('id_movimiento', 'desc')->paginate($limit)
        );
    }


    // CONSULTAR MOVIMIENTO

    public function show($id)
    {
        $movimiento = MovimientoInventario::with([
            'origen',
            'destino',
            'detalles.lote.presentacion.producto'
        ])->find($id);

        if (!$movimiento) {
            return response()->json([
                'message' => 'Movimiento no encontrado.'
            ], 404);
        }

        return response()->json($movimiento);
    }


    // REGISTRAR MOVIMIENTO Y ACTUALIZAR STOCK

    public function store(Request $request)
    {
        $datos = $request->validate([

            'tipo' =>
                'required|in:ENTRADA,SALIDA,TRANSFERENCIA,DEVOLUCION,AJUSTE',

            'id_ubicacion_origen' =>
                'nullable|exists:ubicaciones,id_ubicacion',

            'id_ubicacion_destino' =>
                'nullable|exists:ubicaciones,id_ubicacion',

            'referencia' =>
                'nullable|string|max:100',

            'observacion' =>
                'nullable|string',

            'detalles' =>
                'required|array|min:1',

            'detalles.*.id_lote' =>
                'required|integer|distinct|exists:lotes,id_lote',

            'detalles.*.cantidad' =>
                'required|integer|not_in:0',
        ]);

        $tipo = $datos['tipo'];

        $origen = $datos['id_ubicacion_origen'] ?? null;
        $destino = $datos['id_ubicacion_destino'] ?? null;


        // VALIDAR ORIGEN Y DESTINO SEGÚN EL TIPO

        if ($tipo === 'ENTRADA' && (!$destino || $origen)) {
            throw ValidationException::withMessages([
                'tipo' => 'ENTRADA requiere destino y no admite origen.'
            ]);
        }

        if (in_array($tipo, ['SALIDA', 'AJUSTE']) && (!$origen || $destino)) {
            throw ValidationException::withMessages([
                'tipo' => 'SALIDA y AJUSTE requieren origen y no admiten destino.'
            ]);
        }

        if (in_array($tipo, ['TRANSFERENCIA', 'DEVOLUCION'])) {

            if (!$origen || !$destino || (int) $origen === (int) $destino) {

                throw ValidationException::withMessages([
                    'tipo' => 'Seleccione ubicaciones de origen y destino diferentes.'
                ]);
            }
        }


        // VALIDAR CANTIDADES

        foreach ($datos['detalles'] as $detalle) {

            if ($tipo !== 'AJUSTE' && $detalle['cantidad'] <= 0) {

                throw ValidationException::withMessages([
                    'detalles' => 'Las cantidades deben ser mayores a cero.'
                ]);
            }
        }


        // VALIDAR UBICACIONES Y LOTES ACTIVOS

        $idsUbicaciones = array_values(array_filter([$origen, $destino]));

        $ubicacionesActivas = Ubicacion::whereIn(
            'id_ubicacion',
            $idsUbicaciones
        )
        ->where('estado', true)
        ->count();

        if ($ubicacionesActivas !== count($idsUbicaciones)) {

            throw ValidationException::withMessages([
                'ubicaciones' => 'Las ubicaciones seleccionadas deben estar activas.'
            ]);
        }

        $idsLotes = array_column($datos['detalles'], 'id_lote');

        $lotesActivos = Lote::whereIn('id_lote', $idsLotes)
            ->where('estado', true)
            ->count();

        if ($lotesActivos !== count($idsLotes)) {

            throw ValidationException::withMessages([
                'detalles' => 'Todos los lotes deben estar activos.'
            ]);
        }


        // TRANSACCIÓN: TODO SE GUARDA O TODO SE REVIERTE

        $movimiento = DB::transaction(function () use (
            $datos,
            $tipo,
            $origen,
            $destino
        ) {

            // BLOQUEAR LAS UBICACIONES PARA SERIALIZAR
            // MOVIMIENTOS CONCURRENTES QUE LAS AFECTEN.

            $idsUbicaciones = array_values(array_unique(
                array_filter([$origen, $destino])
            ));

            sort($idsUbicaciones, SORT_NUMERIC);

            foreach ($idsUbicaciones as $idUbicacion) {

                $ubicacion = Ubicacion::where(
                    'id_ubicacion',
                    $idUbicacion
                )
                ->lockForUpdate()
                ->first();

                if (!$ubicacion || !$ubicacion->estado) {

                    throw ValidationException::withMessages([
                        'ubicaciones' => 'Una ubicación ya no está disponible.'
                    ]);
                }
            }


            $movimiento = MovimientoInventario::create([

                'tipo' => $tipo,

                'id_ubicacion_origen' => $origen,

                'id_ubicacion_destino' => $destino,

                'fecha_movimiento' => now(),

                'referencia' => $datos['referencia'] ?? null,

                'observacion' => $datos['observacion'] ?? null,
            ]);


            foreach ($datos['detalles'] as $detalle) {

                $idLote = $detalle['id_lote'];

                $cantidad = (int) $detalle['cantidad'];


                // ENTRADA

                if ($tipo === 'ENTRADA') {

                    $this->modificarStock(
                        $destino,
                        $idLote,
                        $cantidad
                    );
                }


                // SALIDA

                if ($tipo === 'SALIDA') {

                    $this->modificarStock(
                        $origen,
                        $idLote,
                        -$cantidad
                    );
                }


                // TRANSFERENCIA O DEVOLUCIÓN

                if (in_array($tipo, ['TRANSFERENCIA', 'DEVOLUCION'])) {

                    $this->modificarStock(
                        $origen,
                        $idLote,
                        -$cantidad
                    );

                    $this->modificarStock(
                        $destino,
                        $idLote,
                        $cantidad
                    );
                }


                // AJUSTE

                if ($tipo === 'AJUSTE') {

                    $this->modificarStock(
                        $origen,
                        $idLote,
                        $cantidad
                    );
                }


                $movimiento->detalles()->create([

                    'id_lote' => $idLote,

                    'cantidad' => $cantidad,
                ]);
            }


            return $movimiento->load([
                'origen',
                'destino',
                'detalles.lote.presentacion.producto'
            ]);

        }, 3);


        return response()->json([

            'message' => 'Movimiento registrado correctamente.',

            'movimiento' => $movimiento

        ], 201);
    }


    // FUNCIÓN INTERNA PARA MODIFICAR STOCK

    private function modificarStock($idUbicacion, $idLote, $variacion)
    {
        $inventario = Inventario::where(
            'id_ubicacion',
            $idUbicacion
        )
        ->where(
            'id_lote',
            $idLote
        )
        ->lockForUpdate()
        ->first();


        // SI NO EXISTE INVENTARIO, SOLO PODEMOS CREARLO
        // CUANDO LA OPERACIÓN INCREMENTA EL STOCK.

        if (!$inventario) {

            if ($variacion < 0) {

                throw ValidationException::withMessages([
                    'stock' => "No existe stock disponible del lote {$idLote} en la ubicación {$idUbicacion}."
                ]);
            }

            $inventario = Inventario::create([

                'id_ubicacion' => $idUbicacion,

                'id_lote' => $idLote,

                'stock_actual' => 0,

                'stock_minimo' => 0,
            ]);
        }


        $nuevoStock = $inventario->stock_actual + $variacion;


        if ($nuevoStock < 0) {

            throw ValidationException::withMessages([

                'stock' =>
                    "Stock insuficiente del lote {$idLote}. Disponible: {$inventario->stock_actual}. Variación solicitada: {$variacion}."

            ]);
        }


        $inventario->stock_actual = $nuevoStock;

        $inventario->save();
    }
}
