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
        $persona=$request->user()->id_persona;
        $personal=Personal::where('id_persona',$persona)->where('estado',true)->pluck('id_personal');
        $asignado=AsignacionPersonal::whereIn('id_personal',$personal)->where('id_ubicacion',$id)
            ->where('estado',true)->whereDate('fecha_inicio','<=',now()->toDateString())
            ->where(function($q){$q->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',now()->toDateString());})->exists();
        if (!$asignado) abort(403,'No tiene asignación vigente a este punto de venta.');
    }
    private function autorizarConsulta(Request $request, int $id): void {
        if ($request->user()->hasRole('ADMIN')) return;
        $personal=Personal::where('id_persona',$request->user()->id_persona)->where('estado',true)->pluck('id_personal');
        if (!AsignacionPersonal::whereIn('id_personal',$personal)->where('id_ubicacion',$id)->exists()) abort(403,'No tiene acceso al historial de esta ubicación.');
    }
    public function stands(Request $request) {
        $query=Ubicacion::with('evento')->whereIn('tipo',['STAND','SUCURSAL','ALMACEN','ALMACEN_CENTRAL','CENTRAL'])->where('estado',true);
        if (!$request->user()->hasRole('ADMIN')) {
            $ids=Personal::where('id_persona',$request->user()->id_persona)->where('estado',true)->pluck('id_personal');
            $ubicaciones=AsignacionPersonal::whereIn('id_personal',$ids)->where('estado',true)
                ->whereDate('fecha_inicio','<=',now()->toDateString())
                ->where(fn($q)=>$q->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',now()->toDateString()))
                ->pluck('id_ubicacion');
            $query->whereIn('id_ubicacion',$ubicaciones);
        }
        return response()->json($query->orderBy('nombre')->get());
    }
    public function existencias(Request $request) {
        $data=$request->validate(['id_ubicacion'=>'required|integer|exists:ubicaciones,id_ubicacion']);
        $id=(int)$data['id_ubicacion']; $this->autorizarStand($request,$id);
        return response()->json(Inventario::with('lote.presentacion.producto')
            ->where('id_ubicacion',$id)->where('stock_actual','>',0)->orderBy('id_lote')->get());
    }
    public function index(Request $request) {
        $q=Venta::with(['ubicacion.evento','usuario','detalles.lote.presentacion.producto'])->orderByDesc('id_venta');
        $request->validate(['fecha_desde'=>'nullable|date','fecha_hasta'=>'nullable|date|after_or_equal:fecha_desde','estado'=>'nullable|in:CONFIRMADA,ANULADA']);
        if($request->filled('fecha_desde')) $q->whereDate('fecha_venta','>=',$request->query('fecha_desde'));
        if($request->filled('fecha_hasta')) $q->whereDate('fecha_venta','<=',$request->query('fecha_hasta'));
        if($request->filled('estado')) $q->where('estado',$request->query('estado'));
        if($request->filled('id_ubicacion')) { $id=(int)$request->query('id_ubicacion');$this->autorizarConsulta($request,$id);$q->where('id_ubicacion',$id); }
        elseif(!$request->user()->hasRole('ADMIN')) {
            $ids=Personal::where('id_persona',$request->user()->id_persona)->where('estado',true)->pluck('id_personal');
            $loc=AsignacionPersonal::whereIn('id_personal',$ids)->where('estado',true)->whereDate('fecha_inicio','<=',now()->toDateString())
              ->where(fn($w)=>$w->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',now()->toDateString()))->pluck('id_ubicacion');
            $q->whereIn('id_ubicacion',$loc);
        }
        return response()->json($q->paginate(15));
    }
    public function show(Request $request, int $id) {
        $venta=Venta::with(['ubicacion.evento','usuario','detalles.lote.presentacion.producto','usuarioAnulacion'])->findOrFail($id);
        if (!$request->user()->hasRole('ADMIN')) {
            $this->autorizarConsulta($request,(int)$venta->id_ubicacion);
        }
        return response()->json($venta);
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
            'detalles'=>'required|array|min:1|max:30',
            'detalles.*.id_lote'=>'required|integer|distinct|exists:lotes,id_lote',
            'detalles.*.cantidad'=>'required|integer|min:1|max:1000000',
            'pago'=>'required|array',
            'pago.metodo_pago'=>'required_with:pago|in:EFECTIVO,QR,TRANSFERENCIA',
            'pago.referencia'=>'nullable|string|max:120',
            'pago.clave_operacion'=>'required_with:pago|string|max:100',
        ]);
        $id=(int)$data['id_ubicacion']; $this->autorizarStand($request,$id);
        $venta=DB::transaction(function()use($data,$id,$request){
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
            $venta=Venta::create(['id_ubicacion'=>$id,'id_usuario'=>$request->user()->id,
                'fecha_venta'=>now(),'estado'=>'CONFIRMADA','total'=>number_format($totalCentavos/100,2,'.',''),
                'observacion'=>$data['observacion']??null]);
            foreach($lineas as $linea){
                $venta->detalles()->create(['id_lote'=>$linea['id_lote'],'cantidad'=>$linea['cantidad'],
                    'precio_unitario'=>$linea['precio'],'subtotal'=>$linea['subtotal']]);
                $linea['inventario']->decrement('stock_actual',$linea['cantidad']);
            }
            if ($caja) {
                $venta->cobros()->create(['id_caja'=>$caja->id_caja,'id_usuario'=>$request->user()->id,
                    'monto'=>number_format($totalCentavos/100,2,'.',''),
                    'metodo_pago'=>$data['pago']['metodo_pago'],
                    'referencia'=>$data['pago']['referencia']??null,
                    'clave_operacion'=>$data['pago']['clave_operacion'],
                    'fecha_cobro'=>now()]);
            }
            return $venta;
        },3);
        return response()->json(['message'=>'Venta confirmada.','venta'=>$venta->load(['ubicacion','usuario','detalles.lote.presentacion.producto'])],201);
    }
}
