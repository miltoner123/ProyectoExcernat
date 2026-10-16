<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($datos)) {
            throw ValidationException::withMessages([
                'email' => 'Correo o contraseña incorrectos.',
            ]);
        }

        $usuario = $request->user();

        if (!$usuario->estado) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Esta cuenta se encuentra deshabilitada.',
            ]);
        }

        $request->session()->regenerate();

        $usuario->update([
            'ultimo_acceso' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Inicio de sesión correcto.',
            'usuario' => $usuario->load('persona'),
        ]);
    }

    public function usuario(Request $request)
    {
        return response()->json(
            $request->user()->load('persona')
        );
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }
}