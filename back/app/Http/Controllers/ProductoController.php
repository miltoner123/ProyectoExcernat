<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;

class ProductoController extends Controller
{
    function index()
    {
        $productos = Producto::with('categoriaProducto')->get();
        return $productos;
    }
}
