<?php
namespace App\Http\Middleware;
use App\Services\AccesoTemporalEvento;
use Closure;
use Illuminate\Http\Request;

class PermisoOEncargo
{
    public function handle(Request $request, Closure $next, string $permiso)
    {
        $u = $request->user();
        if (!$u || !$u->estado) abort(403);
        if ($u->hasRole('ADMIN') || $u->can($permiso)) return $next($request);
        if (!in_array($permiso, ['eventos.ver', 'ventas.ver', 'ventas.registrar', 'transferencias.recibir'], true)) abort(403);
        if (!app(AccesoTemporalEvento::class)->asignado($u)) abort(403, 'No existe asignacion operativa vigente.');
        // Los controladores verifican adicionalmente la ubicacion solicitada.
        return $next($request);
    }
}
