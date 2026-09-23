<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;


class ProductoController extends Controller
{
    function index(Request $request)
    {
        $limit = $request->query('limit', 10); // Valor predeterminado de 10 si no se proporciona
        $productos = Producto::with('categoria')->paginate($limit);
        return response()->json($productos);
    }
    function store(Request $request)
    {
        $imagen = $request->file('imagen');
        $nombreArchivo = time() . '_' . $imagen->getClientOriginalName();
        $imagen->move(public_path('imagenes'), $nombreArchivo);
        $producto = new Producto();
        $producto->nombre = $request->nombre;
        $producto->descripcion = $request->descripcion;
        $producto->imagen = $nombreArchivo;
        $producto->activo = 1;
        $producto->categoria_id = $request->categoria_id;
        $producto->save();
        return $producto;
    }
    function destroy($id)
    {
        $producto = Producto::find($id);
        if ($producto) {
            $producto->delete();
            return response()->json(['message' => 'Producto eliminado correctamente']);
        } else {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
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
            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                $nombreArchivo = time() . '_' . $imagen->getClientOriginalName();
                $imagen->move(public_path('imagenes'), $nombreArchivo);
                $producto->imagen = $nombreArchivo;
            }
            $producto->activo = 1;
            $producto->categoria_id = $request->categoria_id;
            $producto->save();
            return response()->json(['message' => 'Producto actualizado correctamente', 'producto' => $producto]);
        } else {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
    }
}
