<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    public function index(Request $request)
    {
        $query = Evento::query();

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $eventos = $query
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        return response()->json($eventos);
    }
}