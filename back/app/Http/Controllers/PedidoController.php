<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Listar pedidos
     */
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = Pedido::with([
            'ubicacionSolicitante',
            'detalles.presentacion.producto'
        ]);

        // Buscar por observación
        if ($request->filled('buscar')) {
            $query->where(
                'observacion',
                'ilike',
                '%' . $request->buscar . '%'
            );
        }

        // Filtrar por estado
        if ($request->filled('estado')) {
            $query->where(
                'estado',
                $request->estado
            );
        }

        // Filtrar por tipo
        if ($request->filled('tipo')) {
            $query->where(
                'tipo',
                $request->tipo
            );
        }

        // Filtrar por ubicación solicitante
        if ($request->filled('id_ubicacion_solicitante')) {
            $query->where(
                'id_ubicacion_solicitante',
                $request->id_ubicacion_solicitante
            );
        }

        $pedidos = $query
            ->orderBy('fecha_pedido', 'desc')
            ->paginate($limit);

        return response()->json($pedidos);
    }


    /**
     * Registrar pedido
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_ubicacion_solicitante' =>
                'required|exists:ubicaciones,id_ubicacion',

            'tipo' =>
                'required|string|max:30',

            'fecha_pedido' =>
                'required|date',

            'observacion' =>
                'nullable|string',

            'detalles' =>
                'required|array|min:1',

            'detalles.*.id_presentacion' =>
                'required|exists:presentaciones,id_presentacion',

            'detalles.*.cantidad_paquetes' =>
                'required|integer|min:0',

            'detalles.*.cantidad_unidades' =>
                'required|integer|min:0',
        ]);

        try {

            DB::beginTransaction();

            /*
             * Crear cabecera del pedido
             */
            $pedido = Pedido::create([
                'id_ubicacion_solicitante' =>
                    $request->id_ubicacion_solicitante,

                'tipo' =>
                    $request->tipo,

                'estado' =>
                    'PENDIENTE',

                'fecha_pedido' =>
                    $request->fecha_pedido,

                'observacion' =>
                    $request->observacion,
            ]);


            /*
             * Registrar detalles
             */
            foreach ($request->detalles as $detalle) {

                // No permitir líneas con cantidad 0
                if (
                    $detalle['cantidad_paquetes'] == 0 &&
                    $detalle['cantidad_unidades'] == 0
                ) {

                    DB::rollBack();

                    return response()->json([
                        'message' =>
                            'Cada detalle debe tener al menos un paquete o una unidad.'
                    ], 422);
                }


                $pedido->detalles()->create([
                    'id_presentacion' =>
                        $detalle['id_presentacion'],

                    'cantidad_paquetes' =>
                        $detalle['cantidad_paquetes'],

                    'cantidad_unidades' =>
                        $detalle['cantidad_unidades'],
                ]);
            }


            DB::commit();


            return response()->json([
                'message' =>
                    'Pedido registrado correctamente.',

                'pedido' =>
                    $pedido->load([
                        'ubicacionSolicitante',
                        'detalles.presentacion.producto'
                    ])
            ], 201);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    'Error al registrar el pedido.',

                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }


    /**
     * Mostrar un pedido
     */
    public function show($id)
    {
        $pedido = Pedido::with([
            'ubicacionSolicitante',
            'detalles.presentacion.producto'
        ])->find($id);


        if (!$pedido) {

            return response()->json([
                'message' =>
                    'Pedido no encontrado.'
            ], 404);
        }


        return response()->json($pedido);
    }


    /**
     * Actualizar pedido
     */
    public function update(Request $request, $id)
    {
        $pedido = Pedido::find($id);


        if (!$pedido) {

            return response()->json([
                'message' =>
                    'Pedido no encontrado.'
            ], 404);
        }


        /*
         * Solo se pueden modificar
         * pedidos pendientes
         */
        if ($pedido->estado !== 'PENDIENTE') {

            return response()->json([
                'message' =>
                    'Solo se pueden modificar pedidos pendientes.'
            ], 422);
        }


        $request->validate([
            'id_ubicacion_solicitante' =>
                'required|exists:ubicaciones,id_ubicacion',

            'tipo' =>
                'required|string|max:30',

            'fecha_pedido' =>
                'required|date',

            'observacion' =>
                'nullable|string',

            'detalles' =>
                'required|array|min:1',

            'detalles.*.id_presentacion' =>
                'required|exists:presentaciones,id_presentacion',

            'detalles.*.cantidad_paquetes' =>
                'required|integer|min:0',

            'detalles.*.cantidad_unidades' =>
                'required|integer|min:0',
        ]);


        try {

            DB::beginTransaction();


            /*
             * Actualizar cabecera
             */
            $pedido->update([
                'id_ubicacion_solicitante' =>
                    $request->id_ubicacion_solicitante,

                'tipo' =>
                    $request->tipo,

                'fecha_pedido' =>
                    $request->fecha_pedido,

                'observacion' =>
                    $request->observacion,
            ]);


            /*
             * Eliminamos los detalles anteriores
             * y registramos los nuevos.
             *
             * Esto es válido porque el pedido
             * todavía está PENDIENTE.
             */
            $pedido->detalles()->delete();


            foreach ($request->detalles as $detalle) {

                if (
                    $detalle['cantidad_paquetes'] == 0 &&
                    $detalle['cantidad_unidades'] == 0
                ) {

                    DB::rollBack();

                    return response()->json([
                        'message' =>
                            'Cada detalle debe tener al menos un paquete o una unidad.'
                    ], 422);
                }


                $pedido->detalles()->create([
                    'id_presentacion' =>
                        $detalle['id_presentacion'],

                    'cantidad_paquetes' =>
                        $detalle['cantidad_paquetes'],

                    'cantidad_unidades' =>
                        $detalle['cantidad_unidades'],
                ]);
            }


            DB::commit();


            return response()->json([
                'message' =>
                    'Pedido actualizado correctamente.',

                'pedido' =>
                    $pedido->load([
                        'ubicacionSolicitante',
                        'detalles.presentacion.producto'
                    ])
            ]);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    'Error al actualizar el pedido.',

                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }


    /**
     * Eliminar pedido
     */
    public function destroy($id)
    {
        $pedido = Pedido::find($id);


        if (!$pedido) {

            return response()->json([
                'message' =>
                    'Pedido no encontrado.'
            ], 404);
        }


        /*
         * Solo permitimos eliminar
         * pedidos pendientes.
         */
        if ($pedido->estado !== 'PENDIENTE') {

            return response()->json([
                'message' =>
                    'Solo se pueden eliminar pedidos pendientes.'
            ], 422);
        }


        try {

            $pedido->delete();


            return response()->json([
                'message' =>
                    'Pedido eliminado correctamente.'
            ]);


        } catch (\Exception $e) {

            return response()->json([
                'message' =>
                    'No se pudo eliminar el pedido.',

                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }
}