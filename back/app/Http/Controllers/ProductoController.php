<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;


class ProductoController extends Controller
{
  public function index(Request $request)
{
    $limit = min(max((int) $request->query('limit', 10), 1), 100);

    $productos = Producto::with([
        'categoria',
        'presentaciones'
    ])
    ->orderBy('id', 'desc')
    ->paginate($limit);

    return response()->json($productos);
}
    function store(Request $request)
    {
        $producto = new Producto();
        $producto->nombre = $request->nombre;
        $producto->descripcion = $request->descripcion;
        $producto->estado= 1;
        $producto->categoria_id = $request->categoria_id;
        $producto->save();
        return $producto;
    }
    public function destroy($id)
        {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json(['message' => 'Producto no encontrado'], 404);
            }

            if ($producto->presentaciones()->exists()) {
                return response()->json(['message' => 'El producto tiene presentaciones asociadas. Puede inactivarlo en lugar de eliminarlo.'], 422);
            }

            $producto->delete();

            return response()->json(['message' => 'Producto enviado a la papelera correctamente']);
        }

        public function papelera(Request $request)
        {
            $limit = min(max((int) $request->input('limit', 10), 1), 100);

            return response()->json(Producto::onlyTrashed()->with('categoria')->orderByDesc('deleted_at')->paginate($limit));
        }

        public function restaurar($id)
        {
            $producto = Producto::onlyTrashed()->find($id);

            if (!$producto) {
                return response()->json(['message' => 'Producto eliminado no encontrado'], 404);
            }

            if (!Categoria::whereKey($producto->categoria_id)->exists()) {
                return response()->json(['message' => 'Primero debe restaurar la categoría del producto'], 422);
            }

            $producto->restore();

            return response()->json(['message' => 'Producto restaurado correctamente']);
        }
        public function cambiarEstado(Request $request, $id)
    {
        $datos = $request->validate([
            'estado' => 'required|boolean'
        ]);

        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado.'
            ], 404);
        }

        $producto->estado = $datos['estado'];
        $producto->save();

        return response()->json([
            'message' => $producto->estado
                ? 'Producto activado correctamente.'
                : 'Producto inactivado correctamente.',
            'producto' => $producto
        ]);
    }
    function show($id)
    {
        $producto = Producto::with('categoria')->find($id);
        if ($producto) {
            return $producto;
        } else {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
    }
    function update(Request $request, $id)
    {
        $producto = Producto::find($id);
        if ($producto) {
            $producto->nombre = $request->nombre;
            $producto->descripcion = $request->descripcion;
            $producto->categoria_id = $request->categoria_id;
            $producto->save();
            return response()->json(['message' => 'Producto actualizado correctamente', 'producto' => $producto]);
        } else {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
    }
}
