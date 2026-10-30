<?php
namespace App\Http\Controllers;
use App\Models\{Venta,CobroVenta,Personal,AsignacionPersonal};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CobroVentaController extends Controller {
    private function verificarAcceso(Request $request, Venta $venta): void {
        if ($request->user()->hasRole('ADMIN')) return;
        $personal=Personal::where('id_persona',$request->user()->id_persona)->where('estado',true)->pluck('id_personal');
        $vigente=AsignacionPersonal::whereIn('id_personal',$personal)->where('id_ubicacion',$venta->id_ubicacion)
            ->where('estado',true)->whereDate('fecha_inicio','<=',now()->toDateString())
            ->where(fn($q)=>$q->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',now()->toDateString()))->exists();
        if (!$vigente) abort(403,'No tiene asignación vigente al stand de esta venta.');
    }
    private function resumen(Venta $venta): array {
        $cobrado=(int) round((float)CobroVenta::where('id_venta',$venta->id_venta)->sum('monto')*100);
        $total=(int) round((float)$venta->total*100);
        return ['venta'=>$venta,'total'=>number_format($total/100,2,'.',''),
            'cobrado'=>number_format($cobrado/100,2,'.',''),
            'saldo'=>number_format(max(0,$total-$cobrado)/100,2,'.',''),
            'estado_pago'=>$cobrado===0?'PENDIENTE':($cobrado>=$total?'PAGADA':'PARCIAL')];
    }
    public function index(Request $request) {
        $request->validate(['id_venta'=>'required|integer|exists:ventas,id_venta']);
        $venta=Venta::with(['ubicacion.evento','usuario'])->findOrFail((int)$request->query('id_venta'));
        $this->verificarAcceso($request,$venta);
        return response()->json($this->resumen($venta)+[
            'cobros'=>CobroVenta::with('usuario')->where('id_venta',$venta->id_venta)->orderByDesc('id_cobro')->get()
        ]);
    }
    public function store(Request $request) {
        $data=$request->validate([
            'id_venta'=>'required|integer|exists:ventas,id_venta',
            'monto'=>'required|numeric|decimal:0,2|gt:0|max:9999999999.99',
            'metodo_pago'=>'required|in:EFECTIVO,QR,TRANSFERENCIA',
            'referencia'=>'nullable|string|max:120',
            'clave_operacion'=>'required|string|max:100',
            'observacion'=>'nullable|string|max:500',
        ]);
        $cobro=DB::transaction(function() use ($data,$request) {
            $venta=Venta::whereKey($data['id_venta'])->lockForUpdate()->firstOrFail();
            $this->verificarAcceso($request,$venta);
            if($venta->estado!=='CONFIRMADA') throw ValidationException::withMessages(['venta'=>'No se puede cobrar una venta anulada.']);
            $existente=CobroVenta::where('clave_operacion',$data['clave_operacion'])->first();
            if($existente) {
                if((int)$existente->id_venta!==(int)$venta->id_venta) throw ValidationException::withMessages(['clave_operacion'=>'Identificador ya utilizado.']);
                return $existente;
            }
            $monto=(int)round((float)$data['monto']*100);
            $total=(int)round((float)$venta->total*100);
            $pagado=(int)round((float)CobroVenta::where('id_venta',$venta->id_venta)->sum('monto')*100);
            if($monto<=0 || $monto>$total-$pagado) throw ValidationException::withMessages(['monto'=>'El importe supera el saldo pendiente.']);
            $ubicacion=\App\Models\Ubicacion::with('evento')->findOrFail($venta->id_ubicacion);
            CajaPuntoVentaController::autorizar($request,$ubicacion);
            $caja=\App\Models\CajaPuntoVenta::where('id_ubicacion',$venta->id_ubicacion)->where('estado','ABIERTA')->lockForUpdate()->first();
            if(!$caja) throw ValidationException::withMessages(['caja'=>'Debe abrir una caja para registrar cobros nuevos.']);
            return CobroVenta::create([
                'id_caja'=>$caja->id_caja,'id_venta'=>$venta->id_venta,'id_usuario'=>$request->user()->id,
                'monto'=>number_format($monto/100,2,'.',''),'metodo_pago'=>$data['metodo_pago'],
                'referencia'=>$data['referencia']??null,'clave_operacion'=>$data['clave_operacion'],
                'observacion'=>$data['observacion']??null,'fecha_cobro'=>now()
            ]);
        },3);
        return response()->json(['message'=>'Cobro registrado.','cobro'=>$cobro],201);
    }
}
