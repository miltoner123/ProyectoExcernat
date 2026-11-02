<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AuditoriaSeguridadController extends Controller {
    public function index(Request $request) {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);
        $filters = $request->validate([
            'accion' => ['nullable','string','max:60'],
            'usuario_afectado_id' => ['nullable','integer'],
            'desde' => ['nullable','date'],
            'hasta' => ['nullable','date','after_or_equal:desde'],
            'limit' => ['nullable','integer','min:1','max:100'],
        ]);
        $q = DB::table('auditoria_seguridad as a')
            ->leftJoin('users as responsable', 'responsable.id', '=', 'a.actor_id')
            ->leftJoin('users as afectado', 'afectado.id', '=', 'a.usuario_afectado_id')
            ->select('a.*','responsable.name as actor_nombre','afectado.name as afectado_nombre')
            ->when($filters['accion'] ?? null, fn ($q,$v) => $q->where('a.accion',$v))
            ->when($filters['usuario_afectado_id'] ?? null, fn ($q,$v) => $q->where('a.usuario_afectado_id',$v))
            ->when($filters['desde'] ?? null, fn ($q,$v) => $q->whereDate('a.created_at','>=',$v))
            ->when($filters['hasta'] ?? null, fn ($q,$v) => $q->whereDate('a.created_at','<=',$v))
            ->orderByDesc('a.id');
        return response()->json($q->paginate($filters['limit'] ?? 20));
    }
}
