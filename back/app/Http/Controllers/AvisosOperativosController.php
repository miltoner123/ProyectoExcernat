<?php
namespace App\Http\Controllers;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AvisosOperativosController extends Controller {
 public function index(Request $request) {
  $usuario=$request->user();
  $logistica=$usuario->hasRole('ADMIN') || $usuario->can('recepciones.confirmar');
  $comercial=$usuario->hasRole('ADMIN') || $usuario->can('eventos.gestionar');
  abort_unless($logistica || $comercial,403);
  $avisos=[];
  if($logistica) {
   foreach(Ubicacion::whereIn('tipo',['STAND','SUCURSAL'])->where('estado',true)
       ->where('estado_inventario','PENDIENTE_VALIDACION')->orderBy('id_ubicacion')->limit(30)->get() as $u) {
    $avisos[]=['tipo'=>'HABILITACION','id'=>$u->id_ubicacion,'titulo'=>'Inventario pendiente de habilitación',
      'detalle'=>$u->nombre,'fecha'=>$u->created_at];
   }
   // El estado de recepciones se consulta en el módulo de Recepciones de producción.
  }
  return response()->json(['total'=>count($avisos),'avisos'=>$avisos]);
 }
}
