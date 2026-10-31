<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ExcernatReiniciarPruebas extends Command
{
    protected $signature = 'excernat:reiniciar-pruebas {--ejecutar : Ejecuta el borrado después de mostrar el plan}';
    protected $description = 'Previsualiza o reinicia datos operativos de desarrollo sin tocar catálogos, personas, personal, usuarios ni permisos.';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->error('Este comando solo admite PostgreSQL.');
            return self::FAILURE;
        }
        if (app()->environment('production')) {
            $this->error('Bloqueado en APP_ENV=production.');
            return self::FAILURE;
        }

        // Orden hijo -> padre. Se detiene si aparecen relaciones desconocidas.
        $tablas = [
            'cobros_ventas', 'detalle_ventas', 'ventas', 'cajas_punto_venta',
            'detalle_pedidos', 'pedidos', 'detalle_movimiento', 'movimientos_inventario',
            'inventarios', 'asistencias', 'asignaciones_personal',
            'lotes', 'ubicaciones', 'eventos',
        ];
        $presentes = array_values(array_filter($tablas, fn ($t) => Schema::hasTable($t)));
        $this->warn('ATENCIÓN: se eliminarán TODOS los registros operativos listados, incluidos lotes y existencias.');
        foreach ($presentes as $tabla) {
            $this->line(sprintf('%-28s %s', $tabla, DB::table($tabla)->count()));
        }
        $this->info('Se conservan productos, categorías, presentaciones, personas, personal, cargos, usuarios, roles y permisos.');
        $this->warn('Se conservan las ubicaciones permanentes; solo se eliminan stands vinculados a eventos.');
        if (!$this->option('ejecutar')) {
            $this->info('Vista previa únicamente. Para ejecutar, use --ejecutar tras respaldar PostgreSQL.');
            return self::SUCCESS;
        }
        if (!$this->confirm('¿Confirmas que YA respaldaste PostgreSQL y deseas eliminar los datos operativos?', false)) {
            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($presentes) {
                // PostgreSQL FK graph: prohíbe borrar si hay tablas dependientes fuera del plan.
                $dependencias = DB::select(<<<'SQL'
SELECT child.relname AS tabla_hija, parent.relname AS tabla_padre
FROM pg_constraint fk
JOIN pg_class child ON child.oid = fk.conrelid
JOIN pg_class parent ON parent.oid = fk.confrelid
JOIN pg_namespace ns ON ns.oid = child.relnamespace
WHERE fk.contype = 'f' AND ns.nspname = current_schema()
SQL);
                foreach ($dependencias as $fk) {
                    if (in_array($fk->tabla_padre, $presentes, true) &&
                        !in_array($fk->tabla_hija, $presentes, true)) {
                        // Solo se permiten tablas conservadas si no referencian registros a borrar.
                        $referencia = DB::selectOne(<<<'SQL'
SELECT 1 FROM pg_constraint fk
JOIN pg_class child ON child.oid = fk.conrelid
JOIN pg_class parent ON parent.oid = fk.confrelid
JOIN pg_namespace ns ON ns.oid = child.relnamespace
WHERE fk.contype='f' AND ns.nspname=current_schema()
AND child.relname=? AND parent.relname=? LIMIT 1
SQL, [$fk->tabla_hija, $fk->tabla_padre]);
                        if ($referencia) {
                            throw new RuntimeException("Dependencia externa no prevista: {$fk->tabla_hija} -> {$fk->tabla_padre}. Revisar antes de borrar.");
                        }
                    }
                }
                foreach ($presentes as $tabla) {
                    if ($tabla === 'ubicaciones') {
                        DB::table($tabla)->whereNotNull('id_evento')->delete();
                    } else {
                        DB::table($tabla)->delete();
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->error('Operación revertida automáticamente: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->info('Limpieza terminada. No se eliminaron catálogos ni usuarios.');
        return self::SUCCESS;
    }
}
