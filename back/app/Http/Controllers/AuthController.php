<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AccesoPorCargo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
            'captcha_token' => ['nullable', 'string', 'max:4096'],
        ]);

        // Limitar intentos por correo e IP sin revelar existencia de usuarios.
        $clave = 'excernat-login:' . sha1(Str::lower($datos['email']) . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            throw ValidationException::withMessages([
                'email' => ['Demasiados intentos. Inténtelo nuevamente en unos minutos.'],
            ]);
        }
        RateLimiter::hit($clave, 120);

        // CAPTCHA: el servidor verifica cada token ante el proveedor.
        if (config('services.turnstile.enabled')) {
            $secret = config('services.turnstile.secret_key');
            if (!is_string($secret) || $secret === '') {
                abort(503, 'Verificación de seguridad no configurada.');
            }
            $token = $datos['captcha_token'] ?? '';
            if ($token === '') {
                throw ValidationException::withMessages([
                    'captcha_token' => ['Complete la verificación de seguridad.'],
                ]);
            }
            try {
                $respuesta = Http::asForm()->timeout(8)->post(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => $secret, 'response' => $token, 'remoteip' => $request->ip()]
                );
                $verificado = $respuesta->successful() && $respuesta->json('success') === true;
                $hostname = $respuesta->json('hostname');
                $permitido = config('services.turnstile.hostname');
                if ($permitido && $hostname !== $permitido) $verificado = false;
            } catch (\Throwable $e) {
                abort(503, 'No se pudo verificar la seguridad. Inténtelo nuevamente.');
            }
            if (!$verificado) {
                throw ValidationException::withMessages([
                    'captcha_token' => ['Verificación de seguridad inválida o vencida.'],
                ]);
            }
        }

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

        if (!$usuario->hasRole('ADMIN')) {
            $acceso = app(AccesoPorCargo::class);
            $acceso->sincronizar($usuario, $acceso->nivel($usuario));
        }

        RateLimiter::clear($clave);

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

    public function captchaConfig()
    {
        return response()->json([
            'enabled' => (bool) config('services.turnstile.enabled'),
            'site_key' => config('services.turnstile.enabled') ? config('services.turnstile.site_key') : null,
        ]);
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
