<?php
namespace App\Http\Controllers;
use App\Models\{Venta,Inventario,Ubicacion,AsignacionPersonal,Personal};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class VentaController extends Controller {
    private function autorizarStand(Request $request, int $id): void {
        $ubicacion=Ubicacion::findOrFail($id);
        if (strtoupper($ubicacion->tipo)!=='STAND' || !$ubicacion->estado) {
            throw ValidationException::withMessages(['id_ubicacion'=>'Seleccione un stand activo.']);
        }
        if ($request->user()->hasAnyRole(['ADMIN','GERENTE_GENERAL'])) return;
        $persona=$request->user()->id_persona;
        $personal=Personal::where('id_persona',$persona)->where('estado',true)->pluck('id_personal');
        $asignado=AsignacionPersonal::whereIn('id_personal',$personal)->where('id_ubicacion',$id)
            ->where('estado',true)->whereDate('fecha_inicio','<=',now()->toDateString())
            ->where(function($q){$q->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',now()->toDateString());})->exists();
        if (!$asignado) abort(403,'No tiene una asignación vigente a este stand.');
    }
    public function stands(Request $request) {
        $query=Ubicacion::with('evento')->where('tipo','STAND')->where('estado',true);
        if (!$request->user()->hasAnyRole(['ADMIN','GERENTE_GENERAL'])) {
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
        if($request->filled('id_ubicacion')) { $id=(int)$request->query('id_ubicacion');$this->autorizarStand($request,$id);$q->where('id_ubicacion',$id); }
        elseif(!$request->user()->hasAnyRole(['ADMIN','GERENTE_GENERAL']) && !$request->user()->can('ventas.supervisar')) {
            $ids=Personal::where('id_persona',$request->user()->id_persona)->where('estado',true)->pluck('id_personal');
            $loc=AsignacionPersonal::whereIn('id_personal',$ids)->where('estado',true)->whereDate('fecha_inicio','<=',now()->toDateString())
              ->where(fn($w)=>$w->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',now()->toDateString()))->pluck('id_ubicacion');
            $q->whereIn('id_ubicacion',$loc);
        }
        return response()->json($q->paginate(15));
    }
    public function store(Request $request) {
        $data=$request->validate([
            'id_ubicacion'=>'required|integer|exists:ubicaciones,id_ubicacion',
            'observacion'=>'nullable|string|max:1000',
            'detalles'=>'required|array|min:1|max:30',
            'detalles.*.id_lote'=>'required|integer|distinct|exists:lotes,id_lote',
            'detalles.*.cantidad'=>'required|integer|min:1|max:1000000',
        ]);
        $id=(int)$data['id_ubicacion']; $this->autorizarStand($request,$id);
        $venta=DB::transaction(function()use($data,$id,$request){
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
            return $venta;
        },3);
        return response()->json(['message'=>'Venta confirmada.','venta'=>$venta->load(['ubicacion','usuario','detalles.lote.presentacion.producto'])],201);
    }
}
