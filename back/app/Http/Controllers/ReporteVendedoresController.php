<?php
namespace App\Http\Controllers;

use App\Models\Venta;
use App\Support\ReportePdfSimple;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Informe derivado de ventas registradas, sin alterar movimientos ni ventas históricas. */
class ReporteVendedoresController extends Controller
{
    private function filtros(Request $request): array
    {
        abort_unless($request->user()->hasRole('ADMIN') || $request->user()->can('contabilidad.ver') || $request->user()->can('eventos.gestionar'), 403);
        $data = $request->validate([
            'desde'=>'nullable|date', 'hasta'=>'nullable|date|after_or_equal:desde',
            'id_evento'=>'nullable|integer|exists:eventos,id_evento',
            'id_ubicacion'=>'nullable|integer|exists:ubicaciones,id_ubicacion',
            'id_asignacion_vendedor'=>'nullable|integer|exists:asignaciones_personal,id_asignacion',
        ]);
        if (!empty($data['id_evento']) && !empty($data['id_ubicacion'])) {
            $correcto = DB::table('ubicaciones')->where('id_ubicacion',$data['id_ubicacion'])
                ->where('id_evento',$data['id_evento'])->exists();
            if (!$correcto) throw ValidationException::withMessages(['id_ubicacion'=>'El stand seleccionado no pertenece al evento.']);
        }
        return $data;
    }

    private function centavos($valor): int { return (int) round((float)$valor * 100); }
    private function bs(int $valor): string { return number_format($valor/100,2,'.',''); }

    private function datos(array $f): array
    {
        $q = Venta::query()->with([
            'ubicacion.evento', 'asignacionVendedor.personal.persona',
            'detalles.lote.presentacion.producto.categoria',
        ])->where('estado','CONFIRMADA')->whereNotNull('id_asignacion_vendedor');
        if (!empty($f['desde'])) $q->whereDate('fecha_venta','>=',$f['desde']);
        if (!empty($f['hasta'])) $q->whereDate('fecha_venta','<=',$f['hasta']);
        if (!empty($f['id_ubicacion'])) $q->where('id_ubicacion',$f['id_ubicacion']);
        if (!empty($f['id_asignacion_vendedor'])) $q->where('id_asignacion_vendedor',$f['id_asignacion_vendedor']);
        if (!empty($f['id_evento'])) $q->whereHas('ubicacion',fn($b)=>$b->where('id_evento',$f['id_evento']));
        // Un límite explícito evita publicar totales incompletos o consumir memoria sin control.
        if ((clone $q)->select('id_venta')->limit(10001)->get()->count() > 10000) {
            throw ValidationException::withMessages(['desde'=>'Demasiadas ventas para este reporte. Reduzca el intervalo.']);
        }
        $grupos=[]; $alertas=[];
        foreach ($q->orderBy('id_venta')->get() as $venta) {
            $asig=$venta->asignacionVendedor;
            if (!$asig || !$asig->personal || !$asig->personal->persona) {
                $alertas['Asignaciones históricas sin relación completa; algunas ventas no se pudieron atribuir.']=true;
                continue;
            }
            $ubic=$venta->ubicacion;
            $id=(int)$asig->id_asignacion;
            $persona=$asig->personal->persona;
            $nombre=trim(($persona->nombres_razon_social??'').' '.($persona->apellidos??''));
            $tipo=strtoupper((string)$asig->tipo_remuneracion);
            if (!isset($grupos[$id])) {
                $grupos[$id]=[
                    'id_asignacion_vendedor'=>$id,
                    'vendedor'=>$nombre ?: 'Sin nombre',
                    'tipo_remuneracion'=>$tipo,
                    'monto_fijo'=>$asig->monto_fijo,
                    'tarifa_polvo'=>$asig->comision_polvo,
                    'tarifa_granola'=>$asig->comision_granola,
                    'ventas'=>0, 'unidades'=>0, 'bruto_cents'=>0, 'descuento_cents'=>0,
                    'neto_cents'=>0,'comision_cents'=>0,
                    'detalle'=>[], 'eventos'=>[],
                ];
            }
            $g=&$grupos[$id]; $g['ventas']++;
            $detalle=$venta->detalles->sortBy('id_detalle_venta')->values();
            $brutos=$detalle->map(fn($d)=>$this->centavos($d->subtotal));
            $bruto=$brutos->sum();
            $neto=$this->centavos($venta->total);
            $desc=max(0,$bruto-$neto);
            if ($neto < 0 || $neto > $bruto || $bruto === 0) {
                $alertas['Se detectaron ventas con subtotales inconsistentes; revise sus importes.']=true;
                continue;
            }
            $g['bruto_cents']+=$bruto;
            $g['descuento_cents']+=$desc;
            $g['neto_cents']+=$neto;
            $resto=$desc;
            foreach ($detalle as $idx=>$d) {
                $lineaBruta=$brutos[$idx];
                // Última línea absorbe centavos residuales para que bruto-descuento=neto.
                $asignado=$idx===$detalle->count()-1?$resto:intdiv($desc*$lineaBruta,$bruto);
                $resto-=$asignado;
                $pres=$d->lote?->presentacion;
                $producto=$pres?->producto;
                $categoria=$producto?->categoria?->nombre;
                $familia=match ($categoria) {
                    'Granolas'=>'GRANOLA', 'Multi-cereales','Monocereales'=>'POLVO',
                    default=>'NO_CLASIFICADO',
                };
                $unidades=(int)$d->cantidad;
                $tarifa=$familia==='GRANOLA'?$asig->comision_granola:($familia==='POLVO'?$asig->comision_polvo:null);
                $comision=in_array($tipo,['COMISION','MIXTO'],true) && $tarifa!==null
                    ? $this->centavos($tarifa)*$unidades : 0;
                if ($familia==='NO_CLASIFICADO' && in_array($tipo,['COMISION','MIXTO'],true)) {
                    $alertas['Hay productos sin categoría identificable para comisiones; sus comisiones no se han calculado.']=true;
                }
                if ($tarifa===null && $familia!=='NO_CLASIFICADO' && in_array($tipo,['COMISION','MIXTO'],true)) {
                    $alertas['Algunas asignaciones no tienen tarifa configurada para la categoría vendida.']=true;
                }
                $g['unidades']+=$unidades; $g['comision_cents']+=$comision;
                $g['detalle'][]=[
                    'id_venta'=>$venta->id_venta,'fecha'=>$venta->fecha_venta?->format('Y-m-d'),
                    'evento'=>$ubic?->evento?->nombre ?? 'Sin evento',
                    'stand'=>$ubic?->nombre ?? 'Sin ubicación',
                    'producto'=>$producto?->nombre??'Sin producto',
                    'presentacion'=>$pres?->nombre_presentacion??'',
                    'categoria'=>$categoria??'Sin categoría','familia_comision'=>$familia,
                    'cantidad'=>$unidades,'precio_unitario'=>$d->precio_unitario,
                    'bruto'=>$this->bs($lineaBruta),'descuento'=>$this->bs($asignado),
                    'neto'=>$this->bs($lineaBruta-$asignado),'comision'=>$this->bs($comision),
                ];
                $g['eventos'][$ubic?->evento?->nombre??'Sin evento']=true;
            }
            unset($g);
        }
        $filas=[]; $totales=['ventas'=>0,'unidades'=>0,'bruto'=>0,'descuento'=>0,'neto'=>0,'comision'=>0];
        foreach ($grupos as $g) {
            // El monto fijo corresponde a UNA asignación, no a cada venta. Los filtros parciales
            // no permiten inferir prorrateos contractuales: se presenta separado e informativo.
            $fijo = in_array($g['tipo_remuneracion'],['MONTO_FIJO','MIXTO'],true)
                ? $this->centavos($g['monto_fijo']??0) : 0;
            if ($fijo && (!empty($f['desde']) || !empty($f['hasta']))) {
                $alertas['Monto fijo mostrado por asignación completa; no se prorratea por intervalos parciales.']=true;
            }
            if (in_array($g['tipo_remuneracion'],['COMISION','MIXTO'],true)) {
                $alertas['Las tarifas provienen de la asignación actual; si cambiaron después de las ventas, la comisión histórica es referencial.']=true;
            }
            $filas[]=[
                'id_asignacion_vendedor'=>$g['id_asignacion_vendedor'], 'vendedor'=>$g['vendedor'],
                'tipo_remuneracion'=>$g['tipo_remuneracion'], 'ventas'=>$g['ventas'],
                'unidades'=>$g['unidades'],'bruto'=>$this->bs($g['bruto_cents']),
                'descuento'=>$this->bs($g['descuento_cents']),'neto'=>$this->bs($g['neto_cents']),
                'comision'=>$this->bs($g['comision_cents']),'monto_fijo_referencial'=>$this->bs($fijo),
                'remuneracion_referencial'=>$this->bs($fijo+$g['comision_cents']),
                'eventos'=>array_keys($g['eventos']),'detalle'=>$g['detalle'],
            ];
            foreach (['ventas','unidades'] as $k) $totales[$k]+=$g[$k];
            foreach (['bruto','descuento','neto','comision'] as $k) $totales[$k]+=$g[$k.'_cents'];
        }
        foreach (['bruto','descuento','neto','comision'] as $k) $totales[$k]=$this->bs($totales[$k]);
        usort($filas,fn($a,$b)=>strcmp($a['vendedor'],$b['vendedor']));
        return ['filtros'=>$f,'totales'=>$totales,'vendedores'=>$filas,'advertencias'=>array_keys($alertas)];
    }

    public function index(Request $request) { return response()->json($this->datos($this->filtros($request))); }

    public function pdf(Request $request)
    {
        $datos=$this->datos($this->filtros($request));
        $pdf=new ReportePdfSimple();
        $pdf->titulo('EXCERNAT - VENTAS POR VENDEDOR');
        $pdf->texto('Emitido por: '.$request->user()->name);
        $pdf->texto('Generado: '.now()->format('d/m/Y H:i'));
        $pdf->texto('Periodo: '.($request->query('desde','Inicio')).' - '.($request->query('hasta','Hoy')));
        $pdf->texto('Evento: '.($request->query('id_evento')?:'Todos').' | Stand: '.($request->query('id_ubicacion')?:'Todos'));
        $pdf->separador();
        $pdf->texto('TOTAL NETO Bs '.$datos['totales']['neto'].' | COMISION Bs '.$datos['totales']['comision'],11,true);
        foreach ($datos['vendedores'] as $v) {
            $pdf->separador();
            $pdf->texto($v['vendedor'].' | Asignacion #'.$v['id_asignacion_vendedor'],11,true);
            $pdf->texto('Ventas: '.$v['ventas'].' | Unidades: '.$v['unidades'].' | Bruto Bs '.$v['bruto']);
            $pdf->texto('Descuento Bs '.$v['descuento'].' | Neto Bs '.$v['neto'].' | Comision Bs '.$v['comision']);
            foreach ($v['detalle'] as $linea) {
                $pdf->textoPartido($linea['fecha'].' | '.$linea['evento'].' / '.$linea['stand'].' | '.$linea['producto'].' '.$linea['presentacion']);
                $pdf->texto('  '.$linea['cantidad'].' x Bs '.$linea['precio_unitario'].' | Neto Bs '.$linea['neto'].' | Comision Bs '.$linea['comision']);
            }
        }
        foreach ($datos['advertencias'] as $aviso) { $pdf->separador(); $pdf->textoPartido('NOTA: '.$aviso); }
        return response($pdf->generar(),200,[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'attachment; filename="EXCERNAT_vendedores.pdf"',
            'Cache-Control'=>'private, no-store',
        ]);
    }
}
