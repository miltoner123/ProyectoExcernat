<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 10), 1), 100);

        $query = User::with(['persona', 'roles'])
            ->orderByDesc('id');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'ilike', "%{$buscar}%")
                  ->orWhere('email', 'ilike', "%{$buscar}%")
                  ->orWhereHas('persona', function ($p) use ($buscar) {
                      $p->where('nombres_razon_social', 'ilike', "%{$buscar}%")
                        ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                        ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
                  });
            });
        }

        return response()->json($query->paginate($limit));
    }

    public function roles(Request $request)
    {
        $query = Role::where('guard_name', 'web')
            ->whereIn('name', ['ADMIN', 'ENCARGADO', 'CONSULTA'])
            ->orderBy('name');

        return response()->json(
            $query->get(['id', 'name'])
        );
    }

    public function personasDisponibles(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $personas = Persona::query()
            ->where('tipo_persona', 'PERSONAL')
            ->where('estado', true)
            ->whereDoesntHave('usuario')
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($p) use ($buscar) {
                    $p->where('nombres_razon_social', 'ilike', "%{$buscar}%")
                      ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                      ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
                });
            })
            ->orderBy('nombres_razon_social')
            ->limit(50)
            ->get([
                'id_persona',
                'nombres_razon_social',
                'apellidos',
                'numero_documento',
            ]);

        return response()->json($personas);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_persona' => [
                'required',
                'integer',
                Rule::exists('personas', 'id_persona'),
                Rule::unique('users', 'id_persona'),
            ],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'rol' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ]);

        $this->validarRol($request, $datos['rol']);

        return DB::transaction(function () use ($datos) {
            $persona = Persona::whereKey($datos['id_persona'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($persona->tipo_persona !== 'PERSONAL' || !$persona->estado) {
                throw ValidationException::withMessages([
                    'id_persona' => 'Seleccione una persona de personal activa.',
                ]);
            }

            $usuario = User::create([
                'id_persona' => $persona->id_persona,
                'name' => trim(
                    $persona->nombres_razon_social . ' ' .
                    ($persona->apellidos ?? '')
                ),
                'email' => $datos['email'],
                'password' => $datos['password'],
                'estado' => true,
            ]);

            $usuario->assignRole($datos['rol']);

            return response()->json([
                'message' => 'Usuario registrado correctamente.',
                'usuario' => $usuario->load(['persona', 'roles']),
            ], 201);
        });
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'rol' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            'estado' => ['required', 'boolean'],
        ]);

        $this->validarRol($request, $datos['rol']);

        if ($usuario->id === $request->user()->id) {
            if (!$datos['estado']) {
                throw ValidationException::withMessages([
                    'estado' => 'No puede desactivar su propia cuenta.',
                ]);
            }

            if ($usuario->hasRole('ADMIN') && $datos['rol'] !== 'ADMIN') {
                throw ValidationException::withMessages([
                    'rol' => 'No puede quitarse su propio rol ADMIN.',
                ]);
            }
        }

        if (
            $usuario->hasRole('ADMIN') &&
            !$request->user()->hasRole('ADMIN')
        ) {
            abort(403, 'No puede modificar una cuenta ADMIN.');
        }

        return DB::transaction(function () use ($usuario, $datos) {
            $cambios = [
                'email' => $datos['email'],
                'estado' => $datos['estado'],
            ];

            if (!empty($datos['password'])) {
                $cambios['password'] = $datos['password'];
            }

            $usuario->update($cambios);
            $usuario->syncRoles([$datos['rol']]);

            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'usuario' => $usuario->load(['persona', 'roles']),
            ]);
        });
    }

    private function validarRol(Request $request, string $rol): void
    {
        if (!in_array($rol, ['ADMIN', 'ENCARGADO', 'CONSULTA'], true)) {
            throw ValidationException::withMessages(['rol' => 'Seleccione uno de los tres niveles vigentes.']);
        }

        if (
            $rol === 'ADMIN' &&
            !$request->user()->hasRole('ADMIN')
        ) {
            abort(403, 'Solo ADMIN puede asignar este rol.');
        }
    }
}
