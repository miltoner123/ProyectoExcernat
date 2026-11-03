<?php
namespace App\Http\Controllers;
use App\Models\Ubicacion;
use App\Models\Evento;
use App\Services\AccesoTemporalEvento;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AvisosOperativosController extends Controller {
 public function index(Request $request) {
  $usuario=$request->user();
  $logistica=$usuario->hasRole('ADMIN') || $usuario->can('recepciones.confirmar');
  $comercial=$usuario->hasRole('ADMIN') || $usuario->can('eventos.gestionar');
  $acceso=app(AccesoTemporalEvento::class);
  $idsEventos=$acceso->eventos($usuario);
  abort_unless($logistica || $comercial || $idsEventos->isNotEmpty(),403);
  $avisos=[];
  if($logistica) {
   foreach(Ubicacion::whereIn('tipo',['STAND','SUCURSAL'])->where('estado',true)
       ->where('estado_inventario','PENDIENTE_VALIDACION')->orderBy('id_ubicacion')->limit(30)->get() as $u) {
    $avisos[]=['tipo'=>'HABILITACION','id'=>$u->id_ubicacion,'titulo'=>'Inventario pendiente de habilitación',
      'detalle'=>$u->nombre,'fecha'=>$u->created_at];
   }
   // El estado de recepciones se consulta en el módulo de Recepciones de producción.
  }
  $eventos=Evento::query()->where('estado',true)->where('fecha_fin','<',today())
    ->whereNotIn('estado_operativo',['FINALIZADO','CERRADO']);
  if(!$comercial) $eventos->whereIn('id_evento',$idsEventos);
  foreach($eventos->orderBy('fecha_fin')->limit(30)->get() as $e) {
    $ubicaciones=$e->ubicaciones()->where('tipo','STAND')->pluck('id_ubicacion');
    $stock=Inventario::whereIn('id_ubicacion',$ubicaciones)->where('stock_actual','>',0)->sum('stock_actual');
    $pendientes=MovimientoInventario::where('estado_recepcion','PENDIENTE')
      ->where(fn($q)=>$q->whereIn('id_ubicacion_origen',$ubicaciones)->orWhereIn('id_ubicacion_destino',$ubicaciones))->count();
    $avisos[]=['tipo'=>'EVENTO_PENDIENTE_CIERRE','id'=>$e->id_evento,
      'titulo'=>'Evento fuera de fecha: cierre manual pendiente',
      'detalle'=>$e->nombre.' · Devolver existencias: '.$stock.' · Transferencias pendientes: '.$pendientes,
      'fecha'=>$e->fecha_fin];
  }
  return response()->json(['total'=>count($avisos),'avisos'=>$avisos]);
 }
}
