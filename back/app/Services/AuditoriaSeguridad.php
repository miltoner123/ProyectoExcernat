<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class AuditoriaSeguridad {
    /** Únicamente datos de seguridad explícitamente permitidos, nunca claves o tokens. */
    public function registrar(string $accion, string $entidad, int|string|null $id,
        ?int $afectado, array $anterior, array $nuevo, ?int $actor = null): void {
        DB::table('auditoria_seguridad')->insert([
            'actor_id' => $actor ?? auth()->id(),
            'usuario_afectado_id' => $afectado,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $id === null ? null : (string) $id,
            'anterior' => json_encode($anterior, JSON_UNESCAPED_UNICODE),
            'nuevo' => json_encode($nuevo, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
