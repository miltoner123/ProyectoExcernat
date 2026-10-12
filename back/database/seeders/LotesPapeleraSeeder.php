<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lote;
use App\Models\Presentacion;
use Carbon\Carbon;

class LotesPapeleraSeeder extends Seeder
{
    public function run(): void
    {
        $presentacion = Presentacion::first();

        if (!$presentacion) {
            $this->command->error('No existen presentaciones. Ejecuta primero el seeder principal.');
            return;
        }

        $datos = [
            [
                'codigo_lote' => 'TEST-PAP-001',
                'meses' => 8,
                'dias_atras' => 300,
                'eliminado' => true
            ],
            [
                'codigo_lote' => 'TEST-PAP-002',
                'meses' => 12,
                'dias_atras' => 150,
                'eliminado' => true
            ],
            [
                'codigo_lote' => 'TEST-PAP-003',
                'meses' => 18,
                'dias_atras' => 60,
                'eliminado' => true
            ],
            [
                'codigo_lote' => 'TEST-ACT-001',
                'meses' => 8,
                'dias_atras' => 240,
                'eliminado' => false
            ],
            [
                'codigo_lote' => 'TEST-ACT-002',
                'meses' => 12,
                'dias_atras' => 60,
                'eliminado' => false
            ],
            [
                'codigo_lote' => 'TEST-ACT-003',
                'meses' => 18,
                'dias_atras' => 30,
                'eliminado' => false
            ]
        ];

        foreach ($datos as $item) {

            $fechaProduccion = Carbon::today()->subDays($item['dias_atras']);

            $fechaVencimiento = $fechaProduccion->copy()
                ->addMonthsNoOverflow($item['meses']);

            $lote = Lote::withTrashed()->updateOrCreate(
                [
                    'codigo_lote' => $item['codigo_lote']
                ],
                [
                    'id_presentacion' => $presentacion->id_presentacion,
                    'vida_util_meses' => $item['meses'],
                    'fecha_produccion' => $fechaProduccion->toDateString(),
                    'fecha_vencimiento' => $fechaVencimiento->toDateString(),
                    'cantidad_producida' => 100,
                    'estado' => true,
                    'observacion' => 'Registro generado para probar la papelera de lotes.'
                ]
            );

            if ($item['eliminado']) {

                if (!$lote->trashed()) {
                    $lote->delete();
                }

            } else {

                if ($lote->trashed()) {
                    $lote->restore();
                }
            }
        }

        $this->command->info('Se crearon 6 lotes de prueba: 3 en papelera y 3 activos.');
    }
}
