<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;


class ProductoController extends Controller
{
    function index()
    {
        $productos = Producto::with('categoria')->get();
        return $productos;
    }
    function store(Request $request)
    {
        $imagen = $request->file('imagen');
        $ruta=$imagen->store('public/imagenes');
        $nombreArchivo = basename($ruta);
        $producto = new Producto();
        $producto->nombre = $request->nombre;
        $producto->descripcion = $request->descripcion;
        $producto->imagen = $nombreArchivo;
        $producto->activo = 1;
        $producto->categoria_id = $request->categoria_id;
        $producto->save();
        return $producto;
    }
}
