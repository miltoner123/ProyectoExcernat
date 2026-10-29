<?php

namespace App\Http\Controllers;


use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PresentacionController extends Controller
{
    // LISTAR PRESENTACIONES
    public function index(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 100);

        return response()->json(
            Presentacion::with('producto')
                ->orderByDesc('id_presentacion')
                ->paginate($limit)
        );
    }

    // MOSTRAR PRESENTACIÓN
    public function show($id)
    {
        $presentacion = Presentacion::with('producto')->find($id);

        if (!$presentacion) {
            return response()->json([
                'message' => 'Presentación no encontrada.'
            ], 404);
        }

        return response()->json($presentacion);
    }

    // VALIDACIONES PARA CREAR Y EDITAR
    private function validar(Request $request, $id = null)
    {
        return $request->validate([
            'id_producto' => ['required', 'integer', 'exists:productos,id'],

            'codigo_sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('presentaciones', 'codigo_sku')
                    ->ignore($id, 'id_presentacion')
            ],

            'nombre_presentacion' => 'required|string|max:255',
            'peso_neto' => 'required|numeric|gt:0',
            'unidad_medida' => 'required|in:g,kg',
            'tipo_envase' => 'required|string|max:100',
            'unidades_por_paquete' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0',
            'precio_paquete' => 'nullable|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);
    }

    // CREAR PRESENTACIÓN
    public function store(Request $request)
    {
        $datos = $this->validar($request);
        // El SKU se genera en el servidor; nunca se confía en un código enviado por el cliente.
        $producto = Producto::findOrFail($datos['id_producto']);
        $abreviar = static function (string $texto, int $maximo = 5): string {
            $palabras = preg_split('/[^A-Z0-9]+/', Str::upper(Str::ascii(trim($texto))), -1, PREG_SPLIT_NO_EMPTY);
            $palabras = $palabras ?: [];
            if (count($palabras) === 1) {
                return substr($palabras[0], 0, min($maximo, 2));
            }
            return substr(implode('', array_map(static fn ($p) => substr($p, 0, 1), $palabras)), 0, $maximo);
        };
        $prefijo = $abreviar($producto->nombre, 2) . '-' . $abreviar($datos['nombre_presentacion'], 5);

        if (!$producto->estado) {
            return response()->json([
                'message' => 'No se puede crear una presentación activa para un producto inactivo.'
            ], 422);
        }

        // Guardar la fotografía si se seleccionó.
        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $request->file('imagen')
                ->store('presentaciones', 'public');
        }

        $datos['estado'] = true;
        try {
            // Bloqueo transaccional por prefijo: evita correlativos duplicados en PostgreSQL.
            $presentacion = DB::transaction(function () use ($datos, $prefijo) {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', ['SKU:' . $prefijo]);
                $existentes = Presentacion::where('codigo_sku', 'like', $prefijo . '-%')
                    ->pluck('codigo_sku');
                $mayor = 0;
                foreach ($existentes as $codigo) {
                    if (preg_match('/^' . preg_quote($prefijo, '/') . '-(\d+)$/', $codigo, $m)) {
                        $mayor = max($mayor, (int) $m[1]);
                    }
                }
                $datos['codigo_sku'] = $prefijo . '-' . str_pad((string) ($mayor + 1), 2, '0', STR_PAD_LEFT);
                return Presentacion::create($datos);
            });
        } catch (\Throwable $e) {
            if (!empty($datos['imagen'])) {
                Storage::disk('public')->delete($datos['imagen']);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Presentación registrada correctamente.',
            'presentacion' => $presentacion
        ], 201);
    }

    // ACTUALIZAR PRESENTACIÓN
    public function update(Request $request, $id)
    {
        $presentacion = Presentacion::find($id);

        if (!$presentacion) {
            return response()->json([
                'message' => 'Presentación no encontrada.'
            ], 404);
        }

        $datos = $this->validar($request, $id);
        // El SKU es un identificador permanente; la edición no puede alterarlo.
        unset($datos['codigo_sku']);

        // Evitar que una presentación activa quede asociada
        // a un producto inactivo.
        $producto = Producto::find($datos['id_producto']);

        if ($presentacion->estado && !$producto->estado) {
            return response()->json([
                'message' => 'No se puede asociar una presentación activa a un producto inactivo.'
            ], 422);
        }

        $imagenAnterior = $presentacion->imagen;
        $nuevaImagen = null;

        if ($request->hasFile('imagen')) {
            $nuevaImagen = $request->file('imagen')
                ->store('presentaciones', 'public');

            $datos['imagen'] = $nuevaImagen;
        }

        try {
            // No modificamos estado: tiene su propio endpoint.
            $presentacion->update($datos);
        } catch (\Throwable $e) {
            if ($nuevaImagen) {
                Storage::disk('public')->delete($nuevaImagen);
            }

            throw $e;
        }

        // Borrar la fotografía antigua solo si fue reemplazada.
        if ($nuevaImagen && $imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json([
            'message' => 'Presentación actualizada correctamente.',
            'presentacion' => $presentacion
        ]);
    }

    // ACTIVAR O INACTIVAR
    public function cambiarEstado(Request $request, $id)
    {
        $datos = $request->validate([
            'estado' => 'required|boolean'
        ]);

        $presentacion = Presentacion::with('producto')->find($id);

        if (!$presentacion) {
            return response()->json([
                'message' => 'Presentación no encontrada.'
            ], 404);
        }

        if (
            $datos['estado'] &&
            (!$presentacion->producto || !$presentacion->producto->estado)
        ) {
            return response()->json([
                'message' => 'No se puede activar una presentación si su producto está inactivo.'
            ], 422);
        }

        $presentacion->estado = $datos['estado'];
        $presentacion->save();

        return response()->json([
            'message' => $presentacion->estado
                ? 'Presentación activada correctamente.'
                : 'Presentación inactivada correctamente.',
            'presentacion' => $presentacion
        ]);
    }

    // ELIMINACIÓN LÓGICA
    public function destroy($id)
    {
        $presentacion = Presentacion::find($id);

        if (!$presentacion) {
            return response()->json([
                'message' => 'Presentación no encontrada.'
            ], 404);
        }

        if ($presentacion->lotes()->exists()) {
            return response()->json([
                'message' => 'La presentación tiene lotes asociados. Puede inactivarla para conservar el historial.'
            ], 422);
        }

        // Soft Delete: conservar la imagen para una restauración.
        $presentacion->delete();

        return response()->json([
            'message' => 'Presentación enviada a la papelera correctamente.'
        ]);
    }

    // LISTAR PAPELERA
    public function papelera(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 100);

        return response()->json(
            Presentacion::onlyTrashed()
                ->with('producto')
                ->orderByDesc('deleted_at')
                ->paginate($limit)
        );
    }

    // RESTAURAR PRESENTACIÓN
    public function restaurar($id)
    {
        $presentacion = Presentacion::onlyTrashed()->find($id);

        if (!$presentacion) {
            return response()->json([
                'message' => 'Presentación eliminada no encontrada.'
            ], 404);
        }

        if (!Producto::whereKey($presentacion->id_producto)->exists()) {
            return response()->json([
                'message' => 'Primero debe restaurar el producto asociado.'
            ], 422);
        }

        $presentacion->restore();

        return response()->json([
            'message' => 'Presentación restaurada correctamente.'
        ]);
    }
}
