<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CajaPuntoVenta extends Model {
 protected $table='cajas_punto_venta';protected $primaryKey='id_caja';
 protected $fillable=['id_ubicacion','id_usuario_apertura','id_usuario_cierre','fecha_apertura','fecha_cierre','monto_inicial','efectivo_contado','efectivo_esperado','diferencia','estado','observacion'];
 protected $casts=['fecha_apertura'=>'datetime','fecha_cierre'=>'datetime'];
 public function ubicacion(){return $this->belongsTo(Ubicacion::class,'id_ubicacion','id_ubicacion');}
}
