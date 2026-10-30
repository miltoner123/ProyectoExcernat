<?php
namespace App\Http\Controllers;
use App\Models\{CajaPuntoVenta,Ubicacion,Personal,AsignacionPersonal};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CajaPuntoVentaController extends Controller {
 public static function validarEvento(Ubicacion $u): void {
  $u->loadMissing('evento');
  if (!$u->estado) abort(422,'Punto de venta inactivo.');
  if ($u->id_evento && (!$u->evento || !$u->evento->estado || ($u->evento->fecha_fin && $u->evento->fecha_fin->lt(today())))) abort(422,'Evento finalizado: operaciones comerciales bloqueadas.');
 }
 public static function autorizar(Request $r, Ubicacion $u): void {
  self::validarEvento($u);
  if($r->user()->hasRole('ADMIN')) return;
  $ids=Personal::where('id_persona',$r->user()->id_persona)->where('estado',true)->pluck('id_personal');
  if(!AsignacionPersonal::whereIn('id_personal',$ids)->where('id_ubicacion',$u->id_ubicacion)->where('estado',true)->whereDate('fecha_inicio','<=',today())->where(fn($q)=>$q->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',today()))->exists()) abort(403,'No tiene asignación vigente a este punto de venta.');
 }
 public function ubicaciones(Request $r){
  $q=Ubicacion::with('evento')->where('estado',true);
  if(!$r->user()->hasRole('ADMIN')===false){
   $ids=Personal::where('id_persona',$r->user()->id_persona)->where('estado',true)->pluck('id_personal');
   $q->whereIn('id_ubicacion',AsignacionPersonal::whereIn('id_personal',$ids)->where('estado',true)->whereDate('fecha_inicio','<=',today())->where(fn($w)=>$w->whereNull('fecha_fin')->orWhereDate('fecha_fin','>=',today()))->pluck('id_ubicacion'));
  }
  return $q->orderBy('nombre')->get();
 }
 public function index(Request $r){
  $data=$r->validate(['id_ubicacion'=>'required|integer|exists:ubicaciones,id_ubicacion']);
  $u=Ubicacion::with('evento')->findOrFail($data['id_ubicacion']);
  // Consultar el historial debe ser posible aunque el evento haya finalizado.
  if(!$r->user()->hasRole('ADMIN')===false){
   $ids=Personal::where('id_persona',$r->user()->id_persona)->where('estado',true)->pluck('id_personal');
   if(!AsignacionPersonal::whereIn('id_personal',$ids)->where('id_ubicacion',$u->id_ubicacion)->exists())abort(403);
  }
  return CajaPuntoVenta::where('id_ubicacion',$u->id_ubicacion)->orderByDesc('id_caja')->limit(30)->get()->map(fn($c)=>$this->resumen($c));
 }
 private function resumen(CajaPuntoVenta $c): array {
  $s=DB::table('cobros_ventas')->where('id_caja',$c->id_caja)->selectRaw("COALESCE(SUM(CASE WHEN metodo_pago='EFECTIVO' THEN monto ELSE 0 END),0) AS efectivo,COALESCE(SUM(CASE WHEN metodo_pago='QR' THEN monto ELSE 0 END),0) AS qr,COALESCE(SUM(CASE WHEN metodo_pago='TRANSFERENCIA' THEN monto ELSE 0 END),0) AS transferencia")->first();
  return ['caja'=>$c,'cobros'=>$s,'efectivo_esperado_actual'=>round((float)$c->monto_inicial+(float)$s->efectivo,2)];
 }
 public function abrir(Request $r){
  $d=$r->validate(['id_ubicacion'=>'required|integer|exists:ubicaciones,id_ubicacion','monto_inicial'=>'required|numeric|min:0|max:9999999999.99']);
  $c=DB::transaction(function()use($r,$d){
   $u=Ubicacion::with('evento')->whereKey($d['id_ubicacion'])->lockForUpdate()->firstOrFail();self::autorizar($r,$u);
   if(CajaPuntoVenta::where('id_ubicacion',$u->id_ubicacion)->where('estado','ABIERTA')->exists())throw ValidationException::withMessages(['caja'=>'Ya existe una caja abierta en esta ubicación.']);
   return CajaPuntoVenta::create(['id_ubicacion'=>$u->id_ubicacion,'id_usuario_apertura'=>$r->user()->id,'fecha_apertura'=>now(),'monto_inicial'=>$d['monto_inicial'],'estado'=>'ABIERTA']);
  },3);return response()->json($this->resumen($c),201);
 }
 public function cerrar(Request $r,int $id){
  $d=$r->validate(['efectivo_contado'=>'required|numeric|min:0|max:9999999999.99','observacion'=>'nullable|string|max:1000']);
  $result=DB::transaction(function()use($r,$d,$id){
   $c=CajaPuntoVenta::whereKey($id)->lockForUpdate()->firstOrFail();
   // Se permite cerrar una caja de evento finalizado, pero se valida la asignación.
   $u=Ubicacion::findOrFail($c->id_ubicacion);
   if(!$r->user()->hasRole('ADMIN')===false){
    $ids=Personal::where('id_persona',$r->user()->id_persona)->where('estado',true)->pluck('id_personal');
    if(!AsignacionPersonal::whereIn('id_personal',$ids)->where('id_ubicacion',$u->id_ubicacion)->exists())abort(403);
   }
   if($c->estado!=='ABIERTA')throw ValidationException::withMessages(['caja'=>'La caja ya fue cerrada.']);
   $s=$this->resumen($c);$esperado=round($s['efectivo_esperado_actual'],2);$dif=round((float)$d['efectivo_contado']-$esperado,2);
   if(abs($dif)>=0.01 && empty(trim($d['observacion']??'')))throw ValidationException::withMessages(['observacion'=>'Explique la diferencia de caja.']);
   $c->update(['estado'=>'CERRADA','id_usuario_cierre'=>$r->user()->id,'fecha_cierre'=>now(),'efectivo_contado'=>$d['efectivo_contado'],'efectivo_esperado'=>$esperado,'diferencia'=>$dif,'observacion'=>$d['observacion']??null]);
   return $this->resumen($c);
  },3);return response()->json($result);
 }
}
