<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;

class CategoriaController extends Controller
{
    function index()
    {
        return Categoria::all();
    }
    function store(Request $request)
    {
        $categoria = new Categoria();
        $categoria->nombre = $request->nombre;
        $categoria->descripcion = $request->descripcion;
        $categoria->save();
        return $categoria;
    }
    public function destroy($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        if ($categoria->productos()->exists()) {
            return response()->json(['message' => 'La categoría tiene productos asociados. No puede enviarse a la papelera.'], 422);
        }

        $categoria->delete();

        return response()->json(['message' => 'Categoría enviada a la papelera correctamente']);
    }

    public function papelera(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 10), 1), 100);

        return response()->json(Categoria::onlyTrashed()->orderByDesc('deleted_at')->paginate($limit));
    }

    public function restaurar($id)
    {
        $categoria = Categoria::onlyTrashed()->find($id);

        if (!$categoria) {
            return response()->json(['message' => 'Categoría eliminada no encontrada'], 404);
        }

        $categoria->restore();

        return response()->json(['message' => 'Categoría restaurada correctamente']);
    }
        
}
