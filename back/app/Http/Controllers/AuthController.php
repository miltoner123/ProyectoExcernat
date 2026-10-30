<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión en EXCERNAT.
     */
    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Autenticar utilizando la sesión web de Laravel.
        if (!Auth::guard('web')->attempt([
            'email' => $datos['email'],
            'password' => $datos['password'],
        ])) {
            throw ValidationException::withMessages([
                'email' => ['Correo o contraseña incorrectos.'],
            ]);
        }

        $usuario = Auth::guard('web')->user();

        // Impedir acceso a cuentas desactivadas.
        if (!$usuario->estado) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => ['Esta cuenta se encuentra deshabilitada.'],
            ]);
        }

        // Renovar el identificador de sesión.
        $request->session()->regenerate();

        // Registrar la última fecha de acceso.
        $usuario->update([
            'ultimo_acceso' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Inicio de sesión correcto.',
            'usuario' => $usuario->load('persona', 'roles'),
        ], 200);
    }

    /**
     * Obtener el usuario autenticado.
     */
    public function usuario(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'mensaje' => 'No autenticado.',
            ], 401);
        }

        if (!$usuario->estado) {
            return response()->json([
                'mensaje' => 'La cuenta se encuentra deshabilitada.',
            ], 403);
        }

        return response()->json(
            array_merge($usuario->load('persona', 'roles')->toArray(), ['permisos' => $usuario->getAllPermissions()->pluck('name')->values()->all()]),
            200
        );
    }

    /**
     * Cerrar la sesión actual.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        // Invalidar sesión y renovar token CSRF.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente.',
        ], 200);
    }
}
