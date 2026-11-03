<?php
namespace App\Http\Controllers;
use App\Models\{Evento,MovimientoInventario,CajaPuntoVenta,Inventario};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\AccesoTemporalEvento;
class CierreEventoController extends Controller {
 private function pendientes(Evento $e): array {
  $ids=$e->ubicaciones()->where('tipo','STAND')->pluck('id_ubicacion');
  $stock=Inventario::whereIn('id_ubicacion',$ids)->where('stock_actual','>',0)->sum('stock_actual');
  $transito=MovimientoInventario::where('estado_recepcion','PENDIENTE')->where(function($q)use($ids){$q->whereIn('id_ubicacion_origen',$ids)->orWhereIn('id_ubicacion_destino',$ids);})->count();
  $diferencias=MovimientoInventario::where('estado_recepcion','OBSERVADA')->where(function($q)use($ids){$q->whereIn('id_ubicacion_origen',$ids)->orWhereIn('id_ubicacion_destino',$ids);})->count();
  $cajas=CajaPuntoVenta::whereIn('id_ubicacion',$ids)->where('estado','ABIERTA')->count();
  return ['stock_sobrante'=>(int)$stock,'transferencias_pendientes'=>$transito,'recepciones_observadas'=>$diferencias,'cajas_abiertas'=>$cajas];
 }
 public function resumen(Request $r,int $id){
  $u=$r->user();$acceso=app(AccesoTemporalEvento::class);
  abort_unless($u->hasRole('ADMIN') || $u->can('eventos.gestionar') || $acceso->eventos($u)->contains($id),403);
  $e=Evento::findOrFail($id);return response()->json(['evento'=>$e,'pendientes'=>$this->pendientes($e)]);}
 public function finalizar(Request $r,int $id){
  $usuario = $r->user();
  $acceso = app(AccesoTemporalEvento::class);
  abort_unless($usuario->hasRole('ADMIN') || $usuario->can('eventos.gestionar') || $acceso->eventos($usuario)->contains($id), 403);
  $e=DB::transaction(function()use($id){$e=Evento::whereKey($id)->lockForUpdate()->firstOrFail();
   if(!$e->estado || $e->estado_operativo==='CERRADO')throw ValidationException::withMessages(['evento'=>'Evento inactivo o cerrado.']);
   if($e->fecha_inicio && $e->fecha_inicio->startOfDay()->isFuture())throw ValidationException::withMessages(['evento'=>'No se puede finalizar un evento antes de su fecha de inicio.']);
   $pend=$this->pendientes($e);
   if(array_sum($pend)>0) throw ValidationException::withMessages(['pendientes'=>'Antes de finalizar deben devolverse existencias, recibirse movimientos, conciliar diferencias y cerrar cajas: '.json_encode($pend)]);
   if($e->estado_operativo!=='FINALIZADO'){$e->update(['estado_operativo'=>'FINALIZADO','fecha_finalizacion'=>now()]);}return $e;},3);
  return response()->json(['message'=>'Evento finalizado manualmente después de conciliar existencias y operaciones.','evento'=>$e]);
 }
 public function cerrar(Request $r,int $id){
  $e=DB::transaction(function()use($id){$e=Evento::whereKey($id)->lockForUpdate()->firstOrFail();
   if($e->estado_operativo!=='FINALIZADO')throw ValidationException::withMessages(['evento'=>'Primero debe finalizar el evento.']);
   $pend=$this->pendientes($e);if(array_sum($pend)>0)throw ValidationException::withMessages(['pendientes'=>'No puede cerrar: '.json_encode($pend)]);
   $e->update(['estado_operativo'=>'CERRADO','fecha_cierre'=>now(),'estado'=>false]);return $e;},3);
  return response()->json(['message'=>'Evento cerrado administrativamente.','evento'=>$e]);
 }
}
