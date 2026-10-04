<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Lote;

class PedidoController extends Controller
{
    // LISTADO

    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        if (!in_array($limit, [10, 25, 50])) {
            $limit = 10;
        }

        $query = Pedido::with([
            'detalles.lote.presentacion.producto'
        ]);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {

            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {

                $q->where('referencia', 'ilike', "%{$buscar}%")

                  ->orWhereHas('detalles.lote', function ($sub) use ($buscar) {
                      $sub->where('codigo_lote', 'ilike', "%{$buscar}%");
                  });
            });
        }

        return response()->json(
            $query->orderBy('id_pedido', 'desc')->paginate($limit)
        );
    }


    // CONSULTAR PEDIDO

    public function show($id)
    {
        $pedido = Pedido::with([
            'detalles.lote.presentacion.producto'
        ])->find($id);

        if (!$pedido) {
            return response()->json([
                'message' => 'Pedido no encontrado.'
            ], 404);
        }

        return response()->json($pedido);
    }


    // REGISTRAR PEDIDO

    public function store(Request $request)
    {
        $datos = $request->validate([

            'tipo' =>
                'required|in:COMPRA,VENTA',

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

            'detalles.*.precio_unitario' =>
                'nullable|numeric|min:0',
        ]);


        // VALIDAR LOTES ACTIVOS

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

        $pedido = DB::transaction(function () use ($datos) {

            $pedido = Pedido::create([

                'tipo' => $datos['tipo'],

                'estado' => 'PENDIENTE',

                'fecha_pedido' => now(),

                'referencia' => $datos['referencia'] ?? null,

                'observacion' => $datos['observacion'] ?? null,
            ]);


            foreach ($datos['detalles'] as $detalle) {

                $pedido->detalles()->create([

                    'id_lote' => $detalle['id_lote'],

                    'cantidad' => $detalle['cantidad'],

                    'precio_unitario' => $detalle['precio_unitario'] ?? 0,
                ]);
            }


            return $pedido->load([
                'detalles.lote.presentacion.producto'
            ]);

        }, 3);


        return response()->json([

            'message' => 'Pedido registrado correctamente.',

            'pedido' => $pedido

        ], 201);
    }


    // ACTUALIZAR ESTADO DEL PEDIDO

    public function updateEstado(Request $request, $id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json([
                'message' => 'Pedido no encontrado.'
            ], 404);
        }

        $datos = $request->validate([
            'estado' =>
                'required|in:PENDIENTE,COMPLETADO,CANCELADO',
        ]);


        // NO PERMITIR CAMBIOS SI YA ESTÁ CANCELADO

        if ($pedido->estado === 'CANCELADO') {

            throw ValidationException::withMessages([
                'estado' => 'No se puede modificar un pedido cancelado.'
            ]);
        }


        // SI SE COMPLETA, VERIFICAR QUE TENGA DETALLES

        if ($datos['estado'] === 'COMPLETADO' && $pedido->detalles()->count() === 0) {

            throw ValidationException::withMessages([
                'estado' => 'No se puede completar un pedido sin detalles.'
            ]);
        }


        $pedido->estado = $datos['estado'];
        $pedido->save();


        return response()->json([

            'message' => 'Estado del pedido actualizado correctamente.',

            'pedido' => $pedido->load([
                'detalles.lote.presentacion.producto'
            ])
        ]);
    }


    // CANCELAR PEDIDO

    public function destroy($id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json([
                'message' => 'Pedido no encontrado.'
            ], 404);
        }

        if ($pedido->estado === 'CANCELADO') {
            return response()->json([
                'message' => 'El pedido ya está cancelado.'
            ], 422);
        }

        if ($pedido->estado === 'COMPLETADO') {
            return response()->json([
                'message' => 'No se puede cancelar un pedido completado.'
            ], 422);
        }

        $pedido->estado = 'CANCELADO';
        $pedido->save();

        return response()->json([
            'message' => 'Pedido cancelado correctamente.'
        ]);
    }
}
