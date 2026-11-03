<?php
namespace App\Http\Controllers;
use App\Models\{Venta,Inventario,Ubicacion,AsignacionPersonal,Personal};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class VentaController extends Controller {
    private function autorizarStand(Request $request, int $id): void {
        $ubicacion=Ubicacion::findOrFail($id);
        if (!in_array(strtoupper($ubicacion->tipo), ['STAND','SUCURSAL','ALMACEN','ALMACEN_CENTRAL','CENTRAL']) || !$ubicacion->estado) {
            throw ValidationException::withMessages(['id_ubicacion'=>'Seleccione un punto de venta activo.']);
        }
        \App\Http\Controllers\CajaPuntoVentaController::validarEvento($ubicacion);
        if ($request->user()->hasRole('ADMIN')) return;
        $asignado = app(\App\Services\AccesoTemporalEvento::class)
            ->ubicaciones($request->user())->contains($id);
        if (!$asignado) abort(403,'No tiene asignación vigente a este punto de venta.');
    }
    private function autorizarConsulta(Request $request, int $id): void {
        if ($request->user()->hasRole('ADMIN') || $request->user()->can('contabilidad.ver') || $request->user()->can('ventas.supervisar')) return;
        if (!app(\App\Services\AccesoTemporalEvento::class)
            ->ubicaciones($request->user())->contains($id)) {
            abort(403, 'No tiene asignación vigente a esta ubicación.');
        }
    }
    public function stands(Request $request) {
        $query=Ubicacion::with('evento')->whereIn('tipo',['STAND','SUCURSAL','ALMACEN','ALMACEN_CENTRAL','CENTRAL'])->where('estado',true);
        if (!$request->user()->hasRole('ADMIN') && !$request->user()->can('contabilidad.ver') && !$request->user()->can('ventas.supervisar')) {
            $ubicaciones = app(\App\Services\AccesoTemporalEvento::class)
                ->ubicaciones($request->user());
            $query->whereIn('id_ubicacion',$ubicaciones);
        }
        return response()->json($query->orderBy('nombre')->get());
    }
    public function vendedores(Request $request) {
        $data = $request->validate(['id_ubicacion'=>'required|integer|exists:ubicaciones,id_ubicacion']);
        $id = (int) $data['id_ubicacion'];
        $this->autorizarStand($request, $id);
        return response()->json(AsignacionPersonal::with('personal.persona')
            ->where('id_ubicacion', $id)->where('estado', true)
            ->whereRaw('UPPER(TRIM(funcion_asignada)) = ?', ['VENDEDOR'])
            ->whereDate('fecha_inicio', '<=', today())
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today()))
            ->orderBy('id_asignacion')->get());
    }
    public function existencias(Request $request) {
        $data=$request->validate(['id_ubicacion'=>'required|integer|exists:ubicaciones,id_ubicacion']);
        $id=(int)$data['id_ubicacion']; $this->autorizarConsulta($request,$id);
        return response()->json(Inventario::with('lote.presentacion.producto')
            ->where('id_ubicacion',$id)->where('stock_actual','>',0)->orderBy('id_lote')->get());
    }
    public function index(Request $request) {
        $q=Venta::with(['ubicacion.evento','usuario','asignacionVendedor.personal.persona','detalles.lote.presentacion.producto'])->orderByDesc('id_venta');
        $request->validate(['fecha_desde'=>'nullable|date','fecha_hasta'=>'nullable|date|after_or_equal:fecha_desde','estado'=>'nullable|in:CONFIRMADA,ANULADA']);
        if($request->filled('fecha_desde')) $q->whereDate('fecha_venta','>=',$request->query('fecha_desde'));
        if($request->filled('fecha_hasta')) $q->whereDate('fecha_venta','<=',$request->query('fecha_hasta'));
        if($request->filled('estado')) $q->where('estado',$request->query('estado'));
        if($request->filled('id_ubicacion')) { $id=(int)$request->query('id_ubicacion');$this->autorizarConsulta($request,$id);$q->where('id_ubicacion',$id); }
        elseif(!$request->user()->hasRole('ADMIN') && !$request->user()->can('contabilidad.ver') && !$request->user()->can('ventas.supervisar')) {
            $loc = app(\App\Services\AccesoTemporalEvento::class)
                ->ubicaciones($request->user());
            $q->whereIn('id_ubicacion',$loc);
        }
        return response()->json($q->paginate(15));
    }
    public function show(Request $request, int $id) {
        $venta=Venta::with(['ubicacion.evento','usuario','asignacionVendedor.personal.persona','detalles.lote.presentacion.producto','usuarioAnulacion'])->findOrFail($id);
        if (!$request->user()->hasRole('ADMIN') && !$request->user()->can('contabilidad.ver') && !$request->user()->can('ventas.supervisar')) {
            $this->autorizarConsulta($request,(int)$venta->id_ubicacion);
        }
        return response()->json($venta);
    }
    /** Comprobante interno histórico; jamás factura fiscal. */
    public function comprobantePdf(Request $request, int $id)
    {
        $venta=Venta::with([
            'ubicacion.evento','usuario','asignacionVendedor.personal.persona',
            'detalles.lote.presentacion.producto','cobros'
        ])->findOrFail($id);
        $this->autorizarConsulta($request, (int)$venta->id_ubicacion);
        $pdf=new \App\Support\ReportePdfSimple();
        $pdf->titulo('EXCERNAT - COMPROBANTE INTERNO DE VENTA');
        $pdf->texto('Excelentes Cereales Naturales | No valido como factura fiscal');
        $pdf->texto('Comprobante #'.$venta->id_venta.' | Estado: '.$venta->estado);
        $pdf->texto('Fecha: '.($venta->fecha_venta?->format('d/m/Y H:i') ?? '-'));
        $pdf->textoPartido('Evento: '.($venta->ubicacion?->evento?->nombre??'Sin evento').' | Ubicacion: '.($venta->ubicacion?->nombre??'-'));
        $vendedor=$venta->asignacionVendedor?->personal?->persona;
        $nombre=trim(($vendedor?->nombres_razon_social??'').' '.($vendedor?->apellidos??''));
        $pdf->textoPartido('Registrado por: '.($venta->usuario?->name??'-'));
        $pdf->textoPartido('Vendedor: '.($nombre?:'Sin vendedor asociado'));
        $pdf->separador();
        $filas=[];
        foreach ($venta->detalles as $d) {
            $pres=$d->lote?->presentacion;
            $filas[]=[
                $pres?->producto?->nombre??'Producto no disponible',
                ($pres?->nombre_presentacion??''). ' | '.($d->lote?->codigo_lote??''),
                (string)$d->cantidad,
                number_format((float)$d->precio_unitario,2,'.',''),
                number_format((float)$d->subtotal,2,'.','')
            ];
        }
        $pdf->tabla(['Producto','Presentacion / lote','Cant.','Precio Bs','Subtotal Bs'],
            $filas,[136,170,40,80,83]);
        $bruto=(float)($venta->subtotal_bruto ?? $venta->detalles->sum('subtotal'));
        $descuento=(float)($venta->descuento_total??($bruto-(float)$venta->total));
        $pdf->texto('Subtotal bruto: Bs '.number_format($bruto,2,'.',''));
        $pdf->texto('Descuento: Bs '.number_format($descuento,2,'.',''));
        $pdf->texto('TOTAL NETO: Bs '.number_format((float)$venta->total,2,'.',''),12,true);
        if ($venta->cobros->count()) {
            $cobros=$venta->cobros->groupBy('metodo_pago');
            foreach ($cobros as $metodo=>$registros) {
                $pdf->texto('Cobrado por '.$metodo.': Bs '.number_format($registros->sum('monto'),2,'.',''));
            }
        } else {
            $pdf->texto('Sin cobro registrado; la confirmacion no acredita un pago.');
        }
        if ($venta->estado==='ANULADA') $pdf->textoPartido('VENTA ANULADA. Motivo: '.($venta->motivo_anulacion??'No indicado'));
        $pdf->separador();
        $pdf->texto('Documento interno de control - No valido como factura fiscal.');
        return response($pdf->generar(),200,[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'attachment; filename="EXCERNAT_comprobante_'.$venta->id_venta.'.pdf"',
            'Cache-Control'=>'private, no-store',
        ]);
    }

    public function anular(Request $request, int $id) {
        if (!$request->user()->hasRole('ADMIN')) abort(403,'No tiene permiso para anular ventas.');
        $data=$request->validate(['motivo_anulacion'=>'required|string|min:10|max:1000']);
        $venta=DB::transaction(function() use($id,$request,$data) {
            $venta=Venta::whereKey($id)->lockForUpdate()->firstOrFail();
            if($venta->estado!=='CONFIRMADA') throw ValidationException::withMessages(['estado'=>'Esta venta ya fue anulada o no esta confirmada.']);
            if (\App\Models\CobroVenta::where('id_venta',$venta->id_venta)->exists()) throw ValidationException::withMessages(['cobros'=>'La venta tiene cobros registrados. Debe realizarse la conciliación antes de anularla.']);
            $detalles=$venta->detalles()->orderBy('id_lote')->get();
            foreach($detalles as $detalle) {
                $inventario=Inventario::where('id_ubicacion',$venta->id_ubicacion)->where('id_lote',$detalle->id_lote)->lockForUpdate()->first();
                if(!$inventario) throw ValidationException::withMessages(['inventario'=>'No se encontro el inventario original para devolver el lote '.$detalle->id_lote.'.']);
                $inventario->increment('stock_actual',$detalle->cantidad);
            }
            $venta->update(['estado'=>'ANULADA','motivo_anulacion'=>$data['motivo_anulacion'],
                'id_usuario_anulacion'=>$request->user()->id,'fecha_anulacion'=>now()]);
            return $venta;
        },3);
        return response()->json(['message'=>'Venta anulada y existencias restituidas.','venta'=>$venta->load(['detalles','usuarioAnulacion'])]);
    }
    public function store(Request $request) {
        $data=$request->validate([
            'id_ubicacion'=>'required|integer|exists:ubicaciones,id_ubicacion',
            'observacion'=>'nullable|string|max:1000',
            'descuento_total'=>'nullable|numeric|min:0|max:9999999.99', // Compatibilidad con clientes anteriores
            'monto_cancelado'=>'nullable|numeric|gt:0|max:9999999.99',
            'id_asignacion_vendedor'=>'required|integer|exists:asignaciones_personal,id_asignacion',
            'detalles'=>'required|array|min:1|max:30',
            'detalles.*.id_lote'=>'required|integer|distinct|exists:lotes,id_lote',
            'detalles.*.cantidad'=>'required|integer|min:1|max:1000000',
            'pago'=>'required|array',
            'pago.metodo_pago'=>'required_with:pago|in:EFECTIVO,QR,TRANSFERENCIA',
            'pago.referencia'=>'nullable|string|max:120',
            'pago.clave_operacion'=>'required_with:pago|string|max:100',
        ]);
        $id=(int)$data['id_ubicacion']; $this->autorizarStand($request,$id);
        $asignacion = AsignacionPersonal::whereKey($data['id_asignacion_vendedor'])
            ->where('id_ubicacion', $id)->where('estado', true)
            ->whereRaw('UPPER(TRIM(funcion_asignada)) = ?', ['VENDEDOR'])
            ->whereDate('fecha_inicio', '<=', today())
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today()))
            ->whereHas('personal', fn($q) => $q->where('estado', true))->first();
        if (!$asignacion) throw ValidationException::withMessages(['id_asignacion_vendedor'=>'Seleccione un vendedor vigente del punto de venta.']);
        $venta=DB::transaction(function()use($data,$id,$request,$asignacion){
            // La caja se bloquea antes del inventario para serializar venta y cierre.
            $caja=null;
            if (!empty($data['pago'])) {
                $ubicacion=Ubicacion::with('evento')->findOrFail($id);
                \App\Http\Controllers\CajaPuntoVentaController::autorizar($request,$ubicacion);
                $caja=\App\Models\CajaPuntoVenta::where('id_ubicacion',$id)->where('estado','ABIERTA')->lockForUpdate()->first();
                if (!$caja) throw ValidationException::withMessages(['caja'=>'Abra la caja de esta ubicación antes de registrar una venta con cobro.']);
                if (\App\Models\CobroVenta::where('clave_operacion',$data['pago']['clave_operacion'])->exists()) throw ValidationException::withMessages(['pago'=>'Identificador de cobro repetido.']);
            }
            $items=collect($data['detalles'])->sortBy('id_lote')->values();
            $lineas=[];$totalCentavos=0;
            foreach($items as $item){
                $inventario=Inventario::with('lote.presentacion')->where('id_ubicacion',$id)
                    ->where('id_lote',$item['id_lote'])->lockForUpdate()->first();
                $cantidad=(int)$item['cantidad'];
                if(!$inventario || $inventario->stock_actual<$cantidad) {
                    throw ValidationException::withMessages(['detalles'=>"Stock insuficiente para lote {$item['id_lote']}."]);
                }
                $lote=$inventario->lote;$presentacion=$lote?->presentacion;
                if(!$lote || !$lote->estado || !$presentacion || !$presentacion->estado) {
                    throw ValidationException::withMessages(['detalles'=>"Lote o presentación inactiva: {$item['id_lote']}."]);
                }
                $precio=(string)$presentacion->precio_unitario;
                $centavos=(int)round((float)$precio*100);
                if($centavos<=0)throw ValidationException::withMessages(['detalles'=>'El precio unitario debe ser mayor a cero.']);
                $subtotal=$centavos*$cantidad; $totalCentavos+=$subtotal;
                $lineas[]=['inventario'=>$inventario,'id_lote'=>$item['id_lote'],'cantidad'=>$cantidad,
                    'precio'=>number_format($centavos/100,2,'.',''),'subtotal'=>number_format($subtotal/100,2,'.','')];
            }
            // El servidor calcula importes con precios vigentes de las presentaciones;
            // el importe comunicado por el cliente es el precio FINAL acordado,
            // no dinero entregado para calcular cambio ni un abono parcial.
            if (array_key_exists('monto_cancelado', $data) && $data['monto_cancelado'] !== null) {
                $totalFinalCentavos = (int) round((float) $data['monto_cancelado'] * 100);
                if ($totalFinalCentavos <= 0 || $totalFinalCentavos > $totalCentavos) {
                    throw ValidationException::withMessages([
                        'monto_cancelado' => 'El total acordado debe ser mayor a cero y no superar el subtotal.'
                    ]);
                }
                $descuentoCentavos = $totalCentavos - $totalFinalCentavos;
            } else {
                // Mantener compatibilidad con solicitudes anteriores.
                $descuentoCentavos = (int) round((float) ($data['descuento_total'] ?? 0) * 100);
                if ($descuentoCentavos >= $totalCentavos) {
                    throw ValidationException::withMessages([
                        'descuento_total' => 'El descuento debe ser menor al subtotal de la venta.'
                    ]);
                }
                $totalFinalCentavos = $totalCentavos - $descuentoCentavos;
            }
            $venta=Venta::create(['id_ubicacion'=>$id,'id_usuario'=>$request->user()->id,'id_asignacion_vendedor'=>$asignacion->id_asignacion,
                'fecha_venta'=>now(),'estado'=>'CONFIRMADA','subtotal_bruto'=>number_format($totalCentavos/100,2,'.',''),
                'descuento_total'=>number_format($descuentoCentavos/100,2,'.',''),
                'total'=>number_format($totalFinalCentavos/100,2,'.',''),
                'observacion'=>$data['observacion']??null]);
            foreach($lineas as $linea){
                $venta->detalles()->create(['id_lote'=>$linea['id_lote'],'cantidad'=>$linea['cantidad'],
                    'precio_unitario'=>$linea['precio'],'subtotal'=>$linea['subtotal']]);
                $linea['inventario']->decrement('stock_actual',$linea['cantidad']);
            }
            if ($caja) {
                $venta->cobros()->create(['id_caja'=>$caja->id_caja,'id_usuario'=>$request->user()->id,
                    'monto'=>number_format($totalFinalCentavos/100,2,'.',''),
                    'metodo_pago'=>$data['pago']['metodo_pago'],
                    'referencia'=>$data['pago']['referencia']??null,
                    'clave_operacion'=>$data['pago']['clave_operacion'],
                    'fecha_cobro'=>now()]);
            }
            return $venta;
        },3);
        return response()->json(['message'=>'Venta confirmada.','venta'=>$venta->load(['ubicacion','usuario','asignacionVendedor.personal.persona','detalles.lote.presentacion.producto'])],201);
    }
}
